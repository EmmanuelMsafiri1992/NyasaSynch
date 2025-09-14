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
        // Educational content library
        Schema::create('career_guides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->longText('content');
            $table->string('category'); // technology, healthcare, finance, marketing, education
            $table->string('subcategory')->nullable(); // software-development, data-science, etc.
            $table->string('difficulty_level'); // beginner, intermediate, advanced
            $table->integer('estimated_read_time')->nullable(); // minutes
            $table->string('featured_image')->nullable();
            $table->json('tags')->nullable();
            $table->json('career_paths')->nullable(); // Related career progression info
            $table->json('required_skills')->nullable();
            $table->json('salary_ranges')->nullable();
            $table->integer('view_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['category', 'subcategory', 'is_published']);
            $table->index(['is_featured', 'published_at']);
        });

        // Skills development resources
        Schema::create('skill_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->longText('content')->nullable();
            $table->enum('resource_type', ['article', 'video', 'course', 'tutorial', 'tool', 'book']);
            $table->string('external_url')->nullable();
            $table->string('skill_category'); // technical, soft_skills
            $table->string('skill_name'); // python, leadership, communication
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced']);
            $table->integer('estimated_duration')->nullable(); // minutes/hours
            $table->decimal('price', 8, 2)->nullable();
            $table->string('provider')->nullable(); // coursera, udemy, internal
            $table->json('prerequisites')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->string('thumbnail')->nullable();
            $table->integer('view_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->boolean('is_free')->default(true);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['skill_category', 'skill_name', 'is_published']);
            $table->index(['resource_type', 'difficulty_level']);
        });

        // User progress tracking
        Schema::create('user_learning_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('resource_id')->constrained('skill_resources')->onDelete('cascade');
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'bookmarked']);
            $table->integer('progress_percentage')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent_minutes')->default(0);
            $table->json('notes')->nullable();
            $table->decimal('user_rating', 3, 2)->nullable();
            $table->text('user_review')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'resource_id']);
            $table->index(['user_id', 'status']);
        });

        // Career guide ratings and reviews
        Schema::create('career_guide_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('career_guide_id')->constrained('career_guides')->onDelete('cascade');
            $table->decimal('rating', 3, 2);
            $table->text('review')->nullable();
            $table->boolean('is_helpful_vote')->default(false);
            $table->integer('helpful_votes')->default(0);
            $table->boolean('is_verified_professional')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'career_guide_id']);
            $table->index(['career_guide_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_guide_reviews');
        Schema::dropIfExists('user_learning_progress');
        Schema::dropIfExists('skill_resources');
        Schema::dropIfExists('career_guides');
    }
};
