<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Web\Public\FrontController;
use App\Models\SubscriptionTier;
use App\Models\UserSubscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class SubscriptionController extends FrontController
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display subscription pricing page
     */
    public function pricing()
    {
        $data = [];
        
        // Get all active subscription tiers
        $data['subscriptionTiers'] = SubscriptionTier::orderBy('sort_order')->get();
        
        // Get current user subscription if authenticated
        if (Auth::check()) {
            $data['currentSubscription'] = $this->subscriptionService->getCurrentSubscription(Auth::user());
            $data['usageStats'] = $this->subscriptionService->getUsageStats(Auth::user());
        }

        // SEO
        $title = t('Choose Your Subscription Plan');
        $description = t('Select the perfect subscription plan for your hiring needs. Compare features and pricing.');
        
        MetaTag::set('title', $title);
        MetaTag::set('description', $description);

        return appView('subscription.pricing', $data);
    }

    /**
     * Display user's subscription management page
     */
    public function index()
    {
        if (!Auth::check()) {
            abort(401);
        }

        $data = [];
        $data['authUser'] = Auth::user();
        
        // Get current subscription
        $data['currentSubscription'] = $this->subscriptionService->getCurrentSubscription(Auth::user());
        $data['usageStats'] = $this->subscriptionService->getUsageStats(Auth::user());
        
        // Get available tiers for upgrade/downgrade
        $data['availableTiers'] = SubscriptionTier::orderBy('sort_order')->get();

        // SEO
        $title = t('My Subscription');
        MetaTag::set('title', $title);

        return appView('account.subscription-management', $data);
    }

    /**
     * Show subscription selection for upgrade
     */
    public function upgrade()
    {
        if (!Auth::check()) {
            abort(401);
        }

        $data = [];
        $data['authUser'] = Auth::user();

        // Extra safety check
        if (!$data['authUser']) {
            abort(401, 'Authentication required');
        }
        
        // Get packages (subscription tiers) for payment form
        $data['packages'] = SubscriptionTier::orderBy('sort_order')->get();
        $data['paymentMethods'] = \App\Models\PaymentMethod::where('active', 1)->get();
        
        // Current subscription info
        $data['currentSubscription'] = $this->subscriptionService->getCurrentSubscription(Auth::user());
        
        // Package type for payment flow
        $data['packageType'] = 'subscription';

        // SEO
        $title = t('Upgrade Subscription');
        MetaTag::set('title', $title);

        return appView('account.subscription', $data);
    }

    /**
     * Process subscription purchase
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            abort(401);
        }

        $request->validate([
            'package_id' => 'required|exists:subscription_tiers,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        $tier = SubscriptionTier::findOrFail($request->package_id);
        $billingCycle = $request->input('billing_cycle', 'monthly');
        
        // Create subscription
        $subscription = $this->subscriptionService->createSubscription(
            Auth::user(),
            $tier,
            $billingCycle,
            [
                'payment_method_id' => $request->payment_method_id,
                'auto_renew' => $request->boolean('auto_renew', true)
            ]
        );

        if ($tier->monthly_price > 0 || $tier->yearly_price > 0) {
            // Redirect to payment processing
            flash(t('Please complete your payment to activate your subscription'))->info();
            return redirect()->route('subscription.payment', $subscription->id);
        } else {
            // Free tier - activate immediately
            $this->subscriptionService->activateSubscription($subscription);
            flash(t('Your free subscription has been activated!'))->success();
            return redirect()->route('account.subscription');
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel()
    {
        if (!Auth::check()) {
            abort(401);
        }

        $this->subscriptionService->cancelActiveSubscriptions(Auth::user());
        
        flash(t('Your subscription has been cancelled successfully'))->success();
        return redirect()->route('account.subscription');
    }

    /**
     * Check feature availability (AJAX)
     */
    public function checkFeature(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['can_perform' => false], 401);
        }

        $request->validate([
            'feature' => 'required|string'
        ]);

        $canPerform = $this->subscriptionService->canPerformAction(
            Auth::user(),
            $request->feature
        );

        return response()->json([
            'can_perform' => $canPerform,
            'feature' => $request->feature,
            'usage_stats' => $this->subscriptionService->getUsageStats(Auth::user())
        ]);
    }
}