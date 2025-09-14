<?php

namespace App\Services;

use App\Models\User;
use App\Models\SubscriptionTier;
use App\Models\UserSubscription;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Create a new subscription for a user
     */
    public function createSubscription(User $user, SubscriptionTier $tier, string $billingCycle = 'monthly', array $paymentData = []): UserSubscription
    {
        $price = $billingCycle === 'yearly' ? $tier->yearly_price : $tier->monthly_price;
        $duration = $billingCycle === 'yearly' ? 365 : 30;
        
        // Cancel any existing active subscriptions
        $this->cancelActiveSubscriptions($user);
        
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_tier_id' => $tier->id,
            'billing_cycle' => $billingCycle,
            'amount_paid' => $price,
            'currency' => $paymentData['currency'] ?? 'USD',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays($duration)->toDateString(),
            'auto_renew' => $paymentData['auto_renew'] ?? true,
            'status' => $price > 0 ? 'pending' : 'active',
        ]);

        return $subscription;
    }

    /**
     * Cancel active subscriptions for a user
     */
    public function cancelActiveSubscriptions(User $user): void
    {
        UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled', 'auto_renew' => false]);
    }

    /**
     * Activate a subscription (after successful payment)
     */
    public function activateSubscription(UserSubscription $subscription): bool
    {
        return $subscription->update(['status' => 'active']);
    }

    /**
     * Get user's current active subscription
     */
    public function getCurrentSubscription(User $user): ?UserSubscription
    {
        return UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->with('subscriptionTier')
            ->first();
    }

    /**
     * Check if user can perform an action based on subscription
     */
    public function canPerformAction(User $user, string $action): bool
    {
        $subscription = $this->getCurrentSubscription($user);
        
        if (!$subscription) {
            // No active subscription - check if action is allowed for free users
            return $this->isFreeActionAllowed($action);
        }

        return match($action) {
            'post_job' => $subscription->canPostJob(),
            'feature_post' => $subscription->canFeaturePost(),
            'view_resume' => $subscription->canViewResumes(),
            'access_analytics' => $subscription->subscriptionTier->analytics_access,
            'priority_support' => $subscription->subscriptionTier->priority_support,
            'api_access' => $subscription->subscriptionTier->api_access,
            default => false
        };
    }

    /**
     * Track feature usage
     */
    public function trackUsage(User $user, string $feature): bool
    {
        $subscription = $this->getCurrentSubscription($user);
        
        if (!$subscription) {
            return false;
        }

        return $subscription->incrementUsage($feature);
    }

    /**
     * Get subscription usage statistics
     */
    public function getUsageStats(User $user): array
    {
        $subscription = $this->getCurrentSubscription($user);
        
        if (!$subscription) {
            return [];
        }

        $tier = $subscription->subscriptionTier;
        
        return [
            'jobs_posted' => [
                'used' => $subscription->jobs_posted_count,
                'limit' => $tier->job_posts_limit,
                'unlimited' => $tier->job_posts_limit === 0
            ],
            'featured_posts' => [
                'used' => $subscription->featured_posts_used,
                'limit' => $tier->featured_posts_limit,
                'unlimited' => $tier->featured_posts_limit === 0
            ],
            'resume_views' => [
                'used' => $subscription->resume_views_used,
                'limit' => $tier->resume_views_limit,
                'unlimited' => $tier->resume_views_limit === 0
            ]
        ];
    }

    /**
     * Get renewal reminders for expiring subscriptions
     */
    public function getRenewalReminders(): array
    {
        return UserSubscription::with(['user', 'subscriptionTier'])
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->whereBetween('end_date', [
                now()->toDateString(),
                now()->addDays(7)->toDateString()
            ])
            ->get()
            ->toArray();
    }

    /**
     * Process expired subscriptions
     */
    public function processExpiredSubscriptions(): int
    {
        $count = UserSubscription::where('status', 'active')
            ->where('end_date', '<', now()->toDateString())
            ->update(['status' => 'expired']);

        return $count;
    }

    /**
     * Upgrade subscription
     */
    public function upgradeSubscription(User $user, SubscriptionTier $newTier, string $billingCycle = 'monthly'): UserSubscription
    {
        $currentSubscription = $this->getCurrentSubscription($user);
        
        // Calculate prorated refund/credit if applicable
        if ($currentSubscription) {
            $currentSubscription->update(['status' => 'cancelled']);
        }
        
        return $this->createSubscription($user, $newTier, $billingCycle);
    }

    /**
     * Get subscription analytics for admin
     */
    public function getSubscriptionAnalytics(): array
    {
        $stats = DB::table('user_subscriptions as us')
            ->join('subscription_tiers as st', 'us.subscription_tier_id', '=', 'st.id')
            ->selectRaw('
                st.name as tier_name,
                COUNT(*) as total_subscriptions,
                COUNT(CASE WHEN us.status = "active" THEN 1 END) as active_subscriptions,
                SUM(us.amount_paid) as total_revenue,
                AVG(us.amount_paid) as avg_revenue_per_user
            ')
            ->groupBy('st.id', 'st.name')
            ->get();

        return $stats->toArray();
    }

    /**
     * Check if action is allowed for free users
     */
    private function isFreeActionAllowed(string $action): bool
    {
        $freeActions = ['post_job', 'view_resume']; // Limited free actions
        return in_array($action, $freeActions);
    }
}