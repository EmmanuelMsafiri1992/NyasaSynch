<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtsJobPosting extends Model
{
    use HasFactory;

    protected $fillable = [
        'ats_connection_id',
        'external_job_id',
        'title',
        'description',
        'department',
        'location',
        'employment_type',
        'experience_level',
        'salary_min',
        'salary_max',
        'salary_currency',
        'requirements',
        'benefits',
        'hiring_manager',
        'recruiter',
        'status',
        'posted_at',
        'expires_at',
        'custom_fields',
        'applications_count',
        'last_updated_at'
    ];

    protected $casts = [
        'requirements' => 'array',
        'benefits' => 'array',
        'custom_fields' => 'array',
        'posted_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'applications_count' => 'integer'
    ];

    public function atsConnection(): BelongsTo
    {
        return $this->belongsTo(AtsConnection::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AtsApplication::class);
    }

    public function getSalaryRangeAttribute(): ?string
    {
        if (!$this->salary_min && !$this->salary_max) {
            return null;
        }

        $currency = $this->salary_currency ?? 'USD';
        $symbol = match($currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            default => $currency . ' '
        };

        if ($this->salary_min && $this->salary_max) {
            return $symbol . number_format($this->salary_min) . ' - ' . $symbol . number_format($this->salary_max);
        } elseif ($this->salary_min) {
            return $symbol . number_format($this->salary_min) . '+';
        } else {
            return 'Up to ' . $symbol . number_format($this->salary_max);
        }
    }

    public function getFormattedLocationAttribute(): string
    {
        return $this->location;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getDaysOldAttribute(): int
    {
        return $this->posted_at ? $this->posted_at->diffInDays(now()) : 0;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeByLocation($query, string $location)
    {
        return $query->where('location', 'like', '%' . $location . '%');
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByExperienceLevel($query, string $level)
    {
        return $query->where('experience_level', $level);
    }

    public function scopeByEmploymentType($query, string $type)
    {
        return $query->where('employment_type', $type);
    }

    public function scopeSalaryRange($query, ?float $min = null, ?float $max = null)
    {
        if ($min !== null) {
            $query->where('salary_max', '>=', $min);
        }

        if ($max !== null) {
            $query->where('salary_min', '<=', $max);
        }

        return $query;
    }

    public function scopeSearch($query, string $keywords)
    {
        return $query->where(function($q) use ($keywords) {
            $q->where('title', 'like', '%' . $keywords . '%')
              ->orWhere('description', 'like', '%' . $keywords . '%')
              ->orWhere('department', 'like', '%' . $keywords . '%');
        });
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('posted_at', '>=', now()->subDays($days));
    }

    public function updateApplicationsCount(): void
    {
        $this->update([
            'applications_count' => $this->applications()->count()
        ]);
    }
}