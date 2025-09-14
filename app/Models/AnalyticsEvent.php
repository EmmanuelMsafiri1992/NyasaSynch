<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AnalyticsEvent extends Model
{
    protected $fillable = [
        'event_type',
        'user_id',
        'session_id',
        'user_agent',
        'ip_address',
        'country_code',
        'city',
        'device_type',
        'browser',
        'os',
        'referrer_url',
        'current_url',
        'event_data',
        'event_timestamp'
    ];

    protected $casts = [
        'event_data' => 'array',
        'event_timestamp' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByEventType(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('event_timestamp', [$startDate, $endDate]);
    }

    public function scopeByCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeByDevice(Builder $query, string $deviceType): Builder
    {
        return $query->where('device_type', $deviceType);
    }

    public function scopeWithUser(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeAnonymous(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('event_timestamp', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('event_timestamp', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('event_timestamp', now()->month)
                    ->whereYear('event_timestamp', now()->year);
    }

    // Static methods for common tracking
    public static function track(string $eventType, array $data = [], ?User $user = null): self
    {
        $request = request();

        return self::create([
            'event_type' => $eventType,
            'user_id' => $user?->id,
            'session_id' => $request->session()?->getId(),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'country_code' => self::getCountryFromIp($request->ip()),
            'city' => self::getCityFromIp($request->ip()),
            'device_type' => self::getDeviceType($request->userAgent()),
            'browser' => self::getBrowser($request->userAgent()),
            'os' => self::getOS($request->userAgent()),
            'referrer_url' => $request->header('referer'),
            'current_url' => $request->fullUrl(),
            'event_data' => $data,
            'event_timestamp' => now()
        ]);
    }

    public static function trackPageView(string $page, ?User $user = null, array $additionalData = []): self
    {
        return self::track('page_view', array_merge([
            'page' => $page,
            'timestamp' => now()->toISOString()
        ], $additionalData), $user);
    }

    public static function trackJobView(int $jobId, ?User $user = null): self
    {
        return self::track('job_view', [
            'job_id' => $jobId,
            'timestamp' => now()->toISOString()
        ], $user);
    }

    public static function trackJobApplication(int $jobId, User $user): self
    {
        return self::track('job_application', [
            'job_id' => $jobId,
            'user_id' => $user->id,
            'timestamp' => now()->toISOString()
        ], $user);
    }

    public static function trackSearch(string $query, array $filters = [], ?User $user = null, int $resultsCount = 0): self
    {
        return self::track('search', [
            'query' => $query,
            'filters' => $filters,
            'results_count' => $resultsCount,
            'timestamp' => now()->toISOString()
        ], $user);
    }

    public static function trackConversion(string $conversionType, array $data = [], ?User $user = null): self
    {
        return self::track('conversion', array_merge([
            'conversion_type' => $conversionType,
            'timestamp' => now()->toISOString()
        ], $data), $user);
    }

    // Helper methods
    private static function getCountryFromIp(string $ip): ?string
    {
        // This would integrate with a GeoIP service
        // For now, return null or implement with your preferred service
        return null;
    }

    private static function getCityFromIp(string $ip): ?string
    {
        // This would integrate with a GeoIP service
        return null;
    }

    private static function getDeviceType(string $userAgent): string
    {
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            if (preg_match('/iPad/', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }
        return 'desktop';
    }

    private static function getBrowser(string $userAgent): string
    {
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'Opera') !== false) {
            return 'Opera';
        }
        return 'Other';
    }

    private static function getOS(string $userAgent): string
    {
        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            return 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            return 'iOS';
        }
        return 'Other';
    }

    // Aggregation methods
    public static function getEventCounts(string $eventType, Carbon $startDate, Carbon $endDate, string $groupBy = 'day'): array
    {
        $dateFormat = match($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d'
        };

        return self::selectRaw("DATE_FORMAT(event_timestamp, '$dateFormat') as period, COUNT(*) as count")
            ->byEventType($eventType)
            ->byDateRange($startDate, $endDate)
            ->groupByRaw("DATE_FORMAT(event_timestamp, '$dateFormat')")
            ->orderBy('period')
            ->pluck('count', 'period')
            ->toArray();
    }

    public static function getTopPages(Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        return self::selectRaw('JSON_UNQUOTE(JSON_EXTRACT(event_data, "$.page")) as page, COUNT(*) as views')
            ->byEventType('page_view')
            ->byDateRange($startDate, $endDate)
            ->whereNotNull('event_data')
            ->groupBy('page')
            ->orderByDesc('views')
            ->limit($limit)
            ->pluck('views', 'page')
            ->toArray();
    }

    public static function getDeviceBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        return self::selectRaw('device_type, COUNT(*) as count')
            ->byDateRange($startDate, $endDate)
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();
    }

    public static function getCountryBreakdown(Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        return self::selectRaw('country_code, COUNT(*) as count')
            ->byDateRange($startDate, $endDate)
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('count', 'country_code')
            ->toArray();
    }
}