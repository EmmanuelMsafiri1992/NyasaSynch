<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryData extends Model
{
    protected $fillable = [
        'job_title',
        'normalized_title',
        'category_id',
        'industry',
        'company_size',
        'location_country',
        'location_state',
        'location_city',
        'cost_of_living_index',
        'years_experience_min',
        'years_experience_max',
        'salary_min',
        'salary_max',
        'salary_median',
        'currency',
        'salary_type',
        'bonus_average',
        'equity_percentage',
        'benefits_data',
        'data_source',
        'sample_size',
        'confidence_score',
        'data_collected_at',
        'is_verified'
    ];

    protected $casts = [
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'salary_median' => 'decimal:2',
        'bonus_average' => 'decimal:2',
        'equity_percentage' => 'decimal:4',
        'cost_of_living_index' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'benefits_data' => 'array',
        'data_collected_at' => 'date',
        'is_verified' => 'boolean'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    /**
     * Get total compensation (salary + bonus)
     */
    public function getTotalCompensationMinAttribute(): float
    {
        return $this->salary_min + ($this->bonus_average ?? 0);
    }

    public function getTotalCompensationMaxAttribute(): float
    {
        return $this->salary_max + ($this->bonus_average ?? 0);
    }

    /**
     * Scope for job title search
     */
    public function scopeForJobTitle($query, string $title)
    {
        return $query->where(function($q) use ($title) {
            $q->where('job_title', 'like', "%{$title}%")
              ->orWhere('normalized_title', 'like', "%{$title}%");
        });
    }

    /**
     * Scope for location filtering
     */
    public function scopeForLocation($query, string $location)
    {
        return $query->where(function($q) use ($location) {
            $q->where('location_city', 'like', "%{$location}%")
              ->orWhere('location_state', 'like', "%{$location}%")
              ->orWhere('location_country', 'like', "%{$location}%");
        });
    }

    /**
     * Scope for experience range
     */
    public function scopeForExperience($query, int $years)
    {
        return $query->where(function($q) use ($years) {
            $q->where('years_experience_min', '<=', $years)
              ->where(function($subQ) use ($years) {
                  $subQ->whereNull('years_experience_max')
                       ->orWhere('years_experience_max', '>=', $years);
              });
        });
    }

    /**
     * Scope for recent data
     */
    public function scopeRecent($query, int $months = 12)
    {
        return $query->where('data_collected_at', '>=', now()->subMonths($months));
    }

    /**
     * Scope for verified data only
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Get salary range with confidence interval
     */
    public function getSalaryRangeWithConfidence(): array
    {
        $confidenceMultiplier = $this->confidence_score / 100;
        
        $adjustedMin = $this->salary_min * $confidenceMultiplier;
        $adjustedMax = $this->salary_max * $confidenceMultiplier;
        
        return [
            'min' => round($adjustedMin, 2),
            'max' => round($adjustedMax, 2),
            'median' => $this->salary_median,
            'confidence' => $this->confidence_score
        ];
    }

    /**
     * Adjust salary for cost of living
     */
    public function adjustForCostOfLiving(float $targetCostOfLiving): array
    {
        if (!$this->cost_of_living_index) {
            return $this->getSalaryRangeWithConfidence();
        }

        $adjustmentRatio = $targetCostOfLiving / $this->cost_of_living_index;
        
        return [
            'min' => round($this->salary_min * $adjustmentRatio, 2),
            'max' => round($this->salary_max * $adjustmentRatio, 2),
            'median' => $this->salary_median ? round($this->salary_median * $adjustmentRatio, 2) : null,
            'adjustment_ratio' => $adjustmentRatio,
            'original_col_index' => $this->cost_of_living_index,
            'target_col_index' => $targetCostOfLiving
        ];
    }
}

// Related models for salary features
class UserSalarySubmission extends Model
{
    protected $fillable = [
        'user_id',
        'job_title',
        'company_name',
        'location_city',
        'location_country',
        'years_experience',
        'annual_salary',
        'currency',
        'bonus',
        'additional_compensation',
        'skills',
        'is_anonymous',
        'is_verified'
    ];

    protected $casts = [
        'annual_salary' => 'decimal:2',
        'bonus' => 'decimal:2',
        'skills' => 'array',
        'is_anonymous' => 'boolean',
        'is_verified' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

class CostOfLiving extends Model
{
    protected $fillable = [
        'city',
        'country',
        'index_score',
        'rent_index',
        'groceries_index',
        'restaurant_index',
        'purchasing_power_index',
        'last_updated'
    ];

    protected $casts = [
        'index_score' => 'decimal:2',
        'rent_index' => 'decimal:2',
        'groceries_index' => 'decimal:2',
        'restaurant_index' => 'decimal:2',
        'purchasing_power_index' => 'decimal:2',
        'last_updated' => 'date'
    ];

    /**
     * Get cost of living for a location
     */
    public static function getForLocation(string $city, string $country = null): ?self
    {
        $query = self::where('city', 'like', "%{$city}%");
        
        if ($country) {
            $query->where('country', 'like', "%{$country}%");
        }
        
        return $query->first();
    }

    /**
     * Compare two locations
     */
    public static function compare(string $fromCity, string $toCity): array
    {
        $fromCol = self::getForLocation($fromCity);
        $toCol = self::getForLocation($toCity);
        
        if (!$fromCol || !$toCol) {
            return [];
        }
        
        return [
            'from' => [
                'city' => $fromCol->city,
                'country' => $fromCol->country,
                'index' => $fromCol->index_score
            ],
            'to' => [
                'city' => $toCol->city,
                'country' => $toCol->country,
                'index' => $toCol->index_score
            ],
            'ratio' => $toCol->index_score / $fromCol->index_score,
            'percentage_difference' => (($toCol->index_score - $fromCol->index_score) / $fromCol->index_score) * 100
        ];
    }
}