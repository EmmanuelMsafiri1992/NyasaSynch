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
    $currentSubscription ??= null;
    $usageStats ??= [];
    $availableTiers ??= collect();
@endphp

@section('content')
    @include('common.spacer')
    <div class="main-container">
        <div class="container">
            <div class="row">
                
                <div class="col-md-3 page-sidebar">
                    @include('account.inc.sidebar')
                </div>
                
                <div class="col-md-9 page-content">
                    
                    @include('flash::message')
                    
                    @if (isset($errors) && $errors->any())
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
                            <h5><strong>{{ t('validation_errors_title') }}</strong></h5>
                            <ul class="list list-check">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <div class="inner-box">
                        
                        <h2 class="title-2">
                            <strong>
                                <i class="fa-solid fa-tags"></i> {{ t('My Subscription') }}
                            </strong>
                        </h2>
                        
                        @if($currentSubscription)
                            {{-- Current Subscription Details --}}
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">
                                        <i class="fa-solid fa-crown me-2"></i>
                                        {{ $currentSubscription->subscriptionTier->name }}
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <p class="card-text">
                                                {{ $currentSubscription->subscriptionTier->description }}
                                            </p>
                                            
                                            <div class="subscription-details">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <strong>{{ t('Status') }}:</strong>
                                                        <span class="badge bg-{{ $currentSubscription->isActive() ? 'success' : 'danger' }}">
                                                            {{ ucfirst($currentSubscription->status) }}
                                                        </span>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <strong>{{ t('Billing Cycle') }}:</strong>
                                                        {{ ucfirst($currentSubscription->billing_cycle) }}
                                                    </div>
                                                    <div class="col-sm-6 mt-2">
                                                        <strong>{{ t('Start Date') }}:</strong>
                                                        {{ $currentSubscription->start_date->format('M j, Y') }}
                                                    </div>
                                                    <div class="col-sm-6 mt-2">
                                                        <strong>{{ t('End Date') }}:</strong>
                                                        {{ $currentSubscription->end_date->format('M j, Y') }}
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            @if($currentSubscription->isActive())
                                                <div class="mt-3">
                                                    <strong>{{ t('Days Remaining') }}:</strong>
                                                    <span class="text-primary">{{ $currentSubscription->daysRemaining() }} {{ t('days') }}</span>
                                                    
                                                    {{-- Progress Bar --}}
                                                    <div class="progress mt-2">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: {{ $currentSubscription->getProgressPercentage() }}%"
                                                             aria-valuenow="{{ $currentSubscription->getProgressPercentage() }}" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <div class="col-md-4 text-end">
                                            <div class="subscription-price">
                                                <h3 class="text-primary">
                                                    ${{ number_format($currentSubscription->amount_paid, 2) }}
                                                </h3>
                                                <small class="text-muted">
                                                    per {{ $currentSubscription->billing_cycle === 'yearly' ? 'year' : 'month' }}
                                                </small>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <a href="{{ url('account/subscription') }}" class="btn btn-outline-primary">
                                                    <i class="fa-solid fa-arrow-up me-1"></i>
                                                    {{ t('Upgrade Plan') }}
                                                </a>
                                                
                                                @if(!$currentSubscription->subscriptionTier->isFree())
                                                    <form method="POST" action="{{ route('subscription.cancel') }}" class="d-inline mt-2">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                                onclick="return confirm('{{ t('Are you sure you want to cancel your subscription?') }}')">
                                                            <i class="fa-solid fa-times me-1"></i>
                                                            {{ t('Cancel Subscription') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Usage Statistics --}}
                            @if(!empty($usageStats))
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fa-solid fa-chart-bar me-2"></i>
                                            {{ t('Usage Statistics') }}
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($usageStats as $feature => $stats)
                                                <div class="col-md-4 mb-3">
                                                    <div class="usage-stat">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span class="usage-label">
                                                                @if($feature === 'jobs_posted')
                                                                    <i class="fa-solid fa-briefcase me-1"></i>
                                                                    {{ t('Job Posts') }}
                                                                @elseif($feature === 'featured_posts')
                                                                    <i class="fa-solid fa-star me-1"></i>
                                                                    {{ t('Featured Posts') }}
                                                                @elseif($feature === 'resume_views')
                                                                    <i class="fa-solid fa-file-user me-1"></i>
                                                                    {{ t('Resume Views') }}
                                                                @endif
                                                            </span>
                                                            <span class="usage-count">
                                                                {{ $stats['used'] }}/{{ $stats['unlimited'] ? '∞' : $stats['limit'] }}
                                                            </span>
                                                        </div>
                                                        
                                                        @if(!$stats['unlimited'])
                                                            @php
                                                                $percentage = $stats['limit'] > 0 ? ($stats['used'] / $stats['limit']) * 100 : 0;
                                                                $barClass = $percentage > 80 ? 'bg-danger' : ($percentage > 60 ? 'bg-warning' : 'bg-success');
                                                            @endphp
                                                            <div class="progress progress-sm">
                                                                <div class="progress-bar {{ $barClass }}" role="progressbar" 
                                                                     style="width: {{ min(100, $percentage) }}%"
                                                                     aria-valuenow="{{ $percentage }}" 
                                                                     aria-valuemin="0" aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="text-success small">
                                                                <i class="fa-solid fa-infinity me-1"></i>
                                                                {{ t('Unlimited') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                        @else
                            {{-- No Active Subscription --}}
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <i class="fa-solid fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                                    <h4>{{ t('No Active Subscription') }}</h4>
                                    <p class="text-muted mb-4">
                                        {{ t('You don\'t have an active subscription. Choose a plan to unlock powerful features.') }}
                                    </p>
                                    <a href="{{ url('account/subscription') }}" class="btn btn-primary btn-lg">
                                        <i class="fa-solid fa-rocket me-2"></i>
                                        {{ t('Choose Your Plan') }}
                                    </a>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Available Plans Preview --}}
                        <div class="mt-5">
                            <h4>{{ t('Available Plans') }}</h4>
                            <div class="row">
                                @foreach($availableTiers->take(3) as $tier)
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100 
                                            @if($currentSubscription && $currentSubscription->subscription_tier_id === $tier->id) 
                                                border-primary 
                                            @else 
                                                border-light 
                                            @endif">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">{{ $tier->name }}</h5>
                                                <div class="price mb-3">
                                                    @if($tier->monthly_price > 0)
                                                        <h4 class="text-primary">${{ number_format($tier->monthly_price, 0) }}</h4>
                                                        <small class="text-muted">/month</small>
                                                    @else
                                                        <h4 class="text-success">{{ t('Free') }}</h4>
                                                    @endif
                                                </div>
                                                
                                                <ul class="list-unstyled small">
                                                    <li><strong>{{ $tier->job_posts_limit ?: '∞' }}</strong> {{ t('job posts') }}</li>
                                                    <li><strong>{{ $tier->resume_views_limit ?: '∞' }}</strong> {{ t('resume views') }}</li>
                                                    @if($tier->featured_posts_limit)
                                                        <li><strong>{{ $tier->featured_posts_limit }}</strong> {{ t('featured posts') }}</li>
                                                    @endif
                                                </ul>
                                                
                                                @if($currentSubscription && $currentSubscription->subscription_tier_id === $tier->id)
                                                    <button class="btn btn-outline-success" disabled>
                                                        <i class="fa-solid fa-check me-1"></i>
                                                        {{ t('Current Plan') }}
                                                    </button>
                                                @else
                                                    <a href="{{ url('account/subscription') }}" class="btn btn-outline-primary">
                                                        @if($currentSubscription)
                                                            {{ t('Switch Plan') }}
                                                        @else
                                                            {{ t('Select Plan') }}
                                                        @endif
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="{{ url('pricing') }}" class="btn btn-link">
                                    {{ t('View All Plans & Features') }} <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
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
        .progress-sm {
            height: 8px;
        }
        
        .usage-stat {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .usage-label {
            font-weight: 500;
        }
        
        .usage-count {
            font-weight: 600;
            color: #007bff;
        }
        
        .subscription-price h3 {
            margin-bottom: 0;
        }
        
        .card.border-primary {
            border-width: 2px;
        }
        
        .price h4 {
            margin-bottom: 0;
        }
    </style>
@endsection