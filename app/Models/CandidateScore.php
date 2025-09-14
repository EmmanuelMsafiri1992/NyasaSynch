<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateScore extends Model
{
    protected $fillable = [
        'user_id',
        'profile_completion_score',
        'activity_score',
        'verification_score',
        'response_rate_score',
        'success_rate_score',
        'total_score',
        'profile_completion_percentage',
        'days_active_last_30',
        'applications_sent_last_30',
        'messages_responded_24h',
        'total_messages_received',
        'interviews_attended',
        'jobs_hired_for',
        'email_verified',
        'phone_verified',
        'linkedin_verified',
        'education_verified',
        'employment_verified',
        'score_history',
        'last_calculated_at'
    ];

    protected $casts = [
        'profile_completion_score' => 'decimal:2',
        'activity_score' => 'decimal:2',
        'verification_score' => 'decimal:2',
        'response_rate_score' => 'decimal:2',
        'success_rate_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'email_verified' => 'boolean',
        'phone_verified' => 'boolean',
        'linkedin_verified' => 'boolean',
        'education_verified' => 'boolean',
        'employment_verified' => 'boolean',
        'score_history' => 'array',
        'last_calculated_at' => 'datetime'
    ];

    // Score weights (should total 100%)
    const WEIGHTS = [
        'profile_completion' => 0.30, // 30%
        'activity' => 0.20,           // 20%
        'verification' => 0.25,       // 25%
        'response_rate' => 0.15,      // 15%
        'success_rate' => 0.10        // 10%
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate profile completion score based on user data
     */
    public function calculateProfileCompletionScore(): float
    {
        $user = $this->user;
        if (!$user) return 0;

        $checks = [
            'basic_info' => !empty($user->name) && !empty($user->email) ? 20 : 0,
            'phone' => !empty($user->phone) ? 10 : 0,
            'location' => !empty($user->city_id) ? 10 : 0,
            'photo' => !empty($user->photo) ? 15 : 0,
            'description' => !empty($user->description) && strlen($user->description) > 100 ? 15 : 0,
            'experience' => $user->resumes()->count() > 0 ? 15 : 0,
            'skills' => !empty($user->tags) && count(explode(',', $user->tags)) >= 3 ? 10 : 0,
            'social_links' => !empty($user->website) ? 5 : 0
        ];

        $score = array_sum($checks);
        $this->profile_completion_percentage = $score;
        
        return min(100, $score);
    }

    /**
     * Calculate activity score based on recent user engagement
     */
    public function calculateActivityScore(): float
    {
        $user = $this->user;
        if (!$user) return 0;

        // Get activity from last 30 days
        $daysActive = UserActivityLog::where('user_id', $user->id)
            ->where('activity_date', '>=', now()->subDays(30))
            ->distinct('activity_date')
            ->count();

        $applications = UserActivityLog::where('user_id', $user->id)
            ->where('activity_type', 'application_sent')
            ->where('activity_date', '>=', now()->subDays(30))
            ->count();

        $logins = UserActivityLog::where('user_id', $user->id)
            ->where('activity_type', 'login')
            ->where('activity_date', '>=', now()->subDays(30))
            ->count();

        // Update tracking metrics
        $this->days_active_last_30 = $daysActive;
        $this->applications_sent_last_30 = $applications;

        // Score calculation (out of 100)
        $activityScore = min(100, ($daysActive * 2) + ($applications * 3) + ($logins * 0.5));
        
        return $activityScore;
    }

    /**
     * Calculate verification score based on verified credentials
     */
    public function calculateVerificationScore(): float
    {
        $verificationChecks = [
            'email_verified' => $this->email_verified ? 30 : 0,
            'phone_verified' => $this->phone_verified ? 20 : 0,
            'linkedin_verified' => $this->linkedin_verified ? 20 : 0,
            'education_verified' => $this->education_verified ? 15 : 0,
            'employment_verified' => $this->employment_verified ? 15 : 0
        ];

        return array_sum($verificationChecks);
    }

    /**
     * Calculate response rate score based on employer communication
     */
    public function calculateResponseRateScore(): float
    {
        if ($this->total_messages_received == 0) {
            return 50; // Neutral score for no messages
        }

        $responseRate = ($this->messages_responded_24h / $this->total_messages_received) * 100;
        return min(100, $responseRate);
    }

    /**
     * Calculate success rate score based on interview/hire ratio
     */
    public function calculateSuccessRateScore(): float
    {
        if ($this->interviews_attended == 0) {
            return 50; // Neutral score for no interviews
        }

        $successRate = ($this->jobs_hired_for / $this->interviews_attended) * 100;
        return min(100, $successRate * 2); // Multiply by 2 since 50% success rate = 100 points
    }

    /**
     * Calculate and update total weighted score
     */
    public function calculateTotalScore(): void
    {
        $profileScore = $this->calculateProfileCompletionScore();
        $activityScore = $this->calculateActivityScore();
        $verificationScore = $this->calculateVerificationScore();
        $responseScore = $this->calculateResponseRateScore();
        $successScore = $this->calculateSuccessRateScore();

        // Update individual scores
        $this->profile_completion_score = $profileScore;
        $this->activity_score = $activityScore;
        $this->verification_score = $verificationScore;
        $this->response_rate_score = $responseScore;
        $this->success_rate_score = $successScore;

        // Calculate weighted total
        $totalScore = 
            ($profileScore * self::WEIGHTS['profile_completion']) +
            ($activityScore * self::WEIGHTS['activity']) +
            ($verificationScore * self::WEIGHTS['verification']) +
            ($responseScore * self::WEIGHTS['response_rate']) +
            ($successScore * self::WEIGHTS['success_rate']);

        $this->total_score = round($totalScore, 2);
        
        // Store score history
        $history = $this->score_history ?? [];
        $history[] = [
            'date' => now()->toDateString(),
            'total_score' => $this->total_score,
            'components' => [
                'profile_completion' => $profileScore,
                'activity' => $activityScore,
                'verification' => $verificationScore,
                'response_rate' => $responseScore,
                'success_rate' => $successScore
            ]
        ];
        
        // Keep only last 30 entries
        if (count($history) > 30) {
            $history = array_slice($history, -30);
        }
        
        $this->score_history = $history;
        $this->last_calculated_at = now();
    }

    /**
     * Get score tier (A, B, C, D, F)
     */
    public function getScoreTier(): string
    {
        if ($this->total_score >= 90) return 'A';
        if ($this->total_score >= 80) return 'B';
        if ($this->total_score >= 70) return 'C';
        if ($this->total_score >= 60) return 'D';
        return 'F';
    }

    /**
     * Get score tier color for UI
     */
    public function getScoreTierColor(): string
    {
        return match($this->getScoreTier()) {
            'A' => '#28a745', // Green
            'B' => '#17a2b8', // Blue
            'C' => '#ffc107', // Yellow
            'D' => '#fd7e14', // Orange
            'F' => '#dc3545'  // Red
        };
    }

    /**
     * Scope to get top candidates
     */
    public function scopeTopCandidates($query, $limit = 100)
    {
        return $query->orderByDesc('total_score')->limit($limit);
    }

    /**
     * Scope to get candidates above certain score
     */
    public function scopeAboveScore($query, $minScore = 70)
    {
        return $query->where('total_score', '>=', $minScore);
    }
}