<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class JobPerformanceMetric extends Model
{
    protected $fillable = [
        'post_id',
        'metric_date',
        'views_count',
        'clicks_count',
        'applications_count',
        'saves_count',
        'shares_count',
        'click_through_rate',
        'application_rate',
        'avg_time_on_page',
        'traffic_sources',
        'device_breakdown',
        'location_breakdown'
    ];

    protected $casts = [
        'metric_date' => 'date',
        'click_through_rate' => 'decimal:4',
        'application_rate' => 'decimal:4',
        'avg_time_on_page' => 'decimal:2',
        'traffic_sources' => 'array',
        'device_breakdown' => 'array',
        'location_breakdown' => 'array'
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // Scopes
    public function scopeForJob(Builder $query, int $postId): Builder
    {
        return $query->where('post_id', $postId);
    }

    public function scopeByDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->where('metric_date', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('metric_date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString()
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('metric_date', now()->month)
                    ->whereYear('metric_date', now()->year);
    }

    public function scopeWithHighPerformance(Builder $query, int $minApplications = 5): Builder
    {
        return $query->where('applications_count', '>=', $minApplications);
    }

    // Methods
    public function updateMetrics(): void
    {
        $this->calculateRates();
        $this->save();
    }

    private function calculateRates(): void
    {
        if ($this->views_count > 0) {
            $this->click_through_rate = ($this->clicks_count / $this->views_count) * 100;
            $this->application_rate = ($this->applications_count / $this->views_count) * 100;
        }
    }

    public function getPerformanceScore(): float
    {
        // Calculate a composite performance score (0-100)
        $viewsWeight = 0.2;
        $ctrWeight = 0.3;
        $applicationWeight = 0.5;

        $viewsScore = min(($this->views_count / 100) * 100, 100); // Normalize to max 100 views
        $ctrScore = $this->click_through_rate * 10; // CTR * 10 (max realistic CTR ~10%)
        $applicationScore = $this->application_rate * 20; // Application rate * 20

        return ($viewsScore * $viewsWeight) +
               ($ctrScore * $ctrWeight) +
               ($applicationScore * $applicationWeight);
    }

    public function isHighPerforming(): bool
    {
        return $this->getPerformanceScore() >= 70;
    }

    public function getTopTrafficSource(): ?string
    {
        if (!$this->traffic_sources) {
            return null;
        }

        return array_keys($this->traffic_sources, max($this->traffic_sources))[0] ?? null;
    }

    public function getTopDevice(): ?string
    {
        if (!$this->device_breakdown) {
            return null;
        }

        return array_keys($this->device_breakdown, max($this->device_breakdown))[0] ?? null;
    }

    // Static methods
    public static function updateOrCreateDaily(int $postId, array $metrics = []): self
    {
        return self::updateOrCreate(
            [
                'post_id' => $postId,
                'metric_date' => today()
            ],
            $metrics
        );
    }

    public static function incrementViews(int $postId): void
    {
        $metric = self::updateOrCreateDaily($postId);
        $metric->increment('views_count');
        $metric->updateMetrics();
    }

    public static function incrementClicks(int $postId): void
    {
        $metric = self::updateOrCreateDaily($postId);
        $metric->increment('clicks_count');
        $metric->updateMetrics();
    }

    public static function incrementApplications(int $postId): void
    {
        $metric = self::updateOrCreateDaily($postId);
        $metric->increment('applications_count');
        $metric->updateMetrics();
    }

    public static function incrementSaves(int $postId): void
    {
        $metric = self::updateOrCreateDaily($postId);
        $metric->increment('saves_count');
    }

    public static function getTopPerformingJobs(Carbon $startDate, Carbon $endDate, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::with('post')
            ->byDateRange($startDate, $endDate)
            ->selectRaw('post_id, SUM(applications_count) as total_applications, SUM(views_count) as total_views, AVG(application_rate) as avg_application_rate')
            ->groupBy('post_id')
            ->orderByDesc('total_applications')
            ->limit($limit)
            ->get();
    }

    public static function getAverageMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $result = self::byDateRange($startDate, $endDate)
            ->selectRaw('
                AVG(views_count) as avg_views,
                AVG(clicks_count) as avg_clicks,
                AVG(applications_count) as avg_applications,
                AVG(click_through_rate) as avg_ctr,
                AVG(application_rate) as avg_application_rate,
                AVG(avg_time_on_page) as avg_time_on_page
            ')
            ->first();

        return [
            'avg_views' => round($result->avg_views ?? 0, 2),
            'avg_clicks' => round($result->avg_clicks ?? 0, 2),
            'avg_applications' => round($result->avg_applications ?? 0, 2),
            'avg_ctr' => round($result->avg_ctr ?? 0, 4),
            'avg_application_rate' => round($result->avg_application_rate ?? 0, 4),
            'avg_time_on_page' => round($result->avg_time_on_page ?? 0, 2)
        ];
    }

    public static function getTrends(int $postId, int $days = 30): array
    {
        $metrics = self::forJob($postId)
            ->where('metric_date', '>=', now()->subDays($days))
            ->orderBy('metric_date')
            ->get();

        return [
            'views_trend' => $metrics->pluck('views_count', 'metric_date')->toArray(),
            'applications_trend' => $metrics->pluck('applications_count', 'metric_date')->toArray(),
            'ctr_trend' => $metrics->pluck('click_through_rate', 'metric_date')->toArray(),
            'performance_trend' => $metrics->map(function ($metric) {
                return [
                    'date' => $metric->metric_date->format('Y-m-d'),
                    'score' => $metric->getPerformanceScore()
                ];
            })->pluck('score', 'date')->toArray()
        ];
    }
}