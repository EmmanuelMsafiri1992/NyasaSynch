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
    $guides = $guides ?? collect();
    $featuredGuides = $featuredGuides ?? collect();
    $categories = $categories ?? collect();
    $selectedCategory = $selectedCategory ?? 'all';
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
                            <i class="fa-solid fa-graduation-cap icon-color-1"></i>
                            {{ t('Career Resource Center') }}
                        </h1>
                        <p class="lead text-muted">
                            {{ t('Comprehensive guides, tutorials, and resources to accelerate your career growth') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Search and Filter Bar --}}
            <div class="row mb-4">
                <div class="col-lg-8 offset-lg-2">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" id="filterForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fa-solid fa-search"></i>
                                            </span>
                                            <input type="text" class="form-control" name="search" 
                                                   value="{{ request('search') }}" 
                                                   placeholder="{{ t('Search guides, tutorials, skills...') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <select name="category" class="form-select">
                                            <option value="all">{{ t('All Categories') }}</option>
                                            <option value="technology" {{ $selectedCategory == 'technology' ? 'selected' : '' }}>
                                                {{ t('Technology') }}
                                            </option>
                                            <option value="healthcare" {{ $selectedCategory == 'healthcare' ? 'selected' : '' }}>
                                                {{ t('Healthcare') }}
                                            </option>
                                            <option value="finance" {{ $selectedCategory == 'finance' ? 'selected' : '' }}>
                                                {{ t('Finance') }}
                                            </option>
                                            <option value="marketing" {{ $selectedCategory == 'marketing' ? 'selected' : '' }}>
                                                {{ t('Marketing & Sales') }}
                                            </option>
                                            <option value="education" {{ $selectedCategory == 'education' ? 'selected' : '' }}>
                                                {{ t('Education') }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fa-solid fa-filter"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Featured Guides --}}
            @if($featuredGuides->isNotEmpty())
            <div class="row mb-5">
                <div class="col-12">
                    <h3 class="mb-4">
                        <i class="fa-solid fa-star text-warning me-2"></i>
                        {{ t('Featured Career Guides') }}
                    </h3>
                </div>
                
                @foreach($featuredGuides->take(3) as $guide)
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 featured-guide">
                        @if($guide->featured_image)
                        <img src="{{ $guide->featured_image }}" class="card-img-top" alt="{{ $guide->title }}" style="height: 200px; object-fit: cover;">
                        @else
                        <div class="card-img-top bg-primary text-white d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fa-solid fa-book fa-3x"></i>
                        </div>
                        @endif
                        
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge category-{{ strtolower($guide->category) }}">
                                    {{ ucfirst($guide->category) }}
                                </span>
                                <small class="text-muted ms-2">
                                    <i class="fa-solid fa-clock me-1"></i>
                                    {{ $guide->estimated_read_time }} {{ t('min read') }}
                                </small>
                            </div>
                            
                            <h5 class="card-title">{{ $guide->title }}</h5>
                            <p class="card-text flex-grow-1">{{ Str::limit($guide->description, 120) }}</p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="rating">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fa-solid fa-star {{ $i <= $guide->rating ? 'text-warning' : 'text-muted' }}"></i>
                                        @endfor
                                        <small class="text-muted ms-2">({{ $guide->rating_count }})</small>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fa-solid fa-eye me-1"></i>{{ number_format($guide->view_count) }}
                                    </small>
                                </div>
                                
                                <a href="{{ route('career-guide.show', $guide->slug) }}" class="btn btn-primary w-100">
                                    {{ t('Read Guide') }}
                                    <i class="fa-solid fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Category Navigation --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="category-nav">
                        <div class="nav nav-pills justify-content-center flex-wrap">
                            <a class="nav-link {{ $selectedCategory == 'all' ? 'active' : '' }}" 
                               href="?category=all">
                                <i class="fa-solid fa-th-large me-2"></i>{{ t('All') }}
                            </a>
                            <a class="nav-link {{ $selectedCategory == 'technology' ? 'active' : '' }}" 
                               href="?category=technology">
                                <i class="fa-solid fa-laptop-code me-2"></i>{{ t('Technology') }}
                            </a>
                            <a class="nav-link {{ $selectedCategory == 'healthcare' ? 'active' : '' }}" 
                               href="?category=healthcare">
                                <i class="fa-solid fa-heartbeat me-2"></i>{{ t('Healthcare') }}
                            </a>
                            <a class="nav-link {{ $selectedCategory == 'finance' ? 'active' : '' }}" 
                               href="?category=finance">
                                <i class="fa-solid fa-chart-line me-2"></i>{{ t('Finance') }}
                            </a>
                            <a class="nav-link {{ $selectedCategory == 'marketing' ? 'active' : '' }}" 
                               href="?category=marketing">
                                <i class="fa-solid fa-bullhorn me-2"></i>{{ t('Marketing') }}
                            </a>
                            <a class="nav-link {{ $selectedCategory == 'education' ? 'active' : '' }}" 
                               href="?category=education">
                                <i class="fa-solid fa-graduation-cap me-2"></i>{{ t('Education') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- All Guides Grid --}}
            <div class="row">
                <div class="col-lg-9">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>{{ t('Career Guides & Resources') }}</h4>
                        <div class="view-toggle">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="viewType" id="gridView" checked>
                                <label class="btn btn-outline-secondary btn-sm" for="gridView">
                                    <i class="fa-solid fa-th"></i>
                                </label>
                                <input type="radio" class="btn-check" name="viewType" id="listView">
                                <label class="btn btn-outline-secondary btn-sm" for="listView">
                                    <i class="fa-solid fa-list"></i>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="guidesContainer" class="row">
                        @forelse($guides as $guide)
                        <div class="col-lg-4 col-md-6 mb-4 guide-item">
                            <div class="card h-100 guide-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="mb-3">
                                        <span class="badge category-{{ strtolower($guide->category) }}">
                                            {{ ucfirst($guide->category) }}
                                        </span>
                                        @if($guide->subcategory)
                                        <span class="badge bg-light text-dark">{{ $guide->subcategory }}</span>
                                        @endif
                                        <div class="float-end">
                                            <span class="badge bg-info">{{ ucfirst($guide->difficulty_level) }}</span>
                                        </div>
                                    </div>
                                    
                                    <h5 class="card-title">{{ $guide->title }}</h5>
                                    <p class="card-text flex-grow-1">{{ Str::limit($guide->description, 100) }}</p>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted">
                                                <i class="fa-solid fa-clock me-1"></i>
                                                {{ $guide->estimated_read_time }} min
                                            </small>
                                            <div class="rating-small">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fa-solid fa-star {{ $i <= $guide->rating ? 'text-warning' : 'text-muted' }}"></i>
                                                @endfor
                                            </div>
                                        </div>
                                        
                                        <a href="{{ route('career-guide.show', $guide->slug) }}" class="btn btn-outline-primary btn-sm w-100">
                                            {{ t('Read More') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fa-solid fa-search fa-3x text-muted mb-3"></i>
                                <h4>{{ t('No guides found') }}</h4>
                                <p class="text-muted">{{ t('Try adjusting your search criteria or browse all categories') }}</p>
                                <a href="{{ route('career-guides') }}" class="btn btn-primary">
                                    {{ t('View All Guides') }}
                                </a>
                            </div>
                        </div>
                        @endforelse
                    </div>

                    {{-- Pagination --}}
                    @if($guides instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="d-flex justify-content-center">
                        {{ $guides->appends(request()->query())->links() }}
                    </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="col-lg-3">
                    {{-- Quick Tools --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fa-solid fa-tools me-2"></i>
                                {{ t('Career Tools') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('salary-calculator') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fa-solid fa-calculator me-2"></i>
                                    {{ t('Salary Calculator') }}
                                </a>
                                <a href="{{ route('career-quiz') }}" class="btn btn-outline-success btn-sm">
                                    <i class="fa-solid fa-compass me-2"></i>
                                    {{ t('Career Quiz') }}
                                </a>
                                <a href="{{ route('career-quiz') }}" class="btn btn-outline-info btn-sm">
                                    <i class="fa-solid fa-chart-bar me-2"></i>
                                    {{ t('Skill Assessment') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Popular Topics --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fa-solid fa-fire me-2"></i>
                                {{ t('Popular Topics') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="tag-cloud">
                                <a href="?search=remote work" class="badge bg-light text-dark me-2 mb-2">Remote Work</a>
                                <a href="?search=leadership" class="badge bg-light text-dark me-2 mb-2">Leadership</a>
                                <a href="?search=programming" class="badge bg-light text-dark me-2 mb-2">Programming</a>
                                <a href="?search=data science" class="badge bg-light text-dark me-2 mb-2">Data Science</a>
                                <a href="?search=project management" class="badge bg-light text-dark me-2 mb-2">Project Management</a>
                                <a href="?search=networking" class="badge bg-light text-dark me-2 mb-2">Networking</a>
                                <a href="?search=interview prep" class="badge bg-light text-dark me-2 mb-2">Interview Prep</a>
                            </div>
                        </div>
                    </div>

                    {{-- Newsletter Signup --}}
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fa-solid fa-envelope me-2"></i>
                                {{ t('Stay Updated') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="card-text small">{{ t('Get weekly career tips and new guides delivered to your inbox') }}</p>
                            <form id="newsletterForm">
                                <div class="mb-3">
                                    <input type="email" class="form-control form-control-sm" 
                                           placeholder="{{ t('Your email address') }}" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    {{ t('Subscribe') }}
                                </button>
                            </form>
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
    // View toggle functionality
    $('input[name="viewType"]').change(function() {
        if ($(this).attr('id') === 'listView') {
            $('#guidesContainer .guide-item').removeClass('col-lg-4 col-md-6').addClass('col-12');
            $('.guide-card').addClass('list-view');
        } else {
            $('#guidesContainer .guide-item').removeClass('col-12').addClass('col-lg-4 col-md-6');
            $('.guide-card').removeClass('list-view');
        }
    });
    
    // Auto-submit filter form on category change
    $('select[name="category"]').change(function() {
        $('#filterForm').submit();
    });
    
    // Newsletter subscription
    $('#newsletterForm').on('submit', function(e) {
        e.preventDefault();
        const email = $(this).find('input[type="email"]').val();
        
        // Simple newsletter subscription simulation
        alert('{{ t("Thank you for subscribing! You'll receive weekly career updates.") }}');
        $(this).find('input[type="email"]').val('');
    });
});

</script>
@endsection

@section('after_styles')
<style>
.icon-color-1 {
    color: #007bff;
}

.featured-guide {
    border: 2px solid #007bff;
    transition: all 0.3s ease;
}

.featured-guide:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,123,255,0.15);
}

.guide-card {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.guide-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.guide-card.list-view {
    margin-bottom: 1rem;
}

.guide-card.list-view .card-body {
    flex-direction: row !important;
    align-items: center;
}

.guide-card.list-view .card-title {
    margin-bottom: 0.5rem;
}

.category-nav .nav-link {
    margin: 0.25rem;
    border-radius: 25px;
    transition: all 0.2s ease;
}

.category-nav .nav-link:hover {
    transform: translateY(-2px);
}

.rating i {
    font-size: 0.875rem;
}

.rating-small i {
    font-size: 0.75rem;
}

.tag-cloud .badge {
    transition: all 0.2s ease;
    cursor: pointer;
    font-weight: 500;
    border: 1px solid #dee2e6;
}

.tag-cloud .badge:hover {
    background-color: #007bff !important;
    color: white !important;
    transform: scale(1.05);
}

.view-toggle .btn {
    border-color: #dee2e6;
}

.btn-check:checked + .btn-outline-secondary {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

/* Category badges */
.badge.category-technology {
    background-color: #007bff !important;
    color: white;
}

.badge.category-healthcare {
    background-color: #28a745 !important;
    color: white;
}

.badge.category-finance {
    background-color: #ffc107 !important;
    color: black;
}

.badge.category-marketing {
    background-color: #dc3545 !important;
    color: white;
}

.badge.category-education {
    background-color: #17a2b8 !important;
    color: white;
}

.badge.category-business {
    background-color: #6f42c1 !important;
    color: white;
}

.badge[class*="category-"]:not(.category-technology):not(.category-healthcare):not(.category-finance):not(.category-marketing):not(.category-education):not(.category-business) {
    background-color: #6c757d !important;
    color: white;
}

@media (max-width: 768px) {
    .category-nav .nav {
        justify-content: flex-start !important;
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 0.5rem;
    }
    
    .category-nav .nav-link {
        white-space: nowrap;
        margin: 0.25rem 0.125rem;
    }
}
</style>
@endsection