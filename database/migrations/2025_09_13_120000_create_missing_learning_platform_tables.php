<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User course enrollments (skip if exists)
        if (!Schema::hasTable('user_course_enrollments')) {
            Schema::create('user_course_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->timestamp('enrolled_at');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->integer('progress_percentage')->default(0);
                $table->json('completed_modules')->nullable();
                $table->integer('time_spent_minutes')->default(0);
                $table->decimal('current_score', 5, 2)->default(0);
                $table->boolean('certificate_earned')->default(false);
                $table->timestamps();

                $table->unique(['user_id', 'course_id']);
            });
        }

        // User progress tracking for modules (skip if exists)
        if (!Schema::hasTable('user_module_progress')) {
            Schema::create('user_module_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_module_id')->constrained()->onDelete('cascade');
                $table->enum('status', ['not_started', 'in_progress', 'completed']);
                $table->integer('time_spent_minutes')->default(0);
                $table->json('answers')->nullable();
                $table->decimal('score', 5, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'course_module_id']);
            });
        }

        // Coding workspace/IDE sessions
        if (!Schema::hasTable('coding_workspaces')) {
            Schema::create('coding_workspaces', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_module_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('workspace_name');
                $table->enum('language', ['javascript', 'python', 'java', 'php', 'html_css', 'react', 'node', 'sql']);
                $table->longText('code');
                $table->json('files')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_shared')->default(false);
                $table->string('share_token')->nullable();
                $table->json('execution_results')->nullable();
                $table->timestamp('last_run_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'language']);
            });
        }

        // Course reviews and ratings
        if (!Schema::hasTable('course_reviews')) {
            Schema::create('course_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->integer('rating');
                $table->text('review')->nullable();
                $table->boolean('is_verified_purchase')->default(false);
                $table->boolean('is_published')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'course_id']);
            });
        }

        // Learning paths/tracks
        if (!Schema::hasTable('learning_paths')) {
            Schema::create('learning_paths', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description');
                $table->string('category');
                $table->enum('level', ['beginner', 'intermediate', 'advanced']);
                $table->json('course_ids');
                $table->integer('total_duration_hours')->default(0);
                $table->string('thumbnail_url')->nullable();
                $table->boolean('is_published')->default(false);
                $table->integer('enrolled_count')->default(0);
                $table->timestamps();
            });
        }

        // User learning path progress
        if (!Schema::hasTable('user_learning_paths')) {
            Schema::create('user_learning_paths', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('learning_path_id')->constrained()->onDelete('cascade');
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->integer('progress_percentage')->default(0);
                $table->json('completed_courses')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'learning_path_id']);
            });
        }

        // Certificates issued
        if (!Schema::hasTable('certificates')) {
            Schema::create('certificates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('learning_path_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('certificate_id')->unique();
                $table->string('title');
                $table->text('description');
                $table->decimal('score', 5, 2)->nullable();
                $table->string('instructor_name');
                $table->timestamp('issued_at');
                $table->timestamp('expires_at')->nullable();
                $table->json('verification_data');
                $table->string('pdf_url')->nullable();
                $table->timestamps();
            });
        }

        // Skill assessments
        if (!Schema::hasTable('skill_assessments')) {
            Schema::create('skill_assessments', function (Blueprint $table) {
                $table->id();
                $table->string('skill_name');
                $table->text('description');
                $table->json('questions');
                $table->integer('time_limit_minutes')->default(60);
                $table->integer('passing_score')->default(70);
                $table->enum('difficulty', ['beginner', 'intermediate', 'advanced']);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // User skill assessment results
        if (!Schema::hasTable('user_skill_assessments')) {
            Schema::create('user_skill_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('skill_assessment_id')->constrained()->onDelete('cascade');
                $table->json('answers');
                $table->decimal('score', 5, 2);
                $table->integer('time_taken_minutes');
                $table->boolean('passed');
                $table->json('detailed_results');
                $table->timestamp('completed_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skill_assessments');
        Schema::dropIfExists('skill_assessments');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('user_learning_paths');
        Schema::dropIfExists('learning_paths');
        Schema::dropIfExists('course_reviews');
        Schema::dropIfExists('coding_workspaces');
        Schema::dropIfExists('user_module_progress');
        Schema::dropIfExists('user_course_enrollments');
    }
};