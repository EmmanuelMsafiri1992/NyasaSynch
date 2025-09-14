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
        Schema::create('salary_data', function (Blueprint $table) {
            $table->id();
            $table->string('job_title');
            $table->string('normalized_title')->nullable(); // For matching similar titles
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable(); // startup, small, medium, large, enterprise
            $table->string('location_country');
            $table->string('location_state')->nullable();
            $table->string('location_city')->nullable();
            $table->decimal('cost_of_living_index', 5, 2)->nullable();
            
            // Experience levels
            $table->integer('years_experience_min')->default(0);
            $table->integer('years_experience_max')->nullable();
            
            // Salary data
            $table->decimal('salary_min', 12, 2);
            $table->decimal('salary_max', 12, 2);
            $table->decimal('salary_median', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->enum('salary_type', ['annual', 'monthly', 'hourly'])->default('annual');
            
            // Additional compensation
            $table->decimal('bonus_average', 12, 2)->nullable();
            $table->decimal('equity_percentage', 8, 4)->nullable();
            $table->json('benefits_data')->nullable(); // Health, vacation, etc.
            
            // Data quality and source
            $table->string('data_source')->nullable(); // survey, government, user_submitted
            $table->integer('sample_size')->default(1);
            $table->decimal('confidence_score', 5, 2)->default(100);
            $table->date('data_collected_at');
            $table->boolean('is_verified')->default(false);
            
            $table->timestamps();
            
            $table->index(['job_title', 'location_country', 'years_experience_min'], 'salary_job_location_exp_idx');
            $table->index(['normalized_title', 'location_city'], 'salary_title_city_idx');
        });

        // User salary submissions
        Schema::create('user_salary_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('job_title');
            $table->string('company_name')->nullable();
            $table->string('location_city');
            $table->string('location_country');
            $table->integer('years_experience');
            $table->decimal('annual_salary', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('bonus', 12, 2)->nullable();
            $table->text('additional_compensation')->nullable();
            $table->json('skills')->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        // Cost of living data
        Schema::create('cost_of_living', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->string('country');
            $table->decimal('index_score', 5, 2); // 100 = baseline (e.g., NYC)
            $table->decimal('rent_index', 5, 2)->nullable();
            $table->decimal('groceries_index', 5, 2)->nullable();
            $table->decimal('restaurant_index', 5, 2)->nullable();
            $table->decimal('purchasing_power_index', 5, 2)->nullable();
            $table->date('last_updated');
            $table->timestamps();
            
            $table->unique(['city', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_of_living');
        Schema::dropIfExists('user_salary_submissions');
        Schema::dropIfExists('salary_data');
    }
};
