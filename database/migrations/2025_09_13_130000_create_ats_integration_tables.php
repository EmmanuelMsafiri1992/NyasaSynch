<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ATS Connections table
        Schema::create('ats_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Connection name
            $table->enum('provider', ['workday', 'greenhouse', 'lever', 'bamboohr', 'successfactors', 'taleo', 'icims', 'jazz', 'bullhorn', 'jobvite']);
            $table->string('api_endpoint');
            $table->json('credentials'); // Encrypted API keys, tokens, etc.
            $table->json('configuration')->nullable(); // Custom mapping, filters, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_stats')->nullable(); // Last sync statistics
            $table->json('field_mapping')->nullable(); // Custom field mappings
            $table->timestamps();
        });

        // ATS Job Postings - Jobs synced from ATS systems
        Schema::create('ats_job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ats_connection_id')->constrained()->onDelete('cascade');
            $table->string('external_job_id'); // Job ID from ATS
            $table->string('title');
            $table->longText('description');
            $table->string('department')->nullable();
            $table->string('location');
            $table->enum('employment_type', ['full-time', 'part-time', 'contract', 'temporary', 'internship']);
            $table->enum('experience_level', ['entry-level', 'mid-level', 'senior', 'executive']);
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('salary_currency', 3)->default('USD');
            $table->json('requirements')->nullable(); // Skills, education, etc.
            $table->json('benefits')->nullable();
            $table->string('hiring_manager')->nullable();
            $table->string('recruiter')->nullable();
            $table->enum('status', ['active', 'paused', 'closed', 'draft']);
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('custom_fields')->nullable(); // ATS-specific fields
            $table->integer('applications_count')->default(0);
            $table->timestamp('last_updated_at')->nullable(); // From ATS
            $table->timestamps();

            $table->unique(['ats_connection_id', 'external_job_id']);
            $table->index(['status', 'posted_at']);
        });

        // ATS Candidates - Applicants from ATS systems
        Schema::create('ats_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ats_connection_id')->constrained()->onDelete('cascade');
            $table->string('external_candidate_id'); // Candidate ID from ATS
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->json('skills')->nullable();
            $table->json('education')->nullable();
            $table->json('experience')->nullable();
            $table->string('current_title')->nullable();
            $table->string('current_company')->nullable();
            $table->decimal('desired_salary', 10, 2)->nullable();
            $table->enum('availability', ['immediate', '2-weeks', '1-month', 'flexible']);
            $table->boolean('open_to_remote')->default(false);
            $table->json('custom_fields')->nullable(); // ATS-specific fields
            $table->timestamp('last_updated_at')->nullable(); // From ATS
            $table->timestamps();

            $table->unique(['ats_connection_id', 'external_candidate_id']);
            $table->index(['email', 'ats_connection_id']);
        });

        // ATS Applications - Job applications from ATS
        Schema::create('ats_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ats_job_posting_id')->constrained()->onDelete('cascade');
            $table->foreignId('ats_candidate_id')->constrained()->onDelete('cascade');
            $table->string('external_application_id'); // Application ID from ATS
            $table->enum('status', ['new', 'screening', 'interview', 'assessment', 'offer', 'hired', 'rejected', 'withdrawn']);
            $table->text('cover_letter')->nullable();
            $table->json('attachments')->nullable(); // Resume, portfolio files
            $table->json('questionnaire_responses')->nullable(); // Custom questions
            $table->decimal('offered_salary', 10, 2)->nullable();
            $table->timestamp('applied_at');
            $table->timestamp('status_updated_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->json('interview_notes')->nullable();
            $table->json('assessment_scores')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();

            $table->unique(['ats_job_posting_id', 'ats_candidate_id']);
            $table->index(['status', 'applied_at']);
        });

        // ATS Sync Logs - Track sync activities
        Schema::create('ats_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ats_connection_id')->constrained()->onDelete('cascade');
            $table->enum('sync_type', ['jobs', 'candidates', 'applications', 'full']);
            $table->enum('status', ['started', 'completed', 'failed']);
            $table->json('filters')->nullable(); // Sync filters applied
            $table->integer('records_processed')->default(0);
            $table->integer('records_created')->default(0);
            $table->integer('records_updated')->default(0);
            $table->integer('records_failed')->default(0);
            $table->json('errors')->nullable(); // Sync errors
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->decimal('duration_seconds', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['ats_connection_id', 'status']);
        });

        // ATS Webhooks - Handle real-time updates
        Schema::create('ats_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ats_connection_id')->constrained()->onDelete('cascade');
            $table->string('webhook_id')->unique(); // Unique webhook identifier
            $table->string('event_type'); // job_created, application_submitted, etc.
            $table->json('payload'); // Full webhook payload
            $table->enum('status', ['pending', 'processed', 'failed']);
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'received_at']);
        });

        // ATS Field Mappings - Custom field mapping configurations
        Schema::create('ats_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ats_connection_id')->constrained()->onDelete('cascade');
            $table->enum('entity_type', ['job', 'candidate', 'application']);
            $table->string('local_field'); // Our field name
            $table->string('ats_field'); // ATS field name/path
            $table->enum('field_type', ['string', 'number', 'boolean', 'date', 'array', 'object']);
            $table->json('transformation_rules')->nullable(); // Data transformation rules
            $table->boolean('is_required')->default(false);
            $table->string('default_value')->nullable();
            $table->timestamps();

            $table->unique(['ats_connection_id', 'entity_type', 'local_field'], 'ats_field_mapping_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ats_field_mappings');
        Schema::dropIfExists('ats_webhooks');
        Schema::dropIfExists('ats_sync_logs');
        Schema::dropIfExists('ats_applications');
        Schema::dropIfExists('ats_candidates');
        Schema::dropIfExists('ats_job_postings');
        Schema::dropIfExists('ats_connections');
    }
};