{{--
 * Nyasajob - Job Board Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com/nyasajob
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
--}}
@extends('layouts.master')

@php
    $subscriptionTiers ??= collect();
    $currentSubscription ??= null;
    $usageStats ??= [];
@endphp

@section('content')
    @include('common.spacer')
    <div class="main-container">
        <div class="container">
            
            {{-- Page Header --}}
            <div class="row">
                <div class="col-12">
                    <div class="text-center mb-5">
                        <h1 class="h2 mb-3">
                            <i class="fa-solid fa-tags icon-color-1"></i>
                            {{ t('Choose Your Perfect Plan') }}
                        </h1>
                        <p class="lead text-muted">
                            {{ t('Unlock powerful features to accelerate your hiring success') }}
                        </p>
                        
                        {{-- Current Subscription Alert --}}
                        @if($currentSubscription)
                            <div class="alert alert-info">
                                <i class="fa-solid fa-info-circle"></i>
                                {{ t('Current Plan') }}: <strong>{{ $currentSubscription->subscriptionTier->name }}</strong>
                                - {{ t('Expires') }}: {{ $currentSubscription->end_date->format('M j, Y') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Billing Toggle --}}
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <div class="billing-toggle">
                        <span class="billing-label">{{ t('Monthly') }}</span>
                        <label class="switch mx-3">
                            <input type="checkbox" id="billingToggle">
                            <span class="slider round"></span>
                        </label>
                        <span class="billing-label">
                            {{ t('Yearly') }} 
                            <span class="badge bg-success ms-1">{{ t('Save up to 17%') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Pricing Cards --}}
            <div class="row">
                @foreach($subscriptionTiers as $tier)
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card pricing-card h-100 @if($tier->slug === 'professional') border-primary @endif">
                            @if($tier->slug === 'professional')
                                <div class="card-header bg-primary text-white text-center">
                                    <span class="badge bg-warning text-dark">{{ t('Most Popular') }}</span>
                                </div>
                            @endif
                            
                            <div class="card-body d-flex flex-column">
                                <div class="text-center mb-4">
                                    <h3 class="card-title">{{ $tier->name }}</h3>
                                    <p class="text-muted">{{ $tier->description }}</p>
                                    
                                    {{-- Price Display --}}
                                    <div class="price-wrapper">
                                        <div class="monthly-price">
                                            <h2 class="price">
                                                @if($tier->monthly_price > 0)
                                                    ${{ number_format($tier->monthly_price, 0) }}
                                                    <small class="text-muted">/month</small>
                                                @else
                                                    <span class="text-success">{{ t('Free') }}</span>
                                                @endif
                                            </h2>
                                        </div>
                                        <div class="yearly-price" style="display: none;">
                                            <h2 class="price">
                                                @if($tier->yearly_price > 0)
                                                    ${{ number_format($tier->yearly_price, 0) }}
                                                    <small class="text-muted">/year</small>
                                                    @if($tier->yearly_discount > 0)
                                                        <br><small class="text-success">{{ t('Save') }} {{ $tier->yearly_discount }}%</small>
                                                    @endif
                                                @else
                                                    <span class="text-success">{{ t('Free') }}</span>
                                                @endif
                                            </h2>
                                        </div>
                                    </div>
                                </div>

                                {{-- Features List --}}
                                <ul class="list-unstyled flex-grow-1">
                                    @foreach($tier->features as $feature)
                                        <li class="mb-2">
                                            <i class="fa-solid fa-check text-success me-2"></i>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                    
                                    {{-- Limits --}}
                                    <li class="mb-2">
                                        <i class="fa-solid fa-briefcase me-2 text-primary"></i>
                                        {{ $tier->job_posts_limit > 0 ? $tier->job_posts_limit . ' ' . t('job posts') : t('Unlimited job posts') }}
                                    </li>
                                    
                                    @if($tier->featured_posts_limit > 0)
                                        <li class="mb-2">
                                            <i class="fa-solid fa-star me-2 text-warning"></i>
                                            {{ $tier->featured_posts_limit . ' ' . t('featured posts') }}
                                        </li>
                                    @elseif($tier->slug !== 'free-starter')
                                        <li class="mb-2">
                                            <i class="fa-solid fa-star me-2 text-warning"></i>
                                            {{ t('Unlimited featured posts') }}
                                        </li>
                                    @endif
                                    
                                    <li class="mb-2">
                                        <i class="fa-solid fa-file-user me-2 text-info"></i>
                                        {{ $tier->resume_views_limit > 0 ? $tier->resume_views_limit . ' ' . t('resume views') : t('Unlimited resume access') }}
                                    </li>
                                    
                                    {{-- Premium Features --}}
                                    @if($tier->priority_support)
                                        <li class="mb-2">
                                            <i class="fa-solid fa-headset me-2 text-success"></i>
                                            {{ t('Priority Support') }}
                                        </li>
                                    @endif
                                    
                                    @if($tier->analytics_access)
                                        <li class="mb-2">
                                            <i class="fa-solid fa-chart-line me-2 text-primary"></i>
                                            {{ t('Advanced Analytics') }}
                                        </li>
                                    @endif
                                    
                                    @if($tier->api_access)
                                        <li class="mb-2">
                                            <i class="fa-solid fa-code me-2 text-dark"></i>
                                            {{ t('API Access') }}
                                        </li>
                                    @endif
                                    
                                    @if($tier->white_label)
                                        <li class="mb-2">
                                            <i class="fa-solid fa-palette me-2 text-warning"></i>
                                            {{ t('White Label Options') }}
                                        </li>
                                    @endif
                                </ul>

                                {{-- Action Button --}}
                                <div class="mt-auto text-center">
                                    @auth
                                        @if($currentSubscription && $currentSubscription->subscription_tier_id === $tier->id)
                                            <button class="btn btn-outline-success btn-block" disabled>
                                                <i class="fa-solid fa-check me-1"></i>
                                                {{ t('Current Plan') }}
                                            </button>
                                        @else
                                            <a href="{{ route('subscription.upgrade', ['tier' => $tier->id]) }}" 
                                               class="btn @if($tier->slug === 'professional') btn-primary @else btn-outline-primary @endif btn-block">
                                                @if($currentSubscription)
                                                    @if($tier->sort_order > $currentSubscription->subscriptionTier->sort_order)
                                                        {{ t('Upgrade to') }} {{ $tier->name }}
                                                    @else
                                                        {{ t('Switch to') }} {{ $tier->name }}
                                                    @endif
                                                @else
                                                    {{ $tier->monthly_price > 0 ? t('Get Started') : t('Start Free') }}
                                                @endif
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="btn @if($tier->slug === 'professional') btn-primary @else btn-outline-primary @endif btn-block">
                                            {{ t('Get Started') }}
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- FAQ Section --}}
            <div class="row mt-5">
                <div class="col-12">
                    <div class="text-center mb-4">
                        <h3>{{ t('Frequently Asked Questions') }}</h3>
                    </div>
                    
                    <div class="accordion" id="pricingFAQ">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    {{ t('Can I change my plan at any time?') }}
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#pricingFAQ">
                                <div class="accordion-body">
                                    {{ t('Yes, you can upgrade or downgrade your subscription plan at any time. Changes take effect immediately.') }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    {{ t('What payment methods do you accept?') }}
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                                <div class="accordion-body">
                                    {{ t('We accept all major credit cards, PayPal, and various other payment methods depending on your location.') }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    {{ t('Is there a free trial available?') }}
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                                <div class="accordion-body">
                                    {{ t('Yes, our Free Starter plan lets you try the platform with basic features. You can upgrade at any time.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_styles')
    <style>
        .pricing-card {
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .pricing-card:hover {
            border-color: #007bff;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,123,255,0.1);
        }
        
        .pricing-card.border-primary {
            border-color: #007bff !important;
            box-shadow: 0 5px 15px rgba(0,123,255,0.2);
        }
        
        .price {
            font-weight: 700;
            color: #007bff;
        }
        
        .billing-toggle {
            display: inline-flex;
            align-items: center;
        }
        
        .billing-label {
            font-weight: 500;
            color: #495057;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }
        
        input:checked + .slider {
            background-color: #007bff;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .slider.round {
            border-radius: 34px;
        }
        
        .slider.round:before {
            border-radius: 50%;
        }
        
        .btn-block {
            width: 100%;
        }
    </style>
@endsection

@section('after_scripts')
    <script>
        document.getElementById('billingToggle').addEventListener('change', function() {
            const isYearly = this.checked;
            const monthlyPrices = document.querySelectorAll('.monthly-price');
            const yearlyPrices = document.querySelectorAll('.yearly-price');
            
            monthlyPrices.forEach(el => {
                el.style.display = isYearly ? 'none' : 'block';
            });
            
            yearlyPrices.forEach(el => {
                el.style.display = isYearly ? 'block' : 'none';
            });
        });
    </script>
@endsection