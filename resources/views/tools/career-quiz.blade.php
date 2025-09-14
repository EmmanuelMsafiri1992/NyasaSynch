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
    $assessment = $assessment ?? null;
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
                            <i class="fa-solid fa-compass icon-color-1"></i>
                            {{ t('Career Match Quiz') }}
                        </h1>
                        <p class="lead text-muted">
                            {{ t('Discover your perfect career path with our comprehensive personality and skills assessment') }}
                        </p>
                    </div>
                </div>
            </div>

            @if(!$assessment)
                {{-- Assessment Selection --}}
                <div class="row">
                    <div class="col-lg-8 offset-lg-2">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white text-center">
                                <h4 class="mb-0">{{ t('Choose Your Assessment') }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="assessment-option">
                                            <div class="card h-100 border-info assessment-card" data-type="comprehensive">
                                                <div class="card-body text-center">
                                                    <i class="fa-solid fa-brain text-info fa-3x mb-3"></i>
                                                    <h5 class="card-title">{{ t('Comprehensive Assessment') }}</h5>
                                                    <p class="card-text">{{ t('Complete personality and skills evaluation with detailed career recommendations') }}</p>
                                                    <div class="features">
                                                        <small class="text-muted">
                                                            <i class="fa-solid fa-clock me-1"></i> 20-25 minutes<br>
                                                            <i class="fa-solid fa-chart-bar me-1"></i> 50 questions<br>
                                                            <i class="fa-solid fa-target me-1"></i> Detailed results
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <div class="assessment-option">
                                            <div class="card h-100 border-success assessment-card" data-type="quick">
                                                <div class="card-body text-center">
                                                    <i class="fa-solid fa-bolt text-success fa-3x mb-3"></i>
                                                    <h5 class="card-title">{{ t('Quick Match') }}</h5>
                                                    <p class="card-text">{{ t('Fast personality assessment for immediate job recommendations') }}</p>
                                                    <div class="features">
                                                        <small class="text-muted">
                                                            <i class="fa-solid fa-clock me-1"></i> 5-10 minutes<br>
                                                            <i class="fa-solid fa-chart-bar me-1"></i> 20 questions<br>
                                                            <i class="fa-solid fa-target me-1"></i> Instant results
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button id="startAssessment" class="btn btn-primary btn-lg disabled">
                                        <i class="fa-solid fa-play me-2"></i>
                                        {{ t('Start Assessment') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Benefits Section --}}
                <div class="row mt-5">
                    <div class="col-12">
                        <h3 class="text-center mb-4">{{ t('Why Take Our Career Assessment?') }}</h3>
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="text-center">
                                    <i class="fa-solid fa-bullseye text-primary fa-2x mb-3"></i>
                                    <h5>{{ t('Accurate Matching') }}</h5>
                                    <p class="text-muted">{{ t('Our algorithm matches your personality traits with thousands of job profiles') }}</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="text-center">
                                    <i class="fa-solid fa-chart-line text-success fa-2x mb-3"></i>
                                    <h5>{{ t('Career Growth Path') }}</h5>
                                    <p class="text-muted">{{ t('Get personalized recommendations for skill development and career progression') }}</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="text-center">
                                    <i class="fa-solid fa-users text-info fa-2x mb-3"></i>
                                    <h5>{{ t('Trusted by Professionals') }}</h5>
                                    <p class="text-muted">{{ t('Join thousands who have discovered their ideal career path with our assessment') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            @else
                {{-- Quiz Interface --}}
                <div class="row">
                    <div class="col-lg-10 offset-lg-1">
                        {{-- Progress Bar --}}
                        <div class="card border-info mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">{{ t('Progress') }}</span>
                                    <span id="progressText">1 / {{ count($assessment->questions) }}</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div id="progressBar" class="progress-bar bg-primary" role="progressbar" 
                                         style="width: {{ round(1 / count($assessment->questions) * 100, 2) }}%"></div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Question Container --}}
                        <div class="card border-primary">
                            <div class="card-body">
                                <form id="assessmentForm">
                                    <input type="hidden" id="assessmentId" value="{{ $assessment->id }}">
                                    <input type="hidden" id="currentQuestion" value="0">
                                    <input type="hidden" id="totalQuestions" value="{{ count($assessment->questions) }}">
                                    
                                    <div id="questionContainer">
                                        {{-- Questions will be loaded dynamically --}}
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mt-4">
                                        <button type="button" id="prevBtn" class="btn btn-outline-secondary" disabled>
                                            <i class="fa-solid fa-arrow-left me-2"></i>
                                            {{ t('Previous') }}
                                        </button>
                                        
                                        <button type="button" id="nextBtn" class="btn btn-primary">
                                            {{ t('Next') }}
                                            <i class="fa-solid fa-arrow-right ms-2"></i>
                                        </button>
                                        
                                        <button type="submit" id="submitBtn" class="btn btn-success d-none">
                                            <i class="fa-solid fa-check me-2"></i>
                                            {{ t('Complete Assessment') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('after_scripts')
@if(!$assessment)
<script>
$(document).ready(function() {
    let selectedAssessmentType = null;
    
    $('.assessment-card').on('click', function() {
        $('.assessment-card').removeClass('border-primary bg-light');
        $(this).addClass('border-primary bg-light');
        selectedAssessmentType = $(this).data('type');
        $('#startAssessment').removeClass('disabled');
    });
    
    $('#startAssessment').on('click', function() {
        if (selectedAssessmentType && !$(this).hasClass('disabled')) {
            window.location.href = `/career-quiz/${selectedAssessmentType}`;
        }
    });
});
</script>
@else
<script>
$(document).ready(function() {
    const questions = @json($assessment->questions);
    let currentQuestionIndex = 0;
    let answers = {};
    
    function showQuestion(index) {
        if (index < 0 || index >= questions.length) return;
        
        const question = questions[index];
        
        let optionsHtml = '';
        if (question.type === 'multiple_choice') {
            optionsHtml = question.options.map((option, i) => `
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="answer" id="option${i}" value="${option.value}">
                    <label class="form-check-label" for="option${i}">
                        ${option.text}
                    </label>
                </div>
            `).join('');
        } else if (question.type === 'scale') {
            optionsHtml = `
                <div class="text-center mb-4">
                    <div class="scale-labels d-flex justify-content-between mb-2">
                        <small class="text-muted">${question.scale_labels.min}</small>
                        <small class="text-muted">${question.scale_labels.max}</small>
                    </div>
                    <div class="rating-scale">
                        ${Array.from({length: question.scale_max}, (_, i) => `
                            <input type="radio" name="answer" id="scale${i+1}" value="${i+1}" class="btn-check">
                            <label class="btn btn-outline-primary me-2" for="scale${i+1}">${i+1}</label>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        $('#questionContainer').html(`
            <div class="question-content">
                <h4 class="mb-4">{{ t('Question') }} ${index + 1}</h4>
                <h5 class="mb-4">${question.question}</h5>
                ${question.description ? `<p class="text-muted mb-4">${question.description}</p>` : ''}
                ${optionsHtml}
            </div>
        `);
        
        // Restore previous answer if exists
        if (answers[index] !== undefined) {
            $(`input[name="answer"][value="${answers[index]}"]`).prop('checked', true);
        }
        
        // Update progress
        const progress = ((index + 1) / questions.length) * 100;
        $('#progressBar').css('width', progress + '%');
        $('#progressText').text(`${index + 1} / ${questions.length}`);
        
        // Update navigation buttons
        $('#prevBtn').prop('disabled', index === 0);
        $('#nextBtn').toggle(index < questions.length - 1);
        $('#submitBtn').toggle(index === questions.length - 1);
        
        currentQuestionIndex = index;
    }
    
    $('#nextBtn').on('click', function() {
        const selectedAnswer = $('input[name="answer"]:checked').val();
        
        if (!selectedAnswer) {
            alert('{{ t("Please select an answer before continuing.") }}');
            return;
        }
        
        answers[currentQuestionIndex] = selectedAnswer;
        
        if (currentQuestionIndex < questions.length - 1) {
            showQuestion(currentQuestionIndex + 1);
        }
    });
    
    $('#prevBtn').on('click', function() {
        if (currentQuestionIndex > 0) {
            showQuestion(currentQuestionIndex - 1);
        }
    });
    
    $('#assessmentForm').on('submit', function(e) {
        e.preventDefault();
        
        const selectedAnswer = $('input[name="answer"]:checked').val();
        if (selectedAnswer) {
            answers[currentQuestionIndex] = selectedAnswer;
        }
        
        // Check if all questions are answered
        const unanswered = questions.findIndex((_, i) => answers[i] === undefined);
        if (unanswered !== -1) {
            alert(`{{ t("Please answer question") }} ${unanswered + 1} {{ t("before submitting.") }}`);
            showQuestion(unanswered);
            return;
        }
        
        // Submit assessment
        submitAssessment();
    });
    
    function submitAssessment() {
        const assessmentData = {
            assessment_id: $('#assessmentId').val(),
            answers: answers
        };
        
        // Show loading state
        $('#submitBtn').html(`
            <span class="spinner-border spinner-border-sm me-2"></span>
            {{ t('Processing...') }}
        `).prop('disabled', true);
        
        $.ajax({
            url: '/api/career-assessment/submit',
            method: 'POST',
            data: assessmentData,
            success: function(response) {
                window.location.href = `/career-quiz/results/${response.result_id}`;
            },
            error: function() {
                alert('{{ t("An error occurred while processing your assessment. Please try again.") }}');
                $('#submitBtn').html(`
                    <i class="fa-solid fa-check me-2"></i>
                    {{ t('Complete Assessment') }}
                `).prop('disabled', false);
            }
        });
    }
    
    // Initialize first question
    showQuestion(0);
});
</script>
@endif
@endsection

@section('after_styles')
<style>
.icon-color-1 {
    color: #007bff;
}

.assessment-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.assessment-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.assessment-card.border-primary {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,123,255,0.2);
}

.features {
    margin-top: 1rem;
}

.rating-scale {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
}

.rating-scale .btn {
    min-width: 50px;
    border-radius: 50px;
}

.question-content {
    min-height: 300px;
}

.form-check {
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.form-check:hover {
    background-color: #f8f9fa;
}

.form-check-input:checked + .form-check-label {
    font-weight: 600;
    color: #0d6efd;
}

.progress {
    border-radius: 10px;
}

.progress-bar {
    transition: width 0.3s ease;
}

@media (max-width: 768px) {
    .rating-scale {
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    
    .rating-scale .btn {
        min-width: 40px;
        font-size: 0.875rem;
    }
}
</style>
@endsection