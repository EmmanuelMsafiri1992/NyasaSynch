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
                            <i class="fa-solid fa-road icon-color-1"></i>
                            {{ t('Career Planning Dashboard') }}
                        </h1>
                        <p class="lead text-muted">
                            {{ t('Plan your career journey with personalized roadmaps and milestone tracking') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-2">{{ t('Ready to plan your next career move?') }}</h5>
                                    <p class="mb-0">{{ t('Start with our career assessment or create a custom career plan') }}</p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="d-grid gap-2 d-md-flex">
                                        <a href="{{ route('career-quiz') }}" class="btn btn-light">
                                            <i class="fa-solid fa-compass me-2"></i>
                                            {{ t('Take Assessment') }}
                                        </a>
                                        <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                                            <i class="fa-solid fa-plus me-2"></i>
                                            {{ t('New Plan') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Career Plans --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>{{ t('Your Career Plans') }}</h3>
                        @if($careerPlans->count() > 0)
                            <span class="badge bg-info">{{ $careerPlans->count() }} {{ t('Active Plans') }}</span>
                        @endif
                    </div>

                    @if($careerPlans->count() > 0)
                        <div class="row">
                            @foreach($careerPlans as $plan)
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 plan-card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">{{ $plan->target_role }}</h6>
                                            <span class="badge bg-{{ $plan->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($plan->status) }}
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <small class="text-muted">{{ t('Timeline') }}: {{ $plan->timeline_years }} {{ t('years') }}</small>
                                                <br>
                                                <small class="text-muted">{{ t('Created') }}: {{ $plan->created_at->format('M d, Y') }}</small>
                                            </div>

                                            @if($plan->milestones->count() > 0)
                                                <div class="progress mb-3" style="height: 8px;">
                                                    @php
                                                        $completedMilestones = $plan->milestones->where('status', 'completed')->count();
                                                        $totalMilestones = $plan->milestones->count();
                                                        $progressPercent = $totalMilestones > 0 ? ($completedMilestones / $totalMilestones) * 100 : 0;
                                                    @endphp
                                                    <div class="progress-bar bg-success" style="width: {{ $progressPercent }}%"></div>
                                                </div>
                                                <div class="d-flex justify-content-between text-sm mb-3">
                                                    <span>{{ t('Progress') }}: {{ $completedMilestones }}/{{ $totalMilestones }} {{ t('milestones') }}</span>
                                                    <span>{{ round($progressPercent) }}%</span>
                                                </div>

                                                <div class="milestone-preview">
                                                    <h6 class="text-muted">{{ t('Next Milestones') }}:</h6>
                                                    @foreach($plan->milestones->where('status', '!=', 'completed')->take(3) as $milestone)
                                                        <div class="milestone-item mb-2">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fa-solid fa-circle-dot text-warning me-2"></i>
                                                                <div class="flex-grow-1">
                                                                    <small class="fw-semibold">{{ $milestone->title }}</small>
                                                                    <br>
                                                                    <small class="text-muted">{{ t('Due') }}: {{ $milestone->target_date->format('M Y') }}</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <div class="d-grid gap-2 mt-3">
                                                <button class="btn btn-outline-primary btn-sm view-plan-btn" 
                                                        data-plan-id="{{ $plan->id }}">
                                                    <i class="fa-solid fa-eye me-2"></i>
                                                    {{ t('View Details') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa-solid fa-route text-muted fa-4x mb-3"></i>
                            <h4 class="text-muted">{{ t('No Career Plans Yet') }}</h4>
                            <p class="text-muted mb-4">{{ t('Create your first career plan to start tracking your professional journey') }}</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                                <i class="fa-solid fa-plus me-2"></i>
                                {{ t('Create Your First Plan') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Assessment History --}}
            @if($assessmentResults->count() > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <h3 class="mb-3">{{ t('Assessment History') }}</h3>
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ t('Assessment') }}</th>
                                                <th>{{ t('Result') }}</th>
                                                <th>{{ t('Completed') }}</th>
                                                <th>{{ t('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($assessmentResults as $result)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $result->assessment->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $result->assessment->type }}</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">{{ $result->primary_result ?? t('Completed') }}</span>
                                                    </td>
                                                    <td>{{ $result->completed_at->format('M d, Y') }}</td>
                                                    <td>
                                                        <a href="{{ route('career-quiz.results', $result->id) }}" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fa-solid fa-eye me-1"></i>
                                                            {{ t('View') }}
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tools & Resources --}}
            <div class="row">
                <div class="col-12">
                    <h3 class="mb-3">{{ t('Career Tools & Resources') }}</h3>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card text-center h-100 tool-card">
                                <div class="card-body">
                                    <i class="fa-solid fa-calculator text-info fa-3x mb-3"></i>
                                    <h6 class="card-title">{{ t('Salary Calculator') }}</h6>
                                    <p class="card-text small text-muted">{{ t('Research salary ranges for your target positions') }}</p>
                                    <a href="{{ route('salary-calculator') }}" class="btn btn-outline-info">
                                        {{ t('Use Tool') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card text-center h-100 tool-card">
                                <div class="card-body">
                                    <i class="fa-solid fa-book text-success fa-3x mb-3"></i>
                                    <h6 class="card-title">{{ t('Career Guides') }}</h6>
                                    <p class="card-text small text-muted">{{ t('Industry-specific guides and career advice') }}</p>
                                    <a href="{{ route('career-guides') }}" class="btn btn-outline-success">
                                        {{ t('Browse Guides') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card text-center h-100 tool-card">
                                <div class="card-body">
                                    <i class="fa-solid fa-chart-line text-warning fa-3x mb-3"></i>
                                    <h6 class="card-title">{{ t('Profile Scoring') }}</h6>
                                    <p class="card-text small text-muted">{{ t('Get insights on your profile competitiveness') }}</p>
                                    <a href="{{ route('profile-scoring') }}" class="btn btn-outline-warning">
                                        {{ t('Check Score') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Plan Modal --}}
    <div class="modal fade" id="createPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ t('Create New Career Plan') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createPlanForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ t('Target Role') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="target_role" required
                                       placeholder="{{ t('e.g., Senior Software Engineer') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ t('Current Role') }}</label>
                                <input type="text" class="form-control" name="current_role"
                                       placeholder="{{ t('e.g., Junior Developer') }}">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ t('Timeline') }} <span class="text-danger">*</span></label>
                                <select class="form-select" name="timeline_years" required>
                                    <option value="">{{ t('Select timeline') }}</option>
                                    <option value="1">1 {{ t('year') }}</option>
                                    <option value="2">2 {{ t('years') }}</option>
                                    <option value="3">3 {{ t('years') }}</option>
                                    <option value="5">5 {{ t('years') }}</option>
                                    <option value="10">10 {{ t('years') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ t('Assessment Result') }}</label>
                                <select class="form-select" name="result_id">
                                    <option value="">{{ t('Select assessment (optional)') }}</option>
                                    @foreach($assessmentResults as $result)
                                        <option value="{{ $result->id }}">
                                            {{ $result->assessment->name }} - {{ $result->completed_at->format('M Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">{{ t('Focus Areas') }}</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="focus_areas[]" value="technical_skills">
                                        <label class="form-check-label">{{ t('Technical Skills') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="focus_areas[]" value="leadership">
                                        <label class="form-check-label">{{ t('Leadership') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="focus_areas[]" value="communication">
                                        <label class="form-check-label">{{ t('Communication') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="focus_areas[]" value="certifications">
                                        <label class="form-check-label">{{ t('Certifications') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="focus_areas[]" value="networking">
                                        <label class="form-check-label">{{ t('Networking') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="focus_areas[]" value="experience">
                                        <label class="form-check-label">{{ t('Experience Building') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ t('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-plus me-2"></i>
                            {{ t('Create Plan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('after_scripts')
<script>
$(document).ready(function() {
    // Create Plan Form
    $('#createPlanForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>{{ t("Creating...") }}').prop('disabled', true);
        
        $.ajax({
            url: '/api/career-assessment/create-plan',
            method: 'POST',
            data: formData,
            success: function(response) {
                $('#createPlanModal').modal('hide');
                location.reload(); // Refresh to show new plan
            },
            error: function(xhr) {
                alert('{{ t("Error creating plan. Please try again.") }}');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // View Plan Details
    $('.view-plan-btn').on('click', function() {
        const planId = $(this).data('plan-id');
        // In a real implementation, this would open a detailed view
        alert('{{ t("Plan details functionality coming soon!") }}');
    });
});
</script>
@endsection

@section('after_styles')
<style>
.icon-color-1 {
    color: #007bff;
}

.plan-card, .tool-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.plan-card:hover, .tool-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.milestone-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.milestone-item:last-child {
    border-bottom: none;
}

.progress {
    border-radius: 10px;
}

.card-header {
    background-color: rgba(0,123,255,0.1);
    border-bottom: 1px solid rgba(0,123,255,0.2);
}

.table th {
    border-top: none;
    font-weight: 600;
}

.badge {
    font-size: 0.75em;
}

@media (max-width: 768px) {
    .d-md-flex.gap-2 {
        gap: 0.5rem !important;
    }
    
    .d-md-flex .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>
@endsection