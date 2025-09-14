<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class AggregatedCompany extends BaseModel
{
    protected $fillable = [
        'name', 'slug', 'description', 'logo_url', 'website_url',
        'industry', 'size_range', 'headquarters', 'rating',
        'review_count', 'active_jobs_count', 'social_links', 'benefits'
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'social_links' => 'array',
        'benefits' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (!$company->slug) {
                $company->slug = Str::slug($company->name);
            }
        });
    }

    /**
     * Get aggregated jobs for this company
     */
    public function aggregatedJobs(): BelongsToMany
    {
        return $this->belongsToMany(AggregatedJob::class, 'aggregated_job_companies');
    }

    /**
     * Get active jobs count
     */
    public function getActiveJobsCount(): int
    {
        return $this->aggregatedJobs()->active()->count();
    }

    /**
     * Update jobs count
     */
    public function updateJobsCount(): void
    {
        $this->active_jobs_count = $this->getActiveJobsCount();
        $this->save();
    }

    /**
     * Get formatted rating
     */
    public function getFormattedRating(): string
    {
        if (!$this->rating) {
            return 'No rating';
        }

        return number_format($this->rating, 1) . '/5.0 (' . $this->review_count . ' reviews)';
    }

    /**
     * Scope by industry
     */
    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope with active jobs
     */
    public function scopeWithActiveJobs($query)
    {
        return $query->where('active_jobs_count', '>', 0);
    }

    /**
     * Scope by rating
     */
    public function scopeByMinRating($query, $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }
}