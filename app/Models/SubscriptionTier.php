<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([ActiveScope::class])]
class SubscriptionTier extends BaseModel
{
    protected $fillable = [
        'name', 'slug', 'description', 'features', 'monthly_price', 'yearly_price',
        'job_posts_limit', 'featured_posts_limit', 'resume_views_limit', 
        'priority_support', 'analytics_access', 'api_access', 'white_label',
        'active', 'sort_order'
    ];

    protected $casts = [
        'features' => 'array',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'priority_support' => 'boolean',
        'analytics_access' => 'boolean',
        'api_access' => 'boolean', 
        'white_label' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Get the subscriptions for this tier
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Get the active subscriptions for this tier
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class)->where('status', 'active');
    }

    /**
     * Check if tier is unlimited for a feature
     */
    public function isUnlimited(string $feature): bool
    {
        return $this->getAttribute($feature . '_limit') === 0;
    }

    /**
     * Get discount percentage for yearly billing
     */
    public function getYearlyDiscountAttribute(): int
    {
        if ($this->monthly_price <= 0 || $this->yearly_price <= 0) {
            return 0;
        }
        
        $monthlyYearly = $this->monthly_price * 12;
        return round((($monthlyYearly - $this->yearly_price) / $monthlyYearly) * 100);
    }

    /**
     * Check if this is a free tier
     */
    public function isFree(): bool
    {
        return $this->monthly_price == 0 && $this->yearly_price == 0;
    }

    /**
     * Get formatted price for display
     */
    public function getFormattedPrice(string $cycle = 'monthly'): string
    {
        $price = $cycle === 'yearly' ? $this->yearly_price : $this->monthly_price;
        
        if ($price == 0) {
            return 'Free';
        }
        
        return '$' . number_format($price, 2);
    }
}