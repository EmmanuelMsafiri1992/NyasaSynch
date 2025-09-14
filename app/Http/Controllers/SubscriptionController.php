<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionTier;
use App\Models\UserSubscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->middleware('auth');
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
     * Show user's current subscription details
     */
    public function show()
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
     * Cancel subscription
     */
    public function destroy()
    {
        $user = Auth::user();
        $this->subscriptionService->cancelActiveSubscriptions($user);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully!'
        ]);
    }

    /**
     * Check if user can perform action
     */
    public function checkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string'
        ]);

        $canPerform = $this->subscriptionService->canPerformAction(
            Auth::user(), 
            $request->action
        );

        return response()->json([
            'can_perform' => $canPerform,
            'action' => $request->action
        ]);
    }

    /**
     * Track feature usage
     */
    public function trackUsage(Request $request)
    {
        $request->validate([
            'feature' => 'required|string'
        ]);

        $tracked = $this->subscriptionService->trackUsage(
            Auth::user(), 
            $request->feature
        );

        return response()->json([
            'success' => $tracked,
            'feature' => $request->feature
        ]);
    }

    /**
     * Get pricing comparison view
     */
    public function pricing()
    {
        $tiers = SubscriptionTier::orderBy('sort_order')->get();
        
        return response()->json([
            'pricing_tiers' => $tiers->map(function ($tier) {
                return [
                    'id' => $tier->id,
                    'name' => $tier->name,
                    'slug' => $tier->slug,
                    'description' => $tier->description,
                    'features' => $tier->features,
                    'monthly_price' => $tier->monthly_price,
                    'yearly_price' => $tier->yearly_price,
                    'yearly_discount' => $tier->yearly_discount,
                    'formatted_monthly' => $tier->getFormattedPrice('monthly'),
                    'formatted_yearly' => $tier->getFormattedPrice('yearly'),
                    'is_free' => $tier->isFree(),
                    'limits' => [
                        'job_posts' => $tier->job_posts_limit ?: 'Unlimited',
                        'featured_posts' => $tier->featured_posts_limit ?: 'Unlimited', 
                        'resume_views' => $tier->resume_views_limit ?: 'Unlimited'
                    ],
                    'premium_features' => [
                        'priority_support' => $tier->priority_support,
                        'analytics_access' => $tier->analytics_access,
                        'api_access' => $tier->api_access,
                        'white_label' => $tier->white_label
                    ]
                ];
            })
        ]);
    }
}