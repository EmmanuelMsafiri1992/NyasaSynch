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
        // Career assessment quizzes
        Schema::create('career_assessments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('assessment_type', ['personality', 'skills', 'interests', 'values', 'comprehensive']);
            $table->json('questions'); // Array of question objects
            $table->json('scoring_algorithm'); // Rules for calculating results
            $table->json('result_categories'); // Possible outcomes/personality types
            $table->integer('estimated_duration')->default(10); // minutes
            $table->integer('total_questions');
            $table->boolean('is_active')->default(true);
            $table->integer('completion_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->timestamps();
        });

        // User assessment results
        Schema::create('user_assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assessment_id')->constrained('career_assessments')->onDelete('cascade');
            $table->json('answers'); // User's responses to questions
            $table->json('scores'); // Calculated scores by category
            $table->json('primary_result'); // Main personality type/category
            $table->json('secondary_results')->nullable(); // Additional traits
            $table->json('recommended_careers'); // Job matches based on results
            $table->json('skill_strengths'); // Identified strengths
            $table->json('development_areas'); // Areas for improvement
            $table->text('detailed_analysis')->nullable();
            $table->integer('completion_time_minutes');
            $table->decimal('user_rating', 3, 2)->nullable();
            $table->text('user_feedback')->nullable();
            $table->boolean('is_public')->default(false); // Allow sharing results
            $table->timestamps();

            $table->index(['user_id', 'assessment_id']);
        });

        // Career planning tools
        Schema::create('career_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('plan_name');
            $table->text('description')->nullable();
            $table->enum('plan_type', ['5_year', 'career_pivot', 'skill_development', 'promotion']);
            $table->json('current_situation'); // Current role, skills, goals
            $table->json('target_goals'); // Desired outcomes
            $table->json('milestones'); // Quarterly/yearly targets
            $table->json('action_items'); // Specific tasks and deadlines
            $table->json('skill_gaps'); // Skills to develop
            $table->json('education_goals'); // Courses, certifications needed
            $table->json('financial_projections'); // Salary growth expectations
            $table->date('target_completion_date');
            $table->enum('status', ['draft', 'active', 'completed', 'paused'])->default('draft');
            $table->integer('progress_percentage')->default(0);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('plan_type');
        });

        // Career plan milestones tracking
        Schema::create('career_plan_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_plan_id')->constrained('career_plans')->onDelete('cascade');
            $table->string('milestone_title');
            $table->text('description')->nullable();
            $table->date('target_date');
            $table->date('completed_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->json('success_criteria')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['career_plan_id', 'status']);
            $table->index('target_date');
        });

        // Interactive calculators data
        Schema::create('compensation_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('calculation_type'); // total_comp, negotiation_prep, offer_comparison
            $table->json('input_data'); // User-provided data for calculation
            $table->json('calculated_results'); // Final calculation results
            $table->json('recommendations'); // Actionable advice
            $table->json('market_comparisons')->nullable(); // Benchmark data
            $table->boolean('is_saved')->default(false);
            $table->string('calculation_name')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'calculation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_calculations');
        Schema::dropIfExists('career_plan_milestones');
        Schema::dropIfExists('career_plans');
        Schema::dropIfExists('user_assessment_results');
        Schema::dropIfExists('career_assessments');
    }
};
