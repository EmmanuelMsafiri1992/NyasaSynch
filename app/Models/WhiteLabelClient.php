<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhiteLabelClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'type',
        'contact_email',
        'contact_name',
        'contact_phone',
        'description',
        'status',
        'trial_ends_at',
        'contract_starts_at',
        'contract_ends_at',
        'monthly_fee',
        'features_enabled',
        'limitations',
        'max_jobs',
        'max_companies',
        'max_users',
        'custom_branding',
        'custom_domain',
        'api_access',
        'analytics_config'
    ];

    protected $casts = [
        'features_enabled' => 'array',
        'limitations' => 'array',
        'analytics_config' => 'array',
        'monthly_fee' => 'decimal:2',
        'custom_branding' => 'boolean',
        'custom_domain' => 'boolean',
        'api_access' => 'boolean',
        'trial_ends_at' => 'date',
        'contract_starts_at' => 'date',
        'contract_ends_at' => 'date',
        'max_jobs' => 'integer',
        'max_companies' => 'integer',
        'max_users' => 'integer'
    ];

    public function branding(): HasOne
    {
        return $this->hasOne(WhiteLabelBranding::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(WhiteLabelSetting::class);
    }

    public function usage(): HasMany
    {
        return $this->hasMany(WhiteLabelUsage::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(WhiteLabelApiKey::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(WhiteLabelPage::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(WhiteLabelMenuItem::class);
    }

    public function emailTemplates(): HasMany
    {
        return $this->hasMany(WhiteLabelEmailTemplate::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(WhiteLabelDomain::class);
    }

    public function theme(): HasOne
    {
        return $this->hasOne(WhiteLabelTheme::class);
    }

    public function getUrlAttribute(): string
    {
        return match($this->type) {
            'domain' => 'https://' . $this->domain,
            'subdomain' => 'https://' . $this->slug . '.' . config('app.domain', 'localhost'),
            'path' => config('app.url') . '/' . $this->slug,
            default => config('app.url') . '/' . $this->slug
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'trial' => 'blue',
            'inactive' => 'gray',
            'suspended' => 'red',
            default => 'gray'
        };
    }

    public function getIsTrialAttribute(): bool
    {
        return $this->status === 'trial';
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    public function getTrialDaysLeftAttribute(): ?int
    {
        if (!$this->is_trial || !$this->trial_ends_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    public function getContractDaysLeftAttribute(): ?int
    {
        if (!$this->contract_ends_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->contract_ends_at, false));
    }

    public function hasFeature(string $feature): bool
    {
        $enabledFeatures = $this->features_enabled ?? [];
        return in_array($feature, $enabledFeatures);
    }

    public function getUsageLimit(string $type): ?int
    {
        return match($type) {
            'jobs' => $this->max_jobs,
            'companies' => $this->max_companies,
            'users' => $this->max_users,
            default => null
        };
    }

    public function getCurrentUsage(string $type): int
    {
        $today = now()->format('Y-m-d');

        return $this->usage()
            ->where('usage_date', $today)
            ->where('metric_type', $type)
            ->sum('metric_value');
    }

    public function getRemainingUsage(string $type): int
    {
        $limit = $this->getUsageLimit($type);
        if (!$limit) {
            return PHP_INT_MAX;
        }

        $current = $this->getCurrentUsage($type);
        return max(0, $limit - $current);
    }

    public function isOverLimit(string $type): bool
    {
        return $this->getRemainingUsage($type) <= 0;
    }

    public function getSetting(string $key, $default = null)
    {
        $setting = $this->settings()->where('setting_key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match($setting->setting_type) {
            'json' => json_decode($setting->setting_value, true),
            'boolean' => (bool) $setting->setting_value,
            'number' => (float) $setting->setting_value,
            'integer' => (int) $setting->setting_value,
            default => $setting->setting_value
        };
    }

    public function setSetting(string $key, $value, string $type = 'string', bool $isPublic = false): void
    {
        $settingValue = match($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value
        };

        $this->settings()->updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $settingValue,
                'setting_type' => $type,
                'is_public' => $isPublic
            ]
        );
    }

    public function getPublicSettings(): array
    {
        return $this->settings()
            ->where('is_public', true)
            ->get()
            ->mapWithKeys(function ($setting) {
                $value = match($setting->setting_type) {
                    'json' => json_decode($setting->setting_value, true),
                    'boolean' => (bool) $setting->setting_value,
                    'number' => (float) $setting->setting_value,
                    'integer' => (int) $setting->setting_value,
                    default => $setting->setting_value
                };

                return [$setting->setting_key => $value];
            })
            ->toArray();
    }

    public function recordUsage(string $metricType, int $value = 1, array $metadata = []): void
    {
        $today = now()->format('Y-m-d');

        $this->usage()->updateOrCreate(
            [
                'usage_date' => $today,
                'metric_type' => $metricType
            ],
            [
                'metric_value' => \DB::raw("metric_value + {$value}"),
                'metadata' => !empty($metadata) ? $metadata : null
            ]
        );
    }

    public function getUsageStats(int $days = 30): array
    {
        $startDate = now()->subDays($days)->format('Y-m-d');

        $usage = $this->usage()
            ->where('usage_date', '>=', $startDate)
            ->selectRaw('metric_type, SUM(metric_value) as total_value, MAX(usage_date) as latest_date')
            ->groupBy('metric_type')
            ->get()
            ->pluck('total_value', 'metric_type')
            ->toArray();

        return [
            'period_days' => $days,
            'start_date' => $startDate,
            'end_date' => now()->format('Y-m-d'),
            'metrics' => $usage,
            'limits' => [
                'jobs' => $this->max_jobs,
                'companies' => $this->max_companies,
                'users' => $this->max_users
            ]
        ];
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'trial']);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopeExpiringTrial($query, int $days = 7)
    {
        return $query->where('status', 'trial')
                    ->where('trial_ends_at', '<=', now()->addDays($days))
                    ->where('trial_ends_at', '>=', now());
    }

    public function scopeExpiringContract($query, int $days = 30)
    {
        return $query->where('status', 'active')
                    ->whereNotNull('contract_ends_at')
                    ->where('contract_ends_at', '<=', now()->addDays($days))
                    ->where('contract_ends_at', '>=', now());
    }

    public function scopeWithCustomDomain($query)
    {
        return $query->where('custom_domain', true)
                    ->whereNotNull('domain');
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'contract_starts_at' => now(),
            'trial_ends_at' => null
        ]);
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function extendTrial(int $days): void
    {
        $currentEnd = $this->trial_ends_at ?? now();
        $newEnd = $currentEnd->addDays($days);

        $this->update(['trial_ends_at' => $newEnd]);
    }

    public function extendContract(int $days): void
    {
        $currentEnd = $this->contract_ends_at ?? now();
        $newEnd = $currentEnd->addDays($days);

        $this->update(['contract_ends_at' => $newEnd]);
    }

    public function enableFeature(string $feature): void
    {
        $features = $this->features_enabled ?? [];

        if (!in_array($feature, $features)) {
            $features[] = $feature;
            $this->update(['features_enabled' => $features]);
        }
    }

    public function disableFeature(string $feature): void
    {
        $features = $this->features_enabled ?? [];

        $features = array_values(array_filter($features, fn($f) => $f !== $feature));
        $this->update(['features_enabled' => $features]);
    }
}