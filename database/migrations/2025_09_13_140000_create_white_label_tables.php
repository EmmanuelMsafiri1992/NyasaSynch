<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // White Label Clients table
        Schema::create('white_label_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Client company name
            $table->string('slug')->unique(); // URL slug for subdomain/path
            $table->string('domain')->nullable(); // Custom domain
            $table->enum('type', ['subdomain', 'path', 'domain'])->default('subdomain');
            $table->string('contact_email');
            $table->string('contact_name');
            $table->string('contact_phone')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'trial'])->default('trial');
            $table->date('trial_ends_at')->nullable();
            $table->date('contract_starts_at')->nullable();
            $table->date('contract_ends_at')->nullable();
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->json('features_enabled')->nullable(); // Enabled features
            $table->json('limitations')->nullable(); // Usage limitations
            $table->integer('max_jobs')->default(100);
            $table->integer('max_companies')->default(50);
            $table->integer('max_users')->default(1000);
            $table->boolean('custom_branding')->default(false);
            $table->boolean('custom_domain')->default(false);
            $table->boolean('api_access')->default(false);
            $table->json('analytics_config')->nullable(); // Analytics settings
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('domain');
        });

        // White Label Branding table
        Schema::create('white_label_branding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_label_client_id')->constrained()->onDelete('cascade');
            $table->string('site_name')->nullable();
            $table->string('site_tagline')->nullable();
            $table->text('site_description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('login_logo_url')->nullable();
            $table->string('email_logo_url')->nullable();
            $table->json('color_scheme')->nullable(); // Primary, secondary, accent colors
            $table->json('typography')->nullable(); // Font configurations
            $table->json('custom_css')->nullable(); // Custom CSS rules
            $table->json('footer_content')->nullable(); // Custom footer
            $table->json('email_templates')->nullable(); // Custom email templates
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->json('social_links')->nullable(); // Social media links
            $table->string('google_analytics_id')->nullable();
            $table->string('facebook_pixel_id')->nullable();
            $table->text('custom_head_code')->nullable(); // Custom head scripts
            $table->text('custom_body_code')->nullable(); // Custom body scripts
            $table->timestamps();
        });

        // White Label Settings table
        Schema::create('white_label_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_label_client_id')->constrained()->onDelete('cascade');
            $table->string('setting_key');
            $table->text('setting_value')->nullable();
            $table->string('setting_type')->default('string'); // string, json, boolean, number
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Can be accessed by frontend
            $table->timestamps();

            $table->unique(['white_label_client_id', 'setting_key'], 'wl_settings_unique');
            $table->index(['white_label_client_id', 'is_public']);
        });

        // White Label Usage Analytics table
        Schema::create('white_label_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_label_client_id')->constrained()->onDelete('cascade');
            $table->date('usage_date');
            $table->string('metric_type'); // jobs_posted, users_registered, page_views, api_calls
            $table->integer('metric_value')->default(0);
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();

            $table->unique(['white_label_client_id', 'usage_date', 'metric_type'], 'wl_usage_unique');
            $table->index(['white_label_client_id', 'usage_date']);
        });

        // White Label API Keys table
        Schema::create('white_label_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_label_client_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Key name/description
            $table->string('key_id')->unique(); // Public key identifier
            $table->string('key_secret'); // Encrypted secret key
            $table->json('permissions')->nullable(); // API permissions
            $table->json('rate_limits')->nullable(); // Rate limiting config
            $table->integer('requests_made')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['white_label_client_id', 'is_active']);
        });

        // White Label Pages table (Custom pages)
        Schema::create('white_label_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_label_client_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug');
            $table->longText('content');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->enum('type', ['page', 'policy', 'terms', 'about', 'help'])->default('page');
            $table->boolean('is_published')->default(false);
            $table->boolean('show_in_menu')->default(true);
            $table->integer('menu_order')->default(0);
            $table->string('template')->default('default'); // Template to use
            $table->json('custom_fields')->nullable(); // Additional page data
            $table->timestamps();

            $table->unique(['white_label_client_id', 'slug'], 'wl_pages_unique');
            $table->index(['white_label_client_id', 'is_published']);
        });

        // White Label Menu Items table
        Schema::create('white_label_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_label_client_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('url');
            $table->string('target')->default('_self'); // _self, _blank
            $table->string('icon')->nullable(); // Icon class/name
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->enum('menu_location', ['header', 'footer', 'sidebar'])->default('header');
            $table->foreignId('parent_id')->nullable()->constrained('white_label_menu_items')->onDelete('cascade');
            $table->json('visibility_rules')->nullable(); // When to show/hide
            $table->timestamps();

            $table->index(['white_label_client_id', 'menu_location', 'sort_order'], 'wl_menu_items_idx');
        });

        // White Label Email Templates table
        Schema::create('white_label_email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_label_client_id')->constrained()->onDelete('cascade');
            $table->string('template_key'); // welcome, job_alert, application_received, etc.
            $table->string('subject');
            $table->longText('html_content');
            $table->longText('text_content')->nullable();
            $table->json('variables')->nullable(); // Available template variables
            $table->boolean('is_active')->default(true);
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to')->nullable();
            $table->json('attachments')->nullable(); // Default attachments
            $table->timestamps();

            $table->unique(['white_label_client_id', 'template_key'], 'wl_email_templates_unique');
        });

        // White Label Domains table (for custom domains)
        Schema::create('white_label_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_label_client_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->enum('status', ['pending', 'verified', 'failed', 'inactive'])->default('pending');
            $table->string('ssl_status')->default('pending'); // pending, active, failed
            $table->json('dns_records')->nullable(); // Required DNS records
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('ssl_issued_at')->nullable();
            $table->string('cloudflare_zone_id')->nullable();
            $table->json('verification_errors')->nullable();
            $table->timestamps();

            $table->unique('domain');
            $table->index(['white_label_client_id', 'status']);
        });

        // White Label Theme Customizations table
        Schema::create('white_label_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('white_label_client_id')->constrained()->onDelete('cascade');
            $table->string('theme_name')->default('default');
            $table->json('layout_config')->nullable(); // Layout configurations
            $table->json('component_styles')->nullable(); // Component-specific styles
            $table->json('responsive_settings')->nullable(); // Mobile/tablet settings
            $table->longText('custom_css')->nullable();
            $table->longText('custom_js')->nullable();
            $table->json('widget_config')->nullable(); // Homepage widgets config
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('white_label_themes');
        Schema::dropIfExists('white_label_domains');
        Schema::dropIfExists('white_label_email_templates');
        Schema::dropIfExists('white_label_menu_items');
        Schema::dropIfExists('white_label_pages');
        Schema::dropIfExists('white_label_api_keys');
        Schema::dropIfExists('white_label_usage');
        Schema::dropIfExists('white_label_settings');
        Schema::dropIfExists('white_label_branding');
        Schema::dropIfExists('white_label_clients');
    }
};