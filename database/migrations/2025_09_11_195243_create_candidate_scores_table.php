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
        Schema::create('candidate_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Score components (out of 100 each)
            $table->decimal('profile_completion_score', 5, 2)->default(0);
            $table->decimal('activity_score', 5, 2)->default(0);
            $table->decimal('verification_score', 5, 2)->default(0);
            $table->decimal('response_rate_score', 5, 2)->default(0);
            $table->decimal('success_rate_score', 5, 2)->default(0);
            
            // Overall weighted score
            $table->decimal('total_score', 5, 2)->default(0);
            
            // Tracking metrics
            $table->integer('profile_completion_percentage')->default(0);
            $table->integer('days_active_last_30')->default(0);
            $table->integer('applications_sent_last_30')->default(0);
            $table->integer('messages_responded_24h')->default(0);
            $table->integer('total_messages_received')->default(0);
            $table->integer('interviews_attended')->default(0);
            $table->integer('jobs_hired_for')->default(0);
            
            // Verification flags
            $table->boolean('email_verified')->default(false);
            $table->boolean('phone_verified')->default(false);
            $table->boolean('linkedin_verified')->default(false);
            $table->boolean('education_verified')->default(false);
            $table->boolean('employment_verified')->default(false);
            
            // Score history
            $table->json('score_history')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            
            $table->timestamps();
            $table->unique('user_id');
        });

        // User activity tracking
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('activity_type'); // login, profile_update, application_sent, message_sent, etc.
            $table->json('activity_data')->nullable();
            $table->timestamp('activity_date');
            $table->timestamps();
            
            $table->index(['user_id', 'activity_type', 'activity_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
        Schema::dropIfExists('candidate_scores');
    }
};
