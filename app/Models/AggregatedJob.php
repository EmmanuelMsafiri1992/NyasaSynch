<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class AggregatedJob extends BaseModel
{
    protected $fillable = [
        'aggregation_source_id', 'external_id', 'title', 'description',
        'company_name', 'company_logo_url', 'location', 'country_code', 'city',
        'salary_range', 'salary_min', 'salary_max', 'salary_currency',
        'employment_type', 'experience_level', 'category', 'skills',
        'external_url', 'application_url', 'posted_at', 'expires_at',
        'is_active', 'is_featured', 'views_count', 'applications_count', 'raw_data'
    ];

    protected $casts = [
        'skills' => 'array',
        'posted_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'raw_data' => 'array',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
    ];

    /**
     * Get the aggregation source
     */
    public function aggregationSource(): BelongsTo
    {
        return $this->belongsTo(JobAggregationSource::class);
    }

    /**
     * Get users who saved this job
     */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_saved_aggregated_jobs')
            ->withPivot('notes')
            ->withTimestamps();
    }

    /**
     * Get associated companies
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(AggregatedCompany::class, 'aggregated_job_companies');
    }

    /**
     * Check if job is active
     */
    public function isActive(): bool
    {
        return $this->is_active && (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Check if job is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if job is featured
     */
    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    /**
     * Get formatted salary range
     */
    public function getFormattedSalary(): string
    {
        if ($this->salary_min && $this->salary_max) {
            return number_format($this->salary_min) . ' - ' . number_format($this->salary_max) . ' ' . $this->salary_currency;
        }

        if ($this->salary_range) {
            return $this->salary_range;
        }

        return 'Salary not specified';
    }

    /**
     * Get age of the job posting
     */
    public function getJobAge(): string
    {
        return $this->posted_at->diffForHumans();
    }

    /**
     * Get time until expiry
     */
    public function getTimeUntilExpiry(): ?string
    {
        if (!$this->expires_at) {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment applications count
     */
    public function incrementApplications(): void
    {
        $this->increment('applications_count');
    }

    /**
     * Get skills as comma separated string
     */
    public function getSkillsText(): string
    {
        if (!$this->skills) {
            return '';
        }

        return implode(', ', $this->skills);
    }

    /**
     * Search jobs by keywords
     */
    public function scopeSearch($query, $keywords)
    {
        return $query->whereFullText(['title', 'description', 'company_name'], $keywords);
    }

    /**
     * Scope for active jobs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for featured jobs
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope by location
     */
    public function scopeByLocation($query, $location)
    {
        return $query->where('location', 'like', "%{$location}%")
            ->orWhere('city', 'like', "%{$location}%");
    }

    /**
     * Scope by country
     */
    public function scopeByCountry($query, $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope by employment type
     */
    public function scopeByEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    /**
     * Scope by experience level
     */
    public function scopeByExperienceLevel($query, $level)
    {
        return $query->where('experience_level', $level);
    }

    /**
     * Scope by salary range
     */
    public function scopeBySalaryRange($query, $minSalary, $maxSalary)
    {
        return $query->where(function($q) use ($minSalary, $maxSalary) {
            $q->whereBetween('salary_min', [$minSalary, $maxSalary])
              ->orWhereBetween('salary_max', [$minSalary, $maxSalary]);
        });
    }

    /**
     * Scope for recent jobs
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('posted_at', '>=', now()->subDays($days));
    }

    /**
     * Scope ordered by relevance (featured first, then recent)
     */
    public function scopeByRelevance($query)
    {
        return $query->orderBy('is_featured', 'desc')
            ->orderBy('posted_at', 'desc');
    }
}