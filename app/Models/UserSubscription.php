<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserSubscription extends BaseModel
{
    protected $fillable = [
        'user_id', 'subscription_tier_id', 'billing_cycle', 'amount_paid', 'currency',
        'start_date', 'end_date', 'auto_renew', 'status', 'features_used',
        'jobs_posted_count', 'featured_posts_used', 'resume_views_used'
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'auto_renew' => 'boolean',
        'features_used' => 'array',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription tier
     */
    public function subscriptionTier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date >= now()->toDateString();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->end_date < now()->toDateString();
    }

    /**
     * Days remaining until expiry
     */
    public function daysRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return now()->diffInDays($this->end_date);
    }

    /**
     * Check if user can post a job
     */
    public function canPostJob(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $tier = $this->subscriptionTier;
        if ($tier->job_posts_limit === 0) { // Unlimited
            return true;
        }

        return $this->jobs_posted_count < $tier->job_posts_limit;
    }

    /**
     * Check if user can feature a post
     */
    public function canFeaturePost(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $tier = $this->subscriptionTier;
        if ($tier->featured_posts_limit === 0) { // Unlimited
            return true;
        }

        return $this->featured_posts_used < $tier->featured_posts_limit;
    }

    /**
     * Check if user can view resumes
     */
    public function canViewResumes(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $tier = $this->subscriptionTier;
        if ($tier->resume_views_limit === 0) { // Unlimited
            return true;
        }

        return $this->resume_views_used < $tier->resume_views_limit;
    }

    /**
     * Increment feature usage
     */
    public function incrementUsage(string $feature): bool
    {
        $field = $feature . '_used';
        
        if (!in_array($field, ['jobs_posted_count', 'featured_posts_used', 'resume_views_used'])) {
            return false;
        }

        $this->increment($field);
        return true;
    }

    /**
     * Get subscription progress percentage
     */
    public function getProgressPercentage(): int
    {
        $total = $this->start_date->diffInDays($this->end_date);
        $passed = $this->start_date->diffInDays(now());
        
        if ($total <= 0) {
            return 100;
        }
        
        return min(100, round(($passed / $total) * 100));
    }

    /**
     * Auto-renewal logic
     */
    public function shouldAutoRenew(): bool
    {
        return $this->auto_renew && $this->daysRemaining() <= 7 && $this->status === 'active';
    }
}