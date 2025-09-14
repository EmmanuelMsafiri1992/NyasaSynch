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
        Schema::create('job_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 100); // Alert name for user reference
            $table->text('keywords')->nullable(); // Job keywords to match
            $table->json('categories')->nullable(); // Category IDs
            $table->json('locations')->nullable(); // Location IDs
            $table->string('salary_min')->nullable();
            $table->string('salary_max')->nullable();
            $table->string('job_type')->nullable(); // full-time, part-time, contract, etc.
            $table->enum('frequency', ['instant', 'daily', 'weekly'])->default('daily');
            $table->boolean('active')->default(true);
            $table->timestamp('last_sent')->nullable();
            $table->integer('matches_count')->default(0);
            $table->timestamps();
            
            $table->index(['user_id', 'active']);
            $table->index('frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_alerts');
    }
};
