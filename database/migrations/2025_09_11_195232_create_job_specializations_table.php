<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_specializations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->json('required_fields')->nullable(); // Industry-specific form fields
            $table->json('filter_options')->nullable(); // Specialized filters
            $table->json('metadata')->nullable(); // Additional industry data
            $table->boolean('is_remote_first')->default(false);
            $table->boolean('has_equity_info')->default(false);
            $table->boolean('has_impact_metrics')->default(false);
            $table->boolean('requires_clearance')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Create pivot table for posts and specializations
        Schema::create('post_specializations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('specialization_id')->constrained('job_specializations')->onDelete('cascade');
            $table->json('specialization_data')->nullable(); // Industry-specific data
            $table->timestamps();
            
            $table->unique(['post_id', 'specialization_id']);
        });

        // Enhanced job attributes table
        Schema::create('job_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->enum('work_arrangement', ['remote', 'hybrid', 'onsite', 'flexible'])->nullable();
            $table->string('timezone_preference')->nullable();
            $table->decimal('equity_min', 8, 4)->nullable();
            $table->decimal('equity_max', 8, 4)->nullable();
            $table->enum('company_stage', ['seed', 'series_a', 'series_b', 'series_c', 'ipo', 'established'])->nullable();
            $table->json('impact_categories')->nullable(); // For NGO/Impact jobs
            $table->string('clearance_level')->nullable(); // For government jobs
            $table->integer('project_duration_months')->nullable(); // For freelance
            $table->decimal('hourly_rate_min', 8, 2)->nullable();
            $table->decimal('hourly_rate_max', 8, 2)->nullable();
            $table->enum('client_rating_required', ['none', 'basic', 'excellent'])->default('none');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_attributes');
        Schema::dropIfExists('post_specializations');
        Schema::dropIfExists('job_specializations');
    }
};
