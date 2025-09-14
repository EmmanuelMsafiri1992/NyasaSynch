<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\JobPerformanceMetric;
use App\Models\DashboardMetricsCache;
use App\Models\User;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BusinessIntelligenceService
{
    public function getDashboardOverview(array $filters = []): array
    {
        $timePeriod = $filters['period'] ?? 'today';

        return [
            'users' => $this->getUserMetrics($timePeriod),
            'jobs' => $this->getJobMetrics($timePeriod),
            'applications' => $this->getApplicationMetrics($timePeriod),
            'revenue' => $this->getRevenueMetrics($timePeriod),
            'traffic' => $this->getTrafficMetrics($timePeriod),
            'engagement' => $this->getEngagementMetrics($timePeriod)
        ];
    }

    public function getUserMetrics(string $timePeriod = 'today'): array
    {
        return DashboardMetricsCache::getOrCalculate(
            'user_metrics',
            $timePeriod,
            function () use ($timePeriod) {
                $dateRange = $this->getDateRange($timePeriod);
                $totalQuery = User::query();
                $newQuery = User::whereBetween('created_at', $dateRange);

                // Get user type breakdown
                $jobSeekers = $totalQuery->clone()->where('user_type_id', 1)->count();
                $employers = $totalQuery->clone()->where('user_type_id', 2)->count();

                // Get new users in period
                $newUsers = $newQuery->count();
                $newJobSeekers = $newQuery->clone()->where('user_type_id', 1)->count();
                $newEmployers = $newQuery->clone()->where('user_type_id', 2)->count();

                // Get active users (users with recent activity)
                $activeUsers = AnalyticsEvent::withUser()
                    ->byDateRange($dateRange[0], $dateRange[1])
                    ->distinct('user_id')
                    ->count();

                return [
                    'total_users' => $totalQuery->count(),
                    'job_seekers' => $jobSeekers,
                    'employers' => $employers,
                    'new_users' => $newUsers,
                    'new_job_seekers' => $newJobSeekers,
                    'new_employers' => $newEmployers,
                    'active_users' => $activeUsers,
                    'growth_rate' => $this->calculateGrowthRate('users', $timePeriod)
                ];
            }
        );
    }

    public function getJobMetrics(string $timePeriod = 'today'): array
    {
        return DashboardMetricsCache::getOrCalculate(
            'job_metrics',
            $timePeriod,
            function () use ($timePeriod) {
                $dateRange = $this->getDateRange($timePeriod);

                $totalJobs = Post::active()->count();
                $newJobs = Post::whereBetween('created_at', $dateRange)->count();
                $expiredJobs = Post::where('end_date', '<', now())->count();
                $featuredJobs = Post::active()->where('featured', true)->count();

                // Performance metrics
                $avgViews = JobPerformanceMetric::byDateRange($dateRange[0], $dateRange[1])
                    ->avg('views_count');
                $avgApplications = JobPerformanceMetric::byDateRange($dateRange[0], $dateRange[1])
                    ->avg('applications_count');

                return [
                    'total_active_jobs' => $totalJobs,
                    'new_jobs' => $newJobs,
                    'expired_jobs' => $expiredJobs,
                    'featured_jobs' => $featuredJobs,
                    'avg_views_per_job' => round($avgViews ?? 0, 2),
                    'avg_applications_per_job' => round($avgApplications ?? 0, 2),
                    'job_posting_trend' => $this->getJobPostingTrend($timePeriod)
                ];
            }
        );
    }

    public function getApplicationMetrics(string $timePeriod = 'today'): array
    {
        return DashboardMetricsCache::getOrCalculate(
            'application_metrics',
            $timePeriod,
            function () use ($timePeriod) {
                $dateRange = $this->getDateRange($timePeriod);

                $totalApplications = AnalyticsEvent::byEventType('job_application')
                    ->byDateRange($dateRange[0], $dateRange[1])
                    ->count();

                $uniqueApplicants = AnalyticsEvent::byEventType('job_application')
                    ->byDateRange($dateRange[0], $dateRange[1])
                    ->distinct('user_id')
                    ->count();

                $applicationsPerJob = JobPerformanceMetric::byDateRange($dateRange[0], $dateRange[1])
                    ->sum('applications_count');

                $avgApplicationRate = JobPerformanceMetric::byDateRange($dateRange[0], $dateRange[1])
                    ->avg('application_rate');

                return [
                    'total_applications' => $totalApplications,
                    'unique_applicants' => $uniqueApplicants,
                    'applications_per_user' => $uniqueApplicants > 0 ? round($totalApplications / $uniqueApplicants, 2) : 0,
                    'avg_application_rate' => round($avgApplicationRate ?? 0, 4),
                    'application_trend' => $this->getApplicationTrend($timePeriod)
                ];
            }
        );
    }

    public function getRevenueMetrics(string $timePeriod = 'month'): array
    {
        return DashboardMetricsCache::getOrCalculate(
            'revenue_metrics',
            $timePeriod,
            function () use ($timePeriod) {
                $dateRange = $this->getDateRange($timePeriod);

                // This would integrate with actual revenue tracking
                // For now, return mock data structure
                return [
                    'total_revenue' => 0,
                    'job_posting_revenue' => 0,
                    'premium_subscriptions' => 0,
                    'featured_listings' => 0,
                    'mrr' => 0, // Monthly Recurring Revenue
                    'arpu' => 0, // Average Revenue Per User
                    'revenue_trend' => []
                ];
            }
        );
    }

    public function getTrafficMetrics(string $timePeriod = 'today'): array
    {
        return DashboardMetricsCache::getOrCalculate(
            'traffic_metrics',
            $timePeriod,
            function () use ($timePeriod) {
                $dateRange = $this->getDateRange($timePeriod);

                $totalPageViews = AnalyticsEvent::byEventType('page_view')
                    ->byDateRange($dateRange[0], $dateRange[1])
                    ->count();

                $uniqueVisitors = AnalyticsEvent::byEventType('page_view')
                    ->byDateRange($dateRange[0], $dateRange[1])
                    ->distinct('session_id')
                    ->count();

                $bounceRate = $this->calculateBounceRate($dateRange[0], $dateRange[1]);
                $avgSessionDuration = $this->calculateAvgSessionDuration($dateRange[0], $dateRange[1]);

                return [
                    'total_page_views' => $totalPageViews,
                    'unique_visitors' => $uniqueVisitors,
                    'bounce_rate' => $bounceRate,
                    'avg_session_duration' => $avgSessionDuration,
                    'traffic_sources' => $this->getTrafficSourceBreakdown($dateRange[0], $dateRange[1]),
                    'device_breakdown' => AnalyticsEvent::getDeviceBreakdown($dateRange[0], $dateRange[1]),
                    'top_pages' => AnalyticsEvent::getTopPages($dateRange[0], $dateRange[1])
                ];
            }
        );
    }

    public function getEngagementMetrics(string $timePeriod = 'today'): array
    {
        return DashboardMetricsCache::getOrCalculate(
            'engagement_metrics',
            $timePeriod,
            function () use ($timePeriod) {
                $dateRange = $this->getDateRange($timePeriod);

                $jobViews = AnalyticsEvent::byEventType('job_view')
                    ->byDateRange($dateRange[0], $dateRange[1])
                    ->count();

                $jobSaves = AnalyticsEvent::byEventType('job_save')
                    ->byDateRange($dateRange[0], $dateRange[1])
                    ->count();

                $searches = AnalyticsEvent::byEventType('search')
                    ->byDateRange($dateRange[0], $dateRange[1])
                    ->count();

                return [
                    'job_views' => $jobViews,
                    'job_saves' => $jobSaves,
                    'searches_performed' => $searches,
                    'avg_jobs_per_session' => $this->calculateAvgJobsPerSession($dateRange[0], $dateRange[1]),
                    'search_to_application_rate' => $this->calculateSearchToApplicationRate($dateRange[0], $dateRange[1])
                ];
            }
        );
    }

    public function getJobPerformanceAnalysis(int $jobId, int $days = 30): array
    {
        $job = Post::findOrFail($jobId);
        $startDate = now()->subDays($days);
        $endDate = now();

        $metrics = JobPerformanceMetric::forJob($jobId)
            ->byDateRange($startDate, $endDate)
            ->orderBy('metric_date')
            ->get();

        $totalViews = $metrics->sum('views_count');
        $totalApplications = $metrics->sum('applications_count');
        $avgCTR = $metrics->avg('click_through_rate');
        $avgApplicationRate = $metrics->avg('application_rate');

        return [
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'created_at' => $job->created_at,
                'expires_at' => $job->end_date
            ],
            'summary' => [
                'total_views' => $totalViews,
                'total_applications' => $totalApplications,
                'avg_ctr' => round($avgCTR ?? 0, 4),
                'avg_application_rate' => round($avgApplicationRate ?? 0, 4),
                'performance_score' => $this->calculateJobPerformanceScore($metrics)
            ],
            'trends' => JobPerformanceMetric::getTrends($jobId, $days),
            'comparisons' => $this->getJobBenchmarks($job, $days),
            'recommendations' => $this->generateJobRecommendations($job, $metrics)
        ];
    }

    public function generateReport(string $reportType, array $parameters = []): array
    {
        return match($reportType) {
            'user_acquisition' => $this->generateUserAcquisitionReport($parameters),
            'job_performance' => $this->generateJobPerformanceReport($parameters),
            'revenue_analysis' => $this->generateRevenueAnalysisReport($parameters),
            'engagement_analysis' => $this->generateEngagementAnalysisReport($parameters),
            'cohort_analysis' => $this->generateCohortAnalysisReport($parameters),
            default => throw new \InvalidArgumentException("Unknown report type: $reportType")
        };
    }

    private function getDateRange(string $timePeriod): array
    {
        return match($timePeriod) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'last_7_days' => [now()->subDays(7), now()],
            'last_30_days' => [now()->subDays(30), now()],
            'last_90_days' => [now()->subDays(90), now()],
            default => [now()->startOfDay(), now()->endOfDay()]
        };
    }

    private function calculateGrowthRate(string $metric, string $timePeriod): float
    {
        // This would calculate growth rate compared to previous period
        // Implementation depends on specific business logic
        return 0.0;
    }

    private function getJobPostingTrend(string $timePeriod): array
    {
        $dateRange = $this->getDateRange($timePeriod);
        $groupBy = $timePeriod === 'today' ? 'hour' : 'day';

        return AnalyticsEvent::getEventCounts('job_posted', $dateRange[0], $dateRange[1], $groupBy);
    }

    private function getApplicationTrend(string $timePeriod): array
    {
        $dateRange = $this->getDateRange($timePeriod);
        $groupBy = $timePeriod === 'today' ? 'hour' : 'day';

        return AnalyticsEvent::getEventCounts('job_application', $dateRange[0], $dateRange[1], $groupBy);
    }

    private function calculateBounceRate(Carbon $startDate, Carbon $endDate): float
    {
        // Calculate sessions with only one page view
        $singlePageSessions = DB::table('analytics_events')
            ->select('session_id')
            ->where('event_type', 'page_view')
            ->whereBetween('event_timestamp', [$startDate, $endDate])
            ->groupBy('session_id')
            ->havingRaw('COUNT(*) = 1')
            ->count();

        $totalSessions = DB::table('analytics_events')
            ->where('event_type', 'page_view')
            ->whereBetween('event_timestamp', [$startDate, $endDate])
            ->distinct('session_id')
            ->count();

        return $totalSessions > 0 ? ($singlePageSessions / $totalSessions) * 100 : 0;
    }

    private function calculateAvgSessionDuration(Carbon $startDate, Carbon $endDate): float
    {
        // This would calculate average time between first and last event in session
        // Simplified implementation
        return 0.0;
    }

    private function getTrafficSourceBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        return DashboardMetricsCache::getTrafficSources('custom')['direct'] ?? [];
    }

    private function calculateAvgJobsPerSession(Carbon $startDate, Carbon $endDate): float
    {
        $jobViews = AnalyticsEvent::byEventType('job_view')
            ->byDateRange($startDate, $endDate)
            ->count();

        $sessions = AnalyticsEvent::byDateRange($startDate, $endDate)
            ->distinct('session_id')
            ->count();

        return $sessions > 0 ? $jobViews / $sessions : 0;
    }

    private function calculateSearchToApplicationRate(Carbon $startDate, Carbon $endDate): float
    {
        $searches = AnalyticsEvent::byEventType('search')
            ->byDateRange($startDate, $endDate)
            ->count();

        $applications = AnalyticsEvent::byEventType('job_application')
            ->byDateRange($startDate, $endDate)
            ->count();

        return $searches > 0 ? ($applications / $searches) * 100 : 0;
    }

    private function calculateJobPerformanceScore($metrics): float
    {
        if ($metrics->isEmpty()) {
            return 0;
        }

        return $metrics->avg(function ($metric) {
            return $metric->getPerformanceScore();
        });
    }

    private function getJobBenchmarks(Post $job, int $days): array
    {
        // Compare job performance to industry/category benchmarks
        $categoryAvg = JobPerformanceMetric::whereHas('post', function ($query) use ($job) {
                $query->where('category_id', $job->category_id);
            })
            ->where('metric_date', '>=', now()->subDays($days))
            ->selectRaw('AVG(views_count) as avg_views, AVG(applications_count) as avg_applications, AVG(application_rate) as avg_rate')
            ->first();

        return [
            'category_avg_views' => round($categoryAvg->avg_views ?? 0, 2),
            'category_avg_applications' => round($categoryAvg->avg_applications ?? 0, 2),
            'category_avg_rate' => round($categoryAvg->avg_rate ?? 0, 4)
        ];
    }

    private function generateJobRecommendations(Post $job, $metrics): array
    {
        $recommendations = [];

        if ($metrics->avg('views_count') < 10) {
            $recommendations[] = [
                'type' => 'visibility',
                'priority' => 'high',
                'message' => 'Consider optimizing job title and description for better visibility',
                'action' => 'improve_seo'
            ];
        }

        if ($metrics->avg('application_rate') < 2) {
            $recommendations[] = [
                'type' => 'conversion',
                'priority' => 'medium',
                'message' => 'Application rate is low. Review job requirements and benefits',
                'action' => 'optimize_requirements'
            ];
        }

        return $recommendations;
    }

    private function generateUserAcquisitionReport(array $parameters): array
    {
        // Generate comprehensive user acquisition report
        return [
            'report_type' => 'user_acquisition',
            'generated_at' => now()->toISOString(),
            'data' => []
        ];
    }

    private function generateJobPerformanceReport(array $parameters): array
    {
        // Generate job performance analysis report
        return [
            'report_type' => 'job_performance',
            'generated_at' => now()->toISOString(),
            'data' => []
        ];
    }

    private function generateRevenueAnalysisReport(array $parameters): array
    {
        // Generate revenue analysis report
        return [
            'report_type' => 'revenue_analysis',
            'generated_at' => now()->toISOString(),
            'data' => []
        ];
    }

    private function generateEngagementAnalysisReport(array $parameters): array
    {
        // Generate user engagement analysis report
        return [
            'report_type' => 'engagement_analysis',
            'generated_at' => now()->toISOString(),
            'data' => []
        ];
    }

    private function generateCohortAnalysisReport(array $parameters): array
    {
        // Generate cohort analysis report
        return [
            'report_type' => 'cohort_analysis',
            'generated_at' => now()->toISOString(),
            'data' => []
        ];
    }
}