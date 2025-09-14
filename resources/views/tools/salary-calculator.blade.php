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
                            <i class="fa-solid fa-calculator icon-color-1"></i>
                            {{ t('Salary Calculator') }}
                        </h1>
                        <p class="lead text-muted">
                            {{ t('Get accurate salary estimates based on real market data, location, and your experience level') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Salary Calculator Form --}}
                <div class="col-lg-4 col-md-5 mb-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fa-solid fa-search me-2"></i>
                                {{ t('Calculate Your Salary') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="salaryCalculatorForm">
                                <div class="mb-3">
                                    <label for="jobTitle" class="form-label">
                                        {{ t('Job Title') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="jobTitle" 
                                           placeholder="{{ t('e.g., Software Engineer, Marketing Manager') }}" required>
                                    <div class="form-text">{{ t('Enter your current or target job title') }}</div>
                                </div>

                                <div class="mb-3">
                                    <label for="location" class="form-label">{{ t('Location') }}</label>
                                    <input type="text" class="form-control" id="location" 
                                           placeholder="{{ t('e.g., New York, London, Remote') }}">
                                    <div class="form-text">{{ t('City or country for location-based adjustments') }}</div>
                                </div>

                                <div class="mb-3">
                                    <label for="experience" class="form-label">{{ t('Years of Experience') }}</label>
                                    <select class="form-select" id="experience">
                                        <option value="0">{{ t('Entry Level (0-1 years)') }}</option>
                                        <option value="2">{{ t('Junior (2-4 years)') }}</option>
                                        <option value="5">{{ t('Mid-Level (5-7 years)') }}</option>
                                        <option value="8">{{ t('Senior (8-12 years)') }}</option>
                                        <option value="13">{{ t('Lead/Principal (13+ years)') }}</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="companySize" class="form-label">{{ t('Company Size') }}</label>
                                    <select class="form-select" id="companySize">
                                        <option value="">{{ t('Any Size') }}</option>
                                        <option value="startup">{{ t('Startup (1-50 employees)') }}</option>
                                        <option value="small">{{ t('Small (51-200 employees)') }}</option>
                                        <option value="medium">{{ t('Medium (201-1000 employees)') }}</option>
                                        <option value="large">{{ t('Large (1000+ employees)') }}</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fa-solid fa-calculator me-2"></i>
                                    {{ t('Calculate Salary') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Quick Stats --}}
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fa-solid fa-chart-bar me-2"></i>
                                {{ t('Platform Statistics') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="fw-bold text-primary">10,000+</div>
                                    <small class="text-muted">{{ t('Salary Data Points') }}</small>
                                </div>
                                <div class="col-6">
                                    <div class="fw-bold text-success">150+</div>
                                    <small class="text-muted">{{ t('Cities Covered') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Results Panel --}}
                <div class="col-lg-8 col-md-7">
                    <div id="loadingSpinner" class="d-none text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ t('Loading...') }}</span>
                        </div>
                        <p class="mt-3">{{ t('Calculating your salary estimate...') }}</p>
                    </div>

                    <div id="salaryResults" class="d-none">
                        {{-- Main Salary Range --}}
                        <div class="card border-success mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fa-solid fa-money-bill-wave me-2"></i>
                                    {{ t('Salary Estimate') }}
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 text-center border-end">
                                        <h3 class="text-success" id="salaryMin">$0</h3>
                                        <small class="text-muted">{{ t('Minimum') }}</small>
                                    </div>
                                    <div class="col-md-4 text-center border-end">
                                        <h3 class="text-primary" id="salaryMedian">$0</h3>
                                        <small class="text-muted">{{ t('Median') }}</small>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <h3 class="text-warning" id="salaryMax">$0</h3>
                                        <small class="text-muted">{{ t('Maximum') }}</small>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ t('Salary Range') }}</span>
                                        <span class="badge bg-info" id="confidenceLevel">High Confidence</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-gradient" role="progressbar" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Cost of Living Adjustment --}}
                        <div id="colAdjustment" class="card border-info mb-4 d-none">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fa-solid fa-map-marker-alt me-2"></i>
                                    {{ t('Cost of Living Adjusted') }}
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="text-center">
                                            <h4 class="text-info" id="adjustedMin">$0</h4>
                                            <small class="text-muted">{{ t('Adjusted Minimum') }}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-center">
                                            <h4 class="text-info" id="adjustedMax">$0</h4>
                                            <small class="text-muted">{{ t('Adjusted Maximum') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Market Insights --}}
                        <div class="card border-warning mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fa-solid fa-lightbulb me-2"></i>
                                    {{ t('Market Insights') }}
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul id="marketInsights" class="list-unstyled mb-0">
                                    {{-- Dynamic content will be loaded here --}}
                                </ul>
                            </div>
                        </div>

                        {{-- Negotiation Tips --}}
                        <div class="card border-dark">
                            <div class="card-header bg-dark text-white">
                                <h6 class="mb-0">
                                    <i class="fa-solid fa-handshake me-2"></i>
                                    {{ t('Negotiation Recommendations') }}
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-success">{{ t('Recommended Range') }}</h6>
                                        <div class="d-flex justify-content-between">
                                            <span>{{ t('Target Min:') }}</span>
                                            <strong id="negotiationMin">$0</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>{{ t('Target Max:') }}</span>
                                            <strong id="negotiationMax">$0</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-info">{{ t('Strategy') }}</h6>
                                        <ul id="negotiationTips" class="list-unstyled small">
                                            {{-- Dynamic tips will be loaded here --}}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Error State --}}
                    <div id="errorMessage" class="alert alert-warning d-none">
                        <h5>{{ t('No Data Found') }}</h5>
                        <p id="errorText"></p>
                        <div id="suggestions"></div>
                    </div>

                    {{-- Default State --}}
                    <div id="defaultMessage" class="text-center py-5">
                        <i class="fa-solid fa-search fa-3x text-muted mb-3"></i>
                        <h4>{{ t('Enter Your Job Details') }}</h4>
                        <p class="text-muted">
                            {{ t('Fill out the form to get personalized salary estimates based on real market data.') }}
                        </p>
                        
                        <div class="row mt-4">
                            <div class="col-md-4 mb-3">
                                <div class="card border-light">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-chart-line text-primary fa-2x mb-2"></i>
                                        <h6>{{ t('Real Market Data') }}</h6>
                                        <small class="text-muted">{{ t('Based on verified salary submissions') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card border-light">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-map text-success fa-2x mb-2"></i>
                                        <h6>{{ t('Location Adjusted') }}</h6>
                                        <small class="text-muted">{{ t('Cost of living considerations') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card border-light">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-handshake text-warning fa-2x mb-2"></i>
                                        <h6>{{ t('Negotiation Ready') }}</h6>
                                        <small class="text-muted">{{ t('Get tips for salary discussions') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_scripts')
<script>
$(document).ready(function() {
    $('#salaryCalculatorForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            job_title: $('#jobTitle').val(),
            location: $('#location').val(),
            experience: $('#experience').val(),
            company_size: $('#companySize').val()
        };
        
        // Show loading state
        $('#defaultMessage, #salaryResults, #errorMessage').addClass('d-none');
        $('#loadingSpinner').removeClass('d-none');
        
        // Make API call
        $.ajax({
            url: '/api/salary-calculator',
            method: 'POST',
            data: formData,
            success: function(response) {
                $('#loadingSpinner').addClass('d-none');
                
                if (response.error) {
                    showError(response.error, response.suggestions);
                } else {
                    showResults(response);
                }
            },
            error: function() {
                $('#loadingSpinner').addClass('d-none');
                showError('{{ t("An error occurred while calculating salary. Please try again.") }}');
            }
        });
    });
    
    function showResults(data) {
        // Update main salary figures
        $('#salaryMin').text('$' + formatNumber(data.salary_data.min));
        $('#salaryMedian').text('$' + formatNumber(data.salary_data.median));
        $('#salaryMax').text('$' + formatNumber(data.salary_data.max));
        
        // Update confidence level
        $('#confidenceLevel').text(data.confidence_level.level + ' Confidence');
        $('#confidenceLevel').removeClass('bg-info bg-warning bg-danger')
                             .addClass(getConfidenceBadgeClass(data.confidence_level.level));
        
        // Cost of living adjustment
        if (data.salary_data.cost_of_living_adjusted) {
            $('#adjustedMin').text('$' + formatNumber(data.salary_data.cost_of_living_adjusted.min));
            $('#adjustedMax').text('$' + formatNumber(data.salary_data.cost_of_living_adjusted.max));
            $('#colAdjustment').removeClass('d-none');
        } else {
            $('#colAdjustment').addClass('d-none');
        }
        
        // Market insights
        const insights = data.market_insights || [];
        $('#marketInsights').html(insights.map(insight => 
            `<li><i class="fa-solid fa-check-circle text-success me-2"></i>${insight}</li>`
        ).join(''));
        
        // Negotiation recommendations
        if (data.recommendations && data.recommendations.negotiation_range) {
            $('#negotiationMin').text('$' + formatNumber(data.recommendations.negotiation_range.min));
            $('#negotiationMax').text('$' + formatNumber(data.recommendations.negotiation_range.max));
            
            const advice = data.recommendations.advice || [];
            $('#negotiationTips').html(advice.map(tip => 
                `<li><i class="fa-solid fa-arrow-right text-primary me-2"></i>${tip}</li>`
            ).join(''));
        }
        
        $('#salaryResults').removeClass('d-none');
    }
    
    function showError(errorMessage, suggestions = []) {
        $('#errorText').text(errorMessage);
        
        if (suggestions && suggestions.length > 0) {
            const suggestionHtml = suggestions.map(suggestion => 
                `<button class="btn btn-outline-primary btn-sm me-2 mb-2 suggestion-btn">${suggestion}</button>`
            ).join('');
            $('#suggestions').html(`
                <p><strong>{{ t('Did you mean:') }}</strong></p>
                ${suggestionHtml}
            `);
        } else {
            $('#suggestions').empty();
        }
        
        $('#errorMessage').removeClass('d-none');
    }
    
    // Handle suggestion clicks
    $(document).on('click', '.suggestion-btn', function() {
        $('#jobTitle').val($(this).text());
        $('#salaryCalculatorForm').submit();
    });
    
    function formatNumber(number) {
        return new Intl.NumberFormat().format(Math.round(number));
    }
    
    function getConfidenceBadgeClass(level) {
        switch(level.toLowerCase()) {
            case 'high': return 'bg-success';
            case 'medium': return 'bg-warning';
            case 'low': return 'bg-danger';
            default: return 'bg-info';
        }
    }
});
</script>
@endsection

@section('after_styles')
<style>
.icon-color-1 {
    color: #007bff;
}

.progress-bar.bg-gradient {
    background: linear-gradient(90deg, #28a745 0%, #007bff 50%, #ffc107 100%);
}

.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.border-end {
    border-right: 1px solid #dee2e6;
}

@media (max-width: 768px) {
    .border-end {
        border-right: none;
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
    }
}

.suggestion-btn:hover {
    background-color: #007bff;
    color: white;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>
@endsection