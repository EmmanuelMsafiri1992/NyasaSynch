<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Analytics Events table
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // page_view, job_click, application_submit, etc.
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('referrer_url')->nullable();
            $table->string('current_url')->nullable();
            $table->json('event_data')->nullable(); // Additional event-specific data
            $table->timestamp('event_timestamp')->useCurrent();
            $table->timestamps();

            $table->index(['event_type', 'event_timestamp']);
            $table->index(['user_id', 'event_timestamp']);
            $table->index(['session_id', 'event_timestamp']);
            $table->index(['country_code', 'event_timestamp']);
        });

        // Job Performance Metrics table
        Schema::create('job_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->date('metric_date');
            $table->integer('views_count')->default(0);
            $table->integer('clicks_count')->default(0);
            $table->integer('applications_count')->default(0);
            $table->integer('saves_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->decimal('click_through_rate', 5, 4)->default(0); // CTR as percentage
            $table->decimal('application_rate', 5, 4)->default(0);
            $table->decimal('avg_time_on_page', 8, 2)->default(0); // in seconds
            $table->json('traffic_sources')->nullable(); // organic, social, direct, etc.
            $table->json('device_breakdown')->nullable(); // mobile, desktop, tablet
            $table->json('location_breakdown')->nullable(); // countries/cities
            $table->timestamps();

            $table->unique(['post_id', 'metric_date'], 'job_metrics_unique');
            $table->index('metric_date');
        });

        // Company Performance Metrics table
        Schema::create('company_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->date('metric_date');
            $table->integer('profile_views')->default(0);
            $table->integer('job_posts_count')->default(0);
            $table->integer('total_applications')->default(0);
            $table->integer('followers_gained')->default(0);
            $table->integer('followers_lost')->default(0);
            $table->decimal('avg_time_to_hire', 5, 2)->default(0); // in days
            $table->decimal('application_conversion_rate', 5, 4)->default(0);
            $table->json('top_job_categories')->nullable();
            $table->json('applicant_demographics')->nullable();
            $table->decimal('employer_rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'metric_date'], 'company_metrics_unique');
            $table->index('metric_date');
        });

        // User Engagement Metrics table
        Schema::create('user_engagement_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('metric_date');
            $table->integer('session_count')->default(0);
            $table->integer('page_views')->default(0);
            $table->decimal('session_duration', 8, 2)->default(0); // in minutes
            $table->integer('jobs_viewed')->default(0);
            $table->integer('jobs_applied')->default(0);
            $table->integer('jobs_saved')->default(0);
            $table->integer('searches_performed')->default(0);
            $table->integer('messages_sent')->default(0);
            $table->integer('profile_updates')->default(0);
            $table->boolean('is_active')->default(false); // Active user for the day
            $table->json('feature_usage')->nullable(); // Which features were used
            $table->json('conversion_funnel')->nullable(); // Funnel stage progression
            $table->timestamps();

            $table->unique(['user_id', 'metric_date'], 'user_metrics_unique');
            $table->index(['metric_date', 'is_active']);
        });

        // Revenue Analytics table
        Schema::create('revenue_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('revenue_date');
            $table->string('revenue_type'); // job_posting, featured_listing, premium_subscription, etc.
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method')->nullable();
            $table->string('plan_type')->nullable(); // basic, premium, enterprise
            $table->integer('quantity')->default(1);
            $table->boolean('is_recurring')->default(false);
            $table->json('metadata')->nullable(); // Additional revenue context
            $table->timestamps();

            $table->index(['revenue_date', 'revenue_type']);
            $table->index(['company_id', 'revenue_date']);
        });

        // Marketing Campaign Performance table
        Schema::create('campaign_performance', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_name');
            $table->string('campaign_type'); // email, social, ppc, content, etc.
            $table->date('campaign_date');
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('spend', 10, 2)->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->decimal('click_through_rate', 5, 4)->default(0);
            $table->decimal('conversion_rate', 5, 4)->default(0);
            $table->decimal('cost_per_click', 8, 2)->default(0);
            $table->decimal('cost_per_acquisition', 8, 2)->default(0);
            $table->decimal('return_on_ad_spend', 8, 2)->default(0);
            $table->json('audience_segments')->nullable();
            $table->json('creative_variants')->nullable();
            $table->timestamps();

            $table->index(['campaign_name', 'campaign_date']);
            $table->index(['campaign_type', 'campaign_date']);
        });

        // Search Analytics table
        Schema::create('search_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('search_query');
            $table->string('search_type')->default('job'); // job, company, location
            $table->integer('search_count')->default(1);
            $table->integer('results_count')->default(0);
            $table->integer('clicks_count')->default(0);
            $table->date('search_date');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('location')->nullable();
            $table->string('category')->nullable();
            $table->boolean('has_filters')->default(false);
            $table->json('applied_filters')->nullable();
            $table->decimal('avg_position_clicked', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['search_query', 'search_date']);
            $table->index(['search_type', 'search_date']);
            $table->index('search_date');
        });

        // Cohort Analysis table
        Schema::create('cohort_analysis', function (Blueprint $table) {
            $table->id();
            $table->date('cohort_date'); // When users first registered
            $table->date('analysis_date'); // The date being analyzed
            $table->integer('period_number'); // Days/weeks/months since registration
            $table->string('cohort_type')->default('user'); // user, company, subscription
            $table->integer('cohort_size'); // Total users in this cohort
            $table->integer('active_users'); // Users active on analysis_date
            $table->decimal('retention_rate', 5, 4); // Percentage still active
            $table->decimal('avg_revenue_per_user', 10, 2)->default(0);
            $table->integer('churned_users')->default(0);
            $table->json('segment_breakdown')->nullable(); // By user type, plan, etc.
            $table->timestamps();

            $table->unique(['cohort_date', 'analysis_date', 'cohort_type'], 'cohort_unique');
            $table->index(['cohort_type', 'analysis_date']);
        });

        // A/B Test Results table
        Schema::create('ab_test_results', function (Blueprint $table) {
            $table->id();
            $table->string('test_name');
            $table->string('variant_name'); // control, variant_a, variant_b
            $table->date('test_date');
            $table->integer('participants_count')->default(0);
            $table->integer('conversions_count')->default(0);
            $table->decimal('conversion_rate', 5, 4)->default(0);
            $table->decimal('confidence_level', 5, 4)->default(0);
            $table->boolean('is_significant')->default(false);
            $table->string('metric_name'); // signup_rate, application_rate, etc.
            $table->decimal('metric_value', 10, 4)->default(0);
            $table->json('segment_data')->nullable(); // Results by user segment
            $table->timestamps();

            $table->index(['test_name', 'test_date']);
            $table->index(['test_date', 'is_significant']);
        });

        // Real-time Dashboard Metrics (cached aggregations)
        Schema::create('dashboard_metrics_cache', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key'); // total_users, active_jobs, monthly_revenue, etc.
            $table->string('time_period'); // today, week, month, year
            $table->json('metric_value'); // Stores complex metric data
            $table->date('calculation_date');
            $table->timestamp('last_updated')->useCurrent();
            $table->integer('cache_duration_minutes')->default(60);
            $table->json('filters_applied')->nullable(); // For filtered metrics
            $table->timestamps();

            $table->unique(['metric_key', 'time_period', 'calculation_date'], 'dashboard_cache_unique');
            $table->index('last_updated');
        });

        // Predictive Analytics Models table
        Schema::create('predictive_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_name');
            $table->string('model_type'); // churn_prediction, revenue_forecast, etc.
            $table->string('target_entity'); // user, company, job
            $table->json('model_parameters');
            $table->decimal('accuracy_score', 5, 4)->nullable();
            $table->decimal('precision_score', 5, 4)->nullable();
            $table->decimal('recall_score', 5, 4)->nullable();
            $table->json('feature_importance')->nullable();
            $table->timestamp('last_trained_at');
            $table->boolean('is_active')->default(true);
            $table->integer('prediction_horizon_days')->default(30);
            $table->json('training_data_summary')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'is_active']);
        });

        // Predictive Analytics Results table
        Schema::create('predictive_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('predictive_models')->onDelete('cascade');
            $table->string('entity_type'); // user, company, job
            $table->unsignedBigInteger('entity_id');
            $table->decimal('prediction_score', 5, 4); // 0-1 probability
            $table->string('prediction_category')->nullable(); // high_risk, low_risk, etc.
            $table->date('prediction_date');
            $table->date('prediction_for_date'); // When prediction applies
            $table->json('contributing_factors')->nullable();
            $table->boolean('actual_outcome')->nullable(); // For validation
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['model_id', 'prediction_date']);
            $table->index(['prediction_category', 'prediction_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictive_results');
        Schema::dropIfExists('predictive_models');
        Schema::dropIfExists('dashboard_metrics_cache');
        Schema::dropIfExists('ab_test_results');
        Schema::dropIfExists('cohort_analysis');
        Schema::dropIfExists('search_analytics');
        Schema::dropIfExists('campaign_performance');
        Schema::dropIfExists('revenue_analytics');
        Schema::dropIfExists('user_engagement_metrics');
        Schema::dropIfExists('company_performance_metrics');
        Schema::dropIfExists('job_performance_metrics');
        Schema::dropIfExists('analytics_events');
    }
};