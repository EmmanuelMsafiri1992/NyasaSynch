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
                            {{ t('Profile Scoring & Analysis') }}
                        </h1>
                        <p class="lead text-muted">
                            {{ t('Get detailed insights into your profile competitiveness and improvement suggestions') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Loading State --}}
            <div id="loadingState" class="row">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">{{ t('Loading...') }}</span>
                    </div>
                    <h5>{{ t('Analyzing your profile...') }}</h5>
                    <p class="text-muted">{{ t('This may take a few seconds') }}</p>
                </div>
            </div>

            {{-- Profile Score Overview --}}
            <div id="scoreOverview" class="row mb-4" style="display: none;">
                <div class="col-lg-8 offset-lg-2">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white text-center">
                            <h4 class="mb-0">{{ t('Your Profile Score') }}</h4>
                        </div>
                        <div class="card-body text-center">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="score-circle mb-3">
                                        <canvas id="scoreChart" width="150" height="150"></canvas>
                                        <div class="score-text">
                                            <span id="overallScore" class="score-number">0</span>
                                            <span class="score-max">/100</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-start">
                                    <div id="scoreDescription" class="mb-3">
                                        <h5 id="scoreTitle" class="text-primary">{{ t('Profile Analysis') }}</h5>
                                        <p id="scoreMessage" class="text-muted">{{ t('Calculating your profile strength...') }}</p>
                                    </div>
                                    <div id="scoreActions">
                                        <button class="btn btn-success btn-sm" id="viewRecommendations">
                                            <i class="fa-solid fa-lightbulb me-2"></i>
                                            {{ t('View Recommendations') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Score Breakdown --}}
            <div id="scoreBreakdown" class="row mb-4" style="display: none;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa-solid fa-chart-bar text-info me-2"></i>
                                {{ t('Score Breakdown') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row" id="breakdownCategories">
                                {{-- Categories will be populated by JavaScript --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Profile Recommendations --}}
            <div id="recommendationsSection" class="row mb-4" style="display: none;">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa-solid fa-exclamation-triangle text-warning me-2"></i>
                                {{ t('Improvement Areas') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul id="recommendationsList" class="list-unstyled">
                                {{-- Recommendations will be populated by JavaScript --}}
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa-solid fa-trophy text-success me-2"></i>
                                {{ t('Your Strengths') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul id="strengthsList" class="list-unstyled">
                                {{-- Strengths will be populated by JavaScript --}}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Candidate Ranking --}}
            <div id="rankingSection" class="row mb-4" style="display: none;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa-solid fa-ranking-star text-primary me-2"></i>
                                {{ t('Market Position') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="ranking-metric">
                                        <h3 id="overallRanking" class="text-primary">-</h3>
                                        <p class="text-muted mb-0">{{ t('Overall Ranking') }}</p>
                                        <small class="text-muted">{{ t('out of') }} <span id="totalCandidates">0</span> {{ t('candidates') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="ranking-metric">
                                        <h3 id="percentileRank" class="text-success">-</h3>
                                        <p class="text-muted mb-0">{{ t('Percentile') }}</p>
                                        <small class="text-muted">{{ t('Top percentage of candidates') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="ranking-metric">
                                        <h3 id="competitiveIndex" class="text-info">-</h3>
                                        <p class="text-muted mb-0">{{ t('Competitive Index') }}</p>
                                        <small class="text-muted">{{ t('Market competitiveness') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Job Recommendations --}}
            <div id="jobRecommendations" class="row mb-4" style="display: none;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa-solid fa-briefcase text-success me-2"></i>
                                {{ t('Recommended Jobs') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="jobsList" class="row">
                                {{-- Job recommendations will be populated by JavaScript --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Skills Analysis --}}
            <div id="skillsAnalysis" class="row mb-4" style="display: none;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa-solid fa-cogs text-warning me-2"></i>
                                {{ t('Skills Analysis') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <h6>{{ t('Your Current Skills') }}</h6>
                                    <div id="currentSkills" class="skills-container">
                                        {{-- Current skills will be populated --}}
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <h6>{{ t('Trending Skills') }}</h6>
                                    <div id="trendingSkills" class="skills-container">
                                        {{-- Trending skills will be populated --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="row" id="actionButtons" style="display: none;">
                <div class="col-12 text-center">
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ route('career-planning') }}" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-road me-2"></i>
                            {{ t('Create Career Plan') }}
                        </a>
                        
                        <a href="{{ route('career-guides') }}" class="btn btn-outline-success btn-lg">
                            <i class="fa-solid fa-book me-2"></i>
                            {{ t('Skill Development Guides') }}
                        </a>
                        
                        <button class="btn btn-outline-info btn-lg" onclick="refreshAnalysis()">
                            <i class="fa-solid fa-refresh me-2"></i>
                            {{ t('Refresh Analysis') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    loadProfileAnalysis();
});

let scoreChart = null;

function loadProfileAnalysis() {
    // Show loading state
    $('#loadingState').show();
    
    // Load all analysis data
    Promise.all([
        $.get('/api/candidate-scoring/profile-score'),
        $.get('/api/candidate-scoring/candidate-ranking'),
        $.get('/api/candidate-scoring/job-recommendations?limit=6'),
        $.get('/api/candidate-scoring/skills-analysis')
    ])
    .then(([profileData, rankingData, jobsData, skillsData]) => {
        displayProfileScore(profileData);
        displayCandidateRanking(rankingData);
        displayJobRecommendations(jobsData.recommended_jobs);
        displaySkillsAnalysis(skillsData);
        
        // Hide loading and show all sections
        $('#loadingState').hide();
        $('#scoreOverview, #scoreBreakdown, #recommendationsSection, #rankingSection, #jobRecommendations, #skillsAnalysis, #actionButtons').show();
    })
    .catch(error => {
        console.error('Error loading profile analysis:', error);
        $('#loadingState').hide();
        alert('{{ t("Error loading profile analysis. Please try again.") }}');
    });
}

function displayProfileScore(data) {
    $('#overallScore').text(Math.round(data.percentage));
    
    // Create score chart
    const ctx = document.getElementById('scoreChart').getContext('2d');
    scoreChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [data.percentage, 100 - data.percentage],
                backgroundColor: [getScoreColor(data.percentage), '#e9ecef'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        }
    });
    
    // Update score description
    const scoreLevel = getScoreLevel(data.percentage);
    $('#scoreTitle').text(scoreLevel.title);
    $('#scoreMessage').text(scoreLevel.message);
    
    // Display breakdown
    let breakdownHtml = '';
    Object.entries(data.breakdown).forEach(([category, scores]) => {
        breakdownHtml += `
            <div class="col-md-4 mb-3">
                <div class="breakdown-item">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold">${formatCategoryName(category)}</span>
                        <span class="text-muted">${Math.round(scores.percentage)}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" style="width: ${scores.percentage}%; background-color: ${getScoreColor(scores.percentage)}"></div>
                    </div>
                    <small class="text-muted">${scores.score}/${scores.max_score}</small>
                </div>
            </div>
        `;
    });
    $('#breakdownCategories').html(breakdownHtml);
    
    // Display recommendations
    let recommendationsHtml = '';
    data.recommendations.forEach(rec => {
        recommendationsHtml += `
            <li class="mb-2">
                <i class="fa-solid fa-arrow-right text-warning me-2"></i>
                ${rec}
            </li>
        `;
    });
    $('#recommendationsList').html(recommendationsHtml);
}

function displayCandidateRanking(data) {
    $('#overallRanking').text('#' + data.overall_ranking.toLocaleString());
    $('#percentileRank').text(data.percentile + 'th');
    $('#competitiveIndex').text(Math.round(data.percentile) + '/100');
    $('#totalCandidates').text(data.comparison_pool_size.toLocaleString());
    
    // Display strengths
    let strengthsHtml = '';
    data.strengths.forEach(strength => {
        strengthsHtml += `
            <li class="mb-2">
                <i class="fa-solid fa-check-circle text-success me-2"></i>
                ${formatCategoryName(strength)}
            </li>
        `;
    });
    $('#strengthsList').html(strengthsHtml);
}

function displayJobRecommendations(jobs) {
    let jobsHtml = '';
    jobs.forEach(job => {
        jobsHtml += `
            <div class="col-md-6 mb-3">
                <div class="card job-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title mb-0">${job.title}</h6>
                            <span class="badge bg-success">${job.match_percentage}% {{ t('Match') }}</span>
                        </div>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fa-solid fa-building me-1"></i> ${job.company_name}<br>
                                <i class="fa-solid fa-location-dot me-1"></i> ${job.location}
                            </small>
                        </p>
                        <div class="job-strengths">
                            ${job.key_strengths.map(strength => `<span class="badge bg-light text-dark me-1">${strength}</span>`).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    $('#jobsList').html(jobsHtml);
}

function displaySkillsAnalysis(data) {
    // Current skills
    let currentSkillsHtml = '';
    data.current_skills.forEach(skill => {
        currentSkillsHtml += `<span class="badge bg-primary me-2 mb-2">${skill}</span>`;
    });
    $('#currentSkills').html(currentSkillsHtml || '<span class="text-muted">{{ t("No skills specified") }}</span>');
    
    // Trending skills
    let trendingSkillsHtml = '';
    data.trending_skills.forEach(skill => {
        trendingSkillsHtml += `<span class="badge bg-warning text-dark me-2 mb-2">${skill}</span>`;
    });
    $('#trendingSkills').html(trendingSkillsHtml);
}

function getScoreColor(percentage) {
    if (percentage >= 80) return '#28a745';
    if (percentage >= 60) return '#ffc107';
    if (percentage >= 40) return '#fd7e14';
    return '#dc3545';
}

function getScoreLevel(percentage) {
    if (percentage >= 80) {
        return {
            title: '{{ t("Excellent Profile") }}',
            message: '{{ t("Your profile stands out! You\'re highly competitive in the job market.") }}'
        };
    }
    if (percentage >= 60) {
        return {
            title: '{{ t("Good Profile") }}',
            message: '{{ t("Your profile is solid with room for improvement to stand out more.") }}'
        };
    }
    if (percentage >= 40) {
        return {
            title: '{{ t("Average Profile") }}',
            message: '{{ t("Your profile needs enhancement to be more competitive.") }}'
        };
    }
    return {
        title: '{{ t("Needs Improvement") }}',
        message: '{{ t("Focus on completing your profile and adding more relevant information.") }}'
    };
}

function formatCategoryName(category) {
    return category.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function refreshAnalysis() {
    loadProfileAnalysis();
}
</script>
@endsection

@section('after_styles')
<style>
.icon-color-1 {
    color: #007bff;
}

.score-circle {
    position: relative;
    display: inline-block;
}

.score-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.score-number {
    font-size: 2rem;
    font-weight: bold;
    color: #007bff;
}

.score-max {
    font-size: 1rem;
    color: #6c757d;
}

.breakdown-item {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border-left: 4px solid #007bff;
}

.ranking-metric {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
}

.ranking-metric h3 {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.job-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.job-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.skills-container {
    min-height: 100px;
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.card-header {
    background-color: rgba(0,123,255,0.1);
    border-bottom: 1px solid rgba(0,123,255,0.2);
}

.progress {
    border-radius: 10px;
}

@media (max-width: 768px) {
    .d-flex.flex-wrap .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .ranking-metric h3 {
        font-size: 2rem;
    }
}
</style>
@endsection