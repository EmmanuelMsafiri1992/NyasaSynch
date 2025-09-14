<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtsCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'ats_connection_id',
        'external_candidate_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'linkedin_url',
        'portfolio_url',
        'skills',
        'education',
        'experience',
        'current_title',
        'current_company',
        'desired_salary',
        'availability',
        'open_to_remote',
        'custom_fields',
        'last_updated_at'
    ];

    protected $casts = [
        'skills' => 'array',
        'education' => 'array',
        'experience' => 'array',
        'custom_fields' => 'array',
        'desired_salary' => 'decimal:2',
        'open_to_remote' => 'boolean',
        'last_updated_at' => 'datetime'
    ];

    public function atsConnection(): BelongsTo
    {
        return $this->belongsTo(AtsConnection::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AtsApplication::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getFormattedSalaryAttribute(): ?string
    {
        if (!$this->desired_salary) {
            return null;
        }

        return '$' . number_format($this->desired_salary);
    }

    public function getYearsOfExperienceAttribute(): int
    {
        if (!$this->experience || !is_array($this->experience)) {
            return 0;
        }

        $totalMonths = 0;

        foreach ($this->experience as $job) {
            if (isset($job['start_date']) && isset($job['end_date'])) {
                $start = \Carbon\Carbon::parse($job['start_date']);
                $end = $job['end_date'] === 'current' ? now() : \Carbon\Carbon::parse($job['end_date']);
                $totalMonths += $start->diffInMonths($end);
            }
        }

        return round($totalMonths / 12, 1);
    }

    public function getLatestEducationAttribute(): ?array
    {
        if (!$this->education || !is_array($this->education)) {
            return null;
        }

        return collect($this->education)->sortByDesc('graduation_year')->first();
    }

    public function getCurrentPositionAttribute(): ?string
    {
        if ($this->current_title && $this->current_company) {
            return $this->current_title . ' at ' . $this->current_company;
        }

        return $this->current_title ?? $this->current_company;
    }

    public function getSkillsListAttribute(): string
    {
        if (!$this->skills || !is_array($this->skills)) {
            return '';
        }

        return implode(', ', $this->skills);
    }

    public function scopeBySkills($query, array $skills)
    {
        return $query->where(function($q) use ($skills) {
            foreach ($skills as $skill) {
                $q->orWhereJsonContains('skills', $skill);
            }
        });
    }

    public function scopeByExperienceLevel($query, int $minYears, int $maxYears = null)
    {
        // This would need to be calculated at query time for better performance
        // For now, we'll use a simple approach
        return $query->whereHas('experience', function($q) use ($minYears, $maxYears) {
            // Implementation depends on how experience is stored
        });
    }

    public function scopeByLocation($query, string $location)
    {
        return $query->where('address', 'like', '%' . $location . '%');
    }

    public function scopeOpenToRemote($query)
    {
        return $query->where('open_to_remote', true);
    }

    public function scopeByAvailability($query, string $availability)
    {
        return $query->where('availability', $availability);
    }

    public function scopeBySalaryRange($query, ?float $min = null, ?float $max = null)
    {
        if ($min !== null) {
            $query->where('desired_salary', '>=', $min);
        }

        if ($max !== null) {
            $query->where('desired_salary', '<=', $max);
        }

        return $query;
    }

    public function hasSkill(string $skill): bool
    {
        if (!$this->skills || !is_array($this->skills)) {
            return false;
        }

        return in_array(strtolower($skill), array_map('strtolower', $this->skills));
    }

    public function getApplicationsCountAttribute(): int
    {
        return $this->applications()->count();
    }

    public function getLatestApplicationAttribute(): ?AtsApplication
    {
        return $this->applications()->latest('applied_at')->first();
    }
}