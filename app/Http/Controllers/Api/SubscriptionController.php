<?php

namespace App\Http\Controllers\Api;

use App\Models\SubscriptionTier;
use App\Models\UserSubscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends BaseController
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display subscription tiers
     */
    public function index()
    {
        $tiers = SubscriptionTier::orderBy('sort_order')->get();
        $currentSubscription = null;
        $usageStats = [];

        if (Auth::check()) {
            $currentSubscription = $this->subscriptionService->getCurrentSubscription(Auth::user());
            $usageStats = $this->subscriptionService->getUsageStats(Auth::user());
        }

        return response()->json([
            'tiers' => $tiers,
            'current_subscription' => $currentSubscription,
            'usage_stats' => $usageStats
        ]);
    }

    /**
     * Get analytics data for subscriptions
     */
    public function analytics()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $analytics = $this->subscriptionService->getAnalytics(Auth::user());

        return response()->json([
            'analytics' => $analytics
        ]);
    }

    /**
     * Create a new subscription
     */
    public function store(Request $request)
    {
        $request->validate([
            'tier_id' => 'required|exists:subscription_tiers,id',
            'billing_cycle' => 'required|in:monthly,yearly'
        ]);

        $tier = SubscriptionTier::findOrFail($request->tier_id);
        $user = Auth::user();

        $subscription = $this->subscriptionService->createSubscription(
            $user, 
            $tier, 
            $request->billing_cycle,
            $request->all()
        );

        return response()->json([
            'success' => true,
            'subscription' => $subscription->load('subscriptionTier'),
            'message' => 'Subscription created successfully!'
        ]);
    }

    /**
     * Get current subscription details
     */
    public function current()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $subscription = $this->subscriptionService->getCurrentSubscription(Auth::user());
        $usageStats = $this->subscriptionService->getUsageStats(Auth::user());

        return response()->json([
            'subscription' => $subscription ? $subscription->load('subscriptionTier') : null,
            'usage_stats' => $usageStats,
            'can_upgrade' => !$subscription || $subscription->subscriptionTier->sort_order < 4
        ]);
    }

    /**
     * Update current subscription
     */
    public function update(Request $request)
    {
        $request->validate([
            'tier_id' => 'required|exists:subscription_tiers,id',
            'billing_cycle' => 'required|in:monthly,yearly'
        ]);

        $tier = SubscriptionTier::findOrFail($request->tier_id);
        $user = Auth::user();

        $subscription = $this->subscriptionService->updateSubscription(
            $user, 
            $tier, 
            $request->billing_cycle
        );

        return response()->json([
            'success' => true,
            'subscription' => $subscription->load('subscriptionTier'),
            'message' => 'Subscription updated successfully!'
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel()
    {
        $user = Auth::user();
        $this->subscriptionService->cancelActiveSubscriptions($user);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully!'
        ]);
    }

    /**
     * Get usage statistics
     */
    public function usageStats()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $usageStats = $this->subscriptionService->getUsageStats(Auth::user());

        return response()->json([
            'usage_stats' => $usageStats
        ]);
    }

    /**
     * Check if user can access feature
     */
    public function checkFeature(Request $request)
    {
        $request->validate([
            'feature' => 'required|string'
        ]);

        $canAccess = $this->subscriptionService->canPerformAction(
            Auth::user(), 
            $request->feature
        );

        return response()->json([
            'can_access' => $canAccess,
            'feature' => $request->feature
        ]);
    }
}