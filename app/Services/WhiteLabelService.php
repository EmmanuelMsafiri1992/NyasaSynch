<?php

namespace App\Services;

use App\Models\WhiteLabelClient;
use App\Models\WhiteLabelBranding;
use App\Models\WhiteLabelApiKey;
use App\Models\WhiteLabelDomain;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhiteLabelService
{
    public function createClient(array $data): WhiteLabelClient
    {
        $client = WhiteLabelClient::create($data);

        // Create default branding
        $this->createDefaultBranding($client);

        // Create default settings
        $this->createDefaultSettings($client);

        // Create default pages
        $this->createDefaultPages($client);

        // Create default menu items
        $this->createDefaultMenuItems($client);

        // Create default email templates
        $this->createDefaultEmailTemplates($client);

        // Create default theme
        $this->createDefaultTheme($client);

        return $client->load(['branding', 'settings', 'pages', 'theme']);
    }

    public function updateBranding(WhiteLabelClient $client, array $brandingData): WhiteLabelBranding
    {
        $branding = $client->branding()->updateOrCreate([], $brandingData);

        // Clear cache for this client
        $this->clearClientCache($client);

        return $branding;
    }

    public function generateApiKey(WhiteLabelClient $client, string $name, array $permissions = [], array $rateLimits = []): WhiteLabelApiKey
    {
        $keyPair = WhiteLabelApiKey::generateKeyPair();

        return $client->apiKeys()->create([
            'name' => $name,
            'key_id' => $keyPair['key_id'],
            'key_secret' => $keyPair['key_secret'],
            'permissions' => $permissions,
            'rate_limits' => $rateLimits,
            'is_active' => true
        ]);
    }

    public function verifyDomain(WhiteLabelClient $client, string $domain): WhiteLabelDomain
    {
        $domainModel = $client->domains()->updateOrCreate(
            ['domain' => $domain],
            ['status' => 'pending']
        );

        // Perform DNS verification
        $this->performDnsVerification($domainModel);

        return $domainModel;
    }

    public function getClientByRequest($request): ?WhiteLabelClient
    {
        $host = $request->getHost();
        $path = trim($request->getPathInfo(), '/');

        // Check for custom domain
        $client = WhiteLabelClient::where('domain', $host)
            ->where('type', 'domain')
            ->active()
            ->first();

        if ($client) {
            return $client;
        }

        // Check for subdomain
        $subdomain = explode('.', $host)[0];
        $client = WhiteLabelClient::where('slug', $subdomain)
            ->where('type', 'subdomain')
            ->active()
            ->first();

        if ($client) {
            return $client;
        }

        // Check for path-based
        $pathSegments = explode('/', $path);
        if (!empty($pathSegments[0])) {
            $client = WhiteLabelClient::where('slug', $pathSegments[0])
                ->where('type', 'path')
                ->active()
                ->first();

            if ($client) {
                return $client;
            }
        }

        return null;
    }

    public function getClientConfiguration(WhiteLabelClient $client): array
    {
        $cacheKey = "white_label_config_{$client->id}";

        return Cache::remember($cacheKey, 3600, function () use ($client) {
            $config = [
                'client' => $client->toArray(),
                'branding' => $client->branding?->toArray() ?? [],
                'settings' => $client->getPublicSettings(),
                'theme' => $client->theme?->toArray() ?? [],
                'menu_items' => $client->menuItems()
                    ->active()
                    ->topLevel()
                    ->with('children')
                    ->ordered()
                    ->get()
                    ->toArray(),
                'features' => $client->features_enabled ?? [],
                'limitations' => $client->limitations ?? [],
                'usage_stats' => $client->getUsageStats(30)
            ];

            return $config;
        });
    }

    public function recordUsage(WhiteLabelClient $client, string $metricType, int $value = 1, array $metadata = []): void
    {
        $client->recordUsage($metricType, $value, $metadata);

        // Check if over limits and take action
        if ($client->isOverLimit($metricType)) {
            $this->handleUsageOverLimit($client, $metricType);
        }
    }

    public function renderEmailTemplate(WhiteLabelClient $client, string $templateKey, array $data = []): ?array
    {
        $template = $client->emailTemplates()
            ->active()
            ->byKey($templateKey)
            ->first();

        if (!$template) {
            return null;
        }

        // Add client branding data to template variables
        $brandingData = $client->branding ? [
            'site_name' => $client->branding->site_name ?? $client->name,
            'site_url' => $client->url,
            'logo_url' => $client->branding->logo_url,
            'primary_color' => $client->branding->primary_color,
            'support_email' => $client->contact_email
        ] : [];

        return $template->render(array_merge($data, $brandingData));
    }

    public function getAvailableFeatures(): array
    {
        return [
            'job_posting' => 'Job Posting',
            'resume_database' => 'Resume Database Access',
            'company_profiles' => 'Company Profiles',
            'advanced_search' => 'Advanced Search Filters',
            'analytics_dashboard' => 'Analytics Dashboard',
            'api_access' => 'API Access',
            'custom_branding' => 'Custom Branding',
            'custom_domain' => 'Custom Domain',
            'email_integration' => 'Email Integration',
            'ats_integration' => 'ATS Integration',
            'job_alerts' => 'Job Alerts',
            'application_tracking' => 'Application Tracking',
            'candidate_scoring' => 'Candidate Scoring',
            'salary_insights' => 'Salary Insights',
            'career_tools' => 'Career Assessment Tools',
            'learning_platform' => 'Learning Platform',
            'messaging_system' => 'Messaging System',
            'video_interviews' => 'Video Interviews',
            'background_checks' => 'Background Check Integration',
            'multi_language' => 'Multi-language Support',
            'white_label_mobile' => 'White Label Mobile App'
        ];
    }

    public function exportClientData(WhiteLabelClient $client): array
    {
        return [
            'client' => $client->toArray(),
            'branding' => $client->branding?->toArray(),
            'settings' => $client->settings->toArray(),
            'pages' => $client->pages->toArray(),
            'menu_items' => $client->menuItems->toArray(),
            'email_templates' => $client->emailTemplates->toArray(),
            'api_keys' => $client->apiKeys->makeHidden(['key_secret'])->toArray(),
            'domains' => $client->domains->toArray(),
            'theme' => $client->theme?->toArray(),
            'usage_stats' => $client->getUsageStats(365)
        ];
    }

    private function createDefaultBranding(WhiteLabelClient $client): void
    {
        $client->branding()->create([
            'site_name' => $client->name,
            'site_tagline' => 'Connecting talent with opportunity',
            'site_description' => 'Find your next career opportunity or hire top talent.',
            'color_scheme' => [
                'primary' => '#007bff',
                'secondary' => '#6c757d',
                'accent' => '#28a745',
                'success' => '#28a745',
                'info' => '#17a2b8',
                'warning' => '#ffc107',
                'danger' => '#dc3545',
                'light' => '#f8f9fa',
                'dark' => '#343a40'
            ],
            'typography' => [
                'primary' => 'Inter, system-ui, sans-serif',
                'secondary' => 'Georgia, serif',
                'monospace' => 'Monaco, Consolas, monospace'
            ],
            'meta_title' => $client->name . ' - Job Board',
            'meta_description' => 'Find job opportunities and hire talent on ' . $client->name,
            'social_links' => []
        ]);
    }

    private function createDefaultSettings(WhiteLabelClient $client): void
    {
        $defaultSettings = [
            'jobs_per_page' => ['value' => '20', 'type' => 'integer', 'public' => true],
            'allow_guest_posting' => ['value' => '0', 'type' => 'boolean', 'public' => true],
            'require_email_verification' => ['value' => '1', 'type' => 'boolean', 'public' => false],
            'default_job_duration' => ['value' => '30', 'type' => 'integer', 'public' => false],
            'enable_company_reviews' => ['value' => '1', 'type' => 'boolean', 'public' => true],
            'enable_salary_display' => ['value' => '1', 'type' => 'boolean', 'public' => true],
            'currency' => ['value' => 'USD', 'type' => 'string', 'public' => true],
            'timezone' => ['value' => 'UTC', 'type' => 'string', 'public' => true],
            'date_format' => ['value' => 'Y-m-d', 'type' => 'string', 'public' => true]
        ];

        foreach ($defaultSettings as $key => $config) {
            $client->setSetting($key, $config['value'], $config['type'], $config['public']);
        }
    }

    private function createDefaultPages(WhiteLabelClient $client): void
    {
        $defaultPages = [
            [
                'title' => 'About Us',
                'slug' => 'about',
                'content' => '<p>Welcome to ' . $client->name . ', your premier destination for career opportunities.</p>',
                'type' => 'about',
                'is_published' => true,
                'show_in_menu' => true,
                'menu_order' => 1
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<p>This privacy policy describes how we collect, use, and protect your personal information.</p>',
                'type' => 'policy',
                'is_published' => true,
                'show_in_menu' => true,
                'menu_order' => 2
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => '<p>These terms govern your use of our platform.</p>',
                'type' => 'terms',
                'is_published' => true,
                'show_in_menu' => true,
                'menu_order' => 3
            ]
        ];

        foreach ($defaultPages as $pageData) {
            $client->pages()->create($pageData);
        }
    }

    private function createDefaultMenuItems(WhiteLabelClient $client): void
    {
        $menuItems = [
            [
                'title' => 'Jobs',
                'url' => '/jobs',
                'menu_location' => 'header',
                'sort_order' => 1
            ],
            [
                'title' => 'Companies',
                'url' => '/companies',
                'menu_location' => 'header',
                'sort_order' => 2
            ],
            [
                'title' => 'About',
                'url' => '/page/about',
                'menu_location' => 'header',
                'sort_order' => 3
            ],
            [
                'title' => 'Privacy Policy',
                'url' => '/page/privacy-policy',
                'menu_location' => 'footer',
                'sort_order' => 1
            ],
            [
                'title' => 'Terms of Service',
                'url' => '/page/terms-of-service',
                'menu_location' => 'footer',
                'sort_order' => 2
            ]
        ];

        foreach ($menuItems as $itemData) {
            $client->menuItems()->create($itemData);
        }
    }

    private function createDefaultEmailTemplates(WhiteLabelClient $client): void
    {
        $templates = [
            [
                'template_key' => 'welcome',
                'subject' => 'Welcome to {{site_name}}!',
                'html_content' => '<h1>Welcome {{user_name}}!</h1><p>Thank you for joining {{site_name}}. We\'re excited to help you find your next career opportunity.</p>',
                'variables' => ['user_name', 'site_name', 'site_url'],
                'from_name' => $client->name
            ],
            [
                'template_key' => 'job_alert',
                'subject' => 'New Job Opportunities - {{site_name}}',
                'html_content' => '<h2>New Jobs Matching Your Criteria</h2><p>We found {{job_count}} new jobs that might interest you.</p>',
                'variables' => ['user_name', 'job_count', 'jobs_list', 'site_name'],
                'from_name' => $client->name
            ],
            [
                'template_key' => 'application_received',
                'subject' => 'Application Received - {{job_title}}',
                'html_content' => '<h2>Application Received</h2><p>Thank you for applying to {{job_title}} at {{company_name}}.</p>',
                'variables' => ['user_name', 'job_title', 'company_name', 'application_date'],
                'from_name' => $client->name
            ]
        ];

        foreach ($templates as $templateData) {
            $client->emailTemplates()->create($templateData);
        }
    }

    private function createDefaultTheme(WhiteLabelClient $client): void
    {
        $client->theme()->create([
            'theme_name' => 'default',
            'layout_config' => [
                'header_type' => 'default',
                'sidebar_position' => 'left',
                'footer_style' => 'simple'
            ],
            'component_styles' => [
                'header' => [
                    'background-color' => 'var(--color-primary)',
                    'color' => 'white'
                ],
                'job-card' => [
                    'border' => '1px solid #e0e0e0',
                    'border-radius' => '8px',
                    'padding' => '16px'
                ]
            ],
            'responsive_settings' => [
                '768px' => [
                    '.container' => ['padding' => '0 16px']
                ]
            ],
            'widget_config' => [
                'homepage' => [
                    'hero' => ['enabled' => true],
                    'featured_jobs' => ['enabled' => true, 'count' => 6],
                    'categories' => ['enabled' => true],
                    'stats' => ['enabled' => true]
                ]
            ]
        ]);
    }

    private function performDnsVerification(WhiteLabelDomain $domain): void
    {
        try {
            // Check CNAME record
            $cnameRecords = dns_get_record($domain->domain, DNS_CNAME);
            $txtRecords = dns_get_record('_verification.' . $domain->domain, DNS_TXT);

            $expectedCname = config('app.domain', 'yourdomain.com');
            $expectedTxt = 'nyasajob-verification=' . $domain->getVerificationToken();

            $cnameValid = collect($cnameRecords)->contains('target', $expectedCname);
            $txtValid = collect($txtRecords)->contains(function ($record) use ($expectedTxt) {
                return str_contains($record['txt'], $expectedTxt);
            });

            if ($cnameValid && $txtValid) {
                $domain->markAsVerified();
                $this->setupSSL($domain);
            } else {
                $errors = [];
                if (!$cnameValid) {
                    $errors[] = 'CNAME record not found or incorrect';
                }
                if (!$txtValid) {
                    $errors[] = 'TXT verification record not found';
                }
                $domain->markAsFailed($errors);
            }
        } catch (\Exception $e) {
            $domain->markAsFailed(['DNS lookup failed: ' . $e->getMessage()]);
            Log::error('Domain verification failed', [
                'domain' => $domain->domain,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function setupSSL(WhiteLabelDomain $domain): void
    {
        // In a real implementation, this would integrate with Cloudflare, Let's Encrypt, etc.
        // For now, we'll simulate SSL setup
        $domain->update(['ssl_status' => 'pending']);

        // Simulate successful SSL setup
        $domain->activateSSL();
    }

    private function handleUsageOverLimit(WhiteLabelClient $client, string $metricType): void
    {
        // Log the limit breach
        Log::warning('White label client over usage limit', [
            'client_id' => $client->id,
            'metric_type' => $metricType,
            'limit' => $client->getUsageLimit($metricType),
            'current' => $client->getCurrentUsage($metricType)
        ]);

        // Could implement notifications, temporary suspensions, etc.
    }

    private function clearClientCache(WhiteLabelClient $client): void
    {
        Cache::forget("white_label_config_{$client->id}");
    }
}