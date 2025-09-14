<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class DashboardMetricsCache extends Model
{
    protected $table = 'dashboard_metrics_cache';

    protected $fillable = [
        'metric_key',
        'time_period',
        'metric_value',
        'calculation_date',
        'last_updated',
        'cache_duration_minutes',
        'filters_applied'
    ];

    protected $casts = [
        'metric_value' => 'array',
        'calculation_date' => 'date',
        'last_updated' => 'datetime',
        'filters_applied' => 'array'
    ];

    // Scopes
    public function scopeByMetric(Builder $query, string $metricKey): Builder
    {
        return $query->where('metric_key', $metricKey);
    }

    public function scopeByPeriod(Builder $query, string $timePeriod): Builder
    {
        return $query->where('time_period', $timePeriod);
    }

    public function scopeFresh(Builder $query): Builder
    {
        return $query->where('last_updated', '>', function ($subQuery) {
            $subQuery->selectRaw('DATE_SUB(NOW(), INTERVAL cache_duration_minutes MINUTE)');
        });
    }

    public function scopeStale(Builder $query): Builder
    {
        return $query->where('last_updated', '<=', function ($subQuery) {
            $subQuery->selectRaw('DATE_SUB(NOW(), INTERVAL cache_duration_minutes MINUTE)');
        });
    }

    // Methods
    public function isStale(): bool
    {
        return $this->last_updated->addMinutes($this->cache_duration_minutes) < now();
    }

    public function refresh(): void
    {
        $this->update(['last_updated' => now()]);
    }

    // Static methods
    public static function get(string $metricKey, string $timePeriod = 'today', array $filters = []): ?array
    {
        $cache = self::byMetric($metricKey)
            ->byPeriod($timePeriod)
            ->where('calculation_date', today())
            ->when(!empty($filters), function ($query) use ($filters) {
                $query->where('filters_applied', json_encode($filters));
            })
            ->first();

        if (!$cache || $cache->isStale()) {
            return null;
        }

        return $cache->metric_value;
    }

    public static function set(
        string $metricKey,
        array $metricValue,
        string $timePeriod = 'today',
        int $cacheDurationMinutes = 60,
        array $filters = []
    ): self {
        return self::updateOrCreate([
            'metric_key' => $metricKey,
            'time_period' => $timePeriod,
            'calculation_date' => today(),
            'filters_applied' => empty($filters) ? null : $filters
        ], [
            'metric_value' => $metricValue,
            'last_updated' => now(),
            'cache_duration_minutes' => $cacheDurationMinutes
        ]);
    }

    public static function getOrCalculate(
        string $metricKey,
        string $timePeriod,
        callable $calculator,
        int $cacheDurationMinutes = 60,
        array $filters = []
    ): array {
        $cached = self::get($metricKey, $timePeriod, $filters);

        if ($cached !== null) {
            return $cached;
        }

        $calculated = $calculator();
        self::set($metricKey, $calculated, $timePeriod, $cacheDurationMinutes, $filters);

        return $calculated;
    }

    public static function clearStale(): int
    {
        return self::stale()->delete();
    }

    public static function clearAll(): int
    {
        return self::query()->delete();
    }

    public static function clearByMetric(string $metricKey): int
    {
        return self::byMetric($metricKey)->delete();
    }

    // Predefined metric calculators
    public static function getTotalUsers(string $timePeriod = 'today'): array
    {
        return self::getOrCalculate('total_users', $timePeriod, function () use ($timePeriod) {
            $query = User::query();

            switch ($timePeriod) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'all':
                    // No filter
                    break;
            }

            return [
                'count' => $query->count(),
                'timestamp' => now()->toISOString()
            ];
        });
    }

    public static function getActiveJobs(string $timePeriod = 'today'): array
    {
        return self::getOrCalculate('active_jobs', $timePeriod, function () {
            return [
                'count' => Post::count(),
                'timestamp' => now()->toISOString()
            ];
        });
    }

    public static function getRevenue(string $timePeriod = 'month'): array
    {
        return self::getOrCalculate('revenue', $timePeriod, function () use ($timePeriod) {
            $query = \App\Models\RevenueAnalytics::query();

            switch ($timePeriod) {
                case 'today':
                    $query->where('revenue_date', today());
                    break;
                case 'week':
                    $query->whereBetween('revenue_date', [
                        now()->startOfWeek()->toDateString(),
                        now()->endOfWeek()->toDateString()
                    ]);
                    break;
                case 'month':
                    $query->whereMonth('revenue_date', now()->month)
                          ->whereYear('revenue_date', now()->year);
                    break;
            }

            $total = $query->sum('amount');
            $count = $query->count();

            return [
                'total' => $total,
                'count' => $count,
                'average' => $count > 0 ? $total / $count : 0,
                'timestamp' => now()->toISOString()
            ];
        });
    }

    public static function getApplicationStats(string $timePeriod = 'today'): array
    {
        return self::getOrCalculate('application_stats', $timePeriod, function () use ($timePeriod) {
            $query = AnalyticsEvent::byEventType('job_application');

            switch ($timePeriod) {
                case 'today':
                    $query->today();
                    break;
                case 'week':
                    $query->thisWeek();
                    break;
                case 'month':
                    $query->thisMonth();
                    break;
            }

            $total = $query->count();
            $unique_users = $query->distinct('user_id')->count();

            return [
                'total_applications' => $total,
                'unique_applicants' => $unique_users,
                'applications_per_user' => $unique_users > 0 ? $total / $unique_users : 0,
                'timestamp' => now()->toISOString()
            ];
        });
    }

    public static function getTrafficSources(string $timePeriod = 'week'): array
    {
        return self::getOrCalculate('traffic_sources', $timePeriod, function () use ($timePeriod) {
            $query = AnalyticsEvent::byEventType('page_view');

            switch ($timePeriod) {
                case 'today':
                    $query->today();
                    break;
                case 'week':
                    $query->thisWeek();
                    break;
                case 'month':
                    $query->thisMonth();
                    break;
            }

            $direct = $query->clone()->whereNull('referrer_url')->count();
            $social = $query->clone()->where('referrer_url', 'like', '%facebook%')
                           ->orWhere('referrer_url', 'like', '%twitter%')
                           ->orWhere('referrer_url', 'like', '%linkedin%')
                           ->count();
            $search = $query->clone()->where('referrer_url', 'like', '%google%')
                           ->orWhere('referrer_url', 'like', '%bing%')
                           ->count();
            $other = $query->count() - $direct - $social - $search;

            return [
                'direct' => $direct,
                'social' => $social,
                'search' => $search,
                'other' => $other,
                'total' => $query->count(),
                'timestamp' => now()->toISOString()
            ];
        });
    }

    public static function getTopSearchTerms(string $timePeriod = 'week', int $limit = 10): array
    {
        return self::getOrCalculate('top_search_terms', $timePeriod, function () use ($timePeriod, $limit) {
            $query = \App\Models\SearchAnalytics::query();

            switch ($timePeriod) {
                case 'today':
                    $query->where('search_date', today());
                    break;
                case 'week':
                    $query->whereBetween('search_date', [
                        now()->startOfWeek()->toDateString(),
                        now()->endOfWeek()->toDateString()
                    ]);
                    break;
                case 'month':
                    $query->whereMonth('search_date', now()->month)
                          ->whereYear('search_date', now()->year);
                    break;
            }

            $results = $query->selectRaw('search_query, SUM(search_count) as total_searches')
                           ->groupBy('search_query')
                           ->orderByDesc('total_searches')
                           ->limit($limit)
                           ->pluck('total_searches', 'search_query')
                           ->toArray();

            return [
                'terms' => $results,
                'timestamp' => now()->toISOString()
            ];
        }, 30); // Cache for 30 minutes
    }
}