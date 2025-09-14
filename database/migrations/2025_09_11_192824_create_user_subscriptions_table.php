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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_tier_id')->constrained()->onDelete('cascade');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('amount_paid', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('auto_renew')->default(true);
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('pending');
            $table->json('features_used')->nullable(); // Track feature usage
            $table->integer('jobs_posted_count')->default(0);
            $table->integer('featured_posts_used')->default(0);
            $table->integer('resume_views_used')->default(0);
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
