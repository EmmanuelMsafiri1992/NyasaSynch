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

@section('content')
    @include('common.spacer')
    <div class="main-container">
        <div class="container">
            
            {{-- Page Header --}}
            <div class="row">
                <div class="col-12">
                    <div class="text-center mb-5">
                        <h1 class="h2 mb-3">
                            <i class="fa-solid fa-chart-line icon-color-1"></i>
                            {{ t('Your Career Assessment Results') }}
                        </h1>
                        <p class="lead text-muted">
                            {{ t('Completed') }}: {{ $result->assessment->name }} | {{ $result->completed_at->format('M d, Y') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Results Overview --}}
            <div class="row mb-4">
                <div class="col-lg-8 offset-lg-2">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white text-center">
                            <h4 class="mb-0">{{ t('Assessment Complete!') }}</h4>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-4">
                                <i class="fa-solid fa-trophy text-warning fa-4x mb-3"></i>
                                <h3 class="text-primary">{{ $result->primary_result ?? t('Career Professional') }}</h3>
                                <p class="lead">{{ t('Your top career match based on personality and preferences') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($recommendations['career_matches']))
                {{-- Career Matches --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fa-solid fa-bullseye text-primary me-2"></i>
                                    {{ t('Top Career Matches') }}
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach(array_slice($recommendations['career_matches'], 0, 6) as $index => $match)
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card h-100 border-primary">
                                                <div class="card-body text-center">
                                                    <div class="mb-3">
                                                        <span class="badge bg-primary fs-6">{{ $match['match_percentage'] }}% {{ t('Match') }}</span>
                                                    </div>
                                                    <h6 class="card-title">{{ $match['title'] }}</h6>
                                                    @if(!empty($match['key_strengths']))
                                                        <div class="mt-2">
                                                            <small class="text-muted">{{ t('Key Strengths') }}:</small><br>
                                                            @foreach(array_slice($match['key_strengths'], 0, 3) as $strength)
                                                                <span class="badge bg-light text-dark me-1">{{ ucfirst(str_replace('_', ' ', $strength)) }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Personality Insights --}}
            @if(!empty($result->results['personality_traits']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fa-solid fa-brain text-info me-2"></i>
                                    {{ t('Personality Insights') }}
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($result->results['personality_traits'] as $trait => $score)
                                        @if($score > 60) {{-- Only show strong traits --}}
                                            <div class="col-md-6 mb-3">
                                                <div class="trait-item">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $trait)) }}</span>
                                                        <span class="text-muted">{{ round($score) }}%</span>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-info" 
                                                             style="width: {{ $score }}%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Next Steps & Recommendations --}}
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa-solid fa-list-check text-success me-2"></i>
                                {{ t('Next Steps') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                @foreach($recommendations['next_steps'] as $step)
                                    <li class="mb-2">
                                        <i class="fa-solid fa-check-circle text-success me-2"></i>
                                        {{ t($step) }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa-solid fa-graduation-cap text-warning me-2"></i>
                                {{ t('Helpful Resources') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            @foreach($recommendations['resources'] as $resource)
                                <div class="resource-item mb-3 p-3 bg-light rounded">
                                    <h6 class="mb-1">
                                        <a href="{{ $resource['url'] }}" class="text-decoration-none">
                                            {{ t($resource['title']) }}
                                        </a>
                                    </h6>
                                    <p class="mb-0 text-muted small">{{ t($resource['description']) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="row">
                <div class="col-12 text-center">
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ route('career-planning') }}" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-road me-2"></i>
                            {{ t('Create Career Plan') }}
                        </a>
                        
                        <a href="{{ route('salary-calculator') }}" class="btn btn-outline-info btn-lg">
                            <i class="fa-solid fa-calculator me-2"></i>
                            {{ t('Salary Calculator') }}
                        </a>
                        
                        <a href="{{ route('career-guides') }}" class="btn btn-outline-success btn-lg">
                            <i class="fa-solid fa-book me-2"></i>
                            {{ t('Career Guides') }}
                        </a>
                        
                        <button class="btn btn-outline-secondary btn-lg" onclick="window.print()">
                            <i class="fa-solid fa-print me-2"></i>
                            {{ t('Print Results') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Disclaimer --}}
            <div class="row mt-5">
                <div class="col-12">
                    <div class="alert alert-info">
                        <small>
                            <i class="fa-solid fa-info-circle me-2"></i>
                            {{ t('These results are based on your assessment responses and are meant to provide career guidance. Consider multiple factors when making career decisions, including personal interests, skills, values, and market opportunities.') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_styles')
<style>
.icon-color-1 {
    color: #007bff;
}

.trait-item {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border-left: 4px solid #17a2b8;
}

.resource-item {
    transition: all 0.2s ease;
}

.resource-item:hover {
    background-color: #e9ecef !important;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.progress {
    border-radius: 10px;
}

.card {
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border: none;
}

.card-header {
    border-bottom: 2px solid rgba(0,0,0,0.05);
}

@media print {
    .btn, .alert {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
}

@media (max-width: 768px) {
    .d-flex.flex-wrap .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>
@endsection