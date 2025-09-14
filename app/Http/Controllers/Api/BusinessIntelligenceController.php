<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BusinessIntelligenceService;
use App\Models\AnalyticsEvent;
use App\Models\JobPerformanceMetric;
use App\Models\DashboardMetricsCache;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BusinessIntelligenceController extends Controller
{
    private BusinessIntelligenceService $biService;

    public function __construct(BusinessIntelligenceService $biService)
    {
        $this->biService = $biService;
        $this->middleware('auth:sanctum')->except(['trackEvent', 'getPublicMetrics']);
    }

    public function getDashboard(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:today,yesterday,week,month,quarter,year,last_7_days,last_30_days,last_90_days',
            'filters' => 'sometimes|array',
            'refresh' => 'sometimes|boolean'
        ]);

        try {
            if ($request->get('refresh', false)) {
                DashboardMetricsCache::clearAll();
            }

            $filters = $request->get('filters', []);
            $filters['period'] = $request->get('period', 'today');

            $dashboard = $this->biService->getDashboardOverview($filters);

            return response()->json([
                'success' => true,
                'dashboard' => $dashboard,
                'last_updated' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:today,week,month,year,last_30_days',
            'breakdown' => 'sometimes|in:type,location,acquisition_source'
        ]);

        try {
            $period = $request->get('period', 'month');
            $breakdown = $request->get('breakdown');

            $metrics = $this->biService->getUserMetrics($period);

            if ($breakdown) {
                $metrics['breakdown'] = $this->getUserBreakdown($breakdown, $period);
            }

            return response()->json([
                'success' => true,
                'metrics' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getJobMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:today,week,month,year',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'company_id' => 'sometimes|integer|exists:companies,id'
        ]);

        try {
            $period = $request->get('period', 'month');
            $metrics = $this->biService->getJobMetrics($period);

            // Add filters if specified
            if ($request->filled('category_id') || $request->filled('company_id')) {
                $metrics['filtered_data'] = $this->getFilteredJobMetrics($request->all());
            }

            return response()->json([
                'success' => true,
                'metrics' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get job metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getJobPerformance(Request $request, int $jobId): JsonResponse
    {
        $request->validate([
            'days' => 'sometimes|integer|min:1|max:365'
        ]);

        try {
            $days = $request->get('days', 30);
            $analysis = $this->biService->getJobPerformanceAnalysis($jobId, $days);

            return response()->json([
                'success' => true,
                'analysis' => $analysis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get job performance analysis',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function getTrafficAnalytics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:today,week,month',
            'include_breakdown' => 'sometimes|boolean'
        ]);

        try {
            $period = $request->get('period', 'week');
            $includeBreakdown = $request->get('include_breakdown', true);

            $metrics = $this->biService->getTrafficMetrics($period);

            if (!$includeBreakdown) {
                unset($metrics['traffic_sources'], $metrics['device_breakdown'], $metrics['top_pages']);
            }

            return response()->json([
                'success' => true,
                'traffic' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get traffic analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getEngagementMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:today,week,month',
            'user_type' => 'sometimes|in:job_seeker,employer'
        ]);

        try {
            $period = $request->get('period', 'week');
            $userType = $request->get('user_type');

            $metrics = $this->biService->getEngagementMetrics($period);

            if ($userType) {
                $metrics['user_type_breakdown'] = $this->getEngagementByUserType($userType, $period);
            }

            return response()->json([
                'success' => true,
                'engagement' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get engagement metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateReport(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => ['required', Rule::in(['user_acquisition', 'job_performance', 'revenue_analysis', 'engagement_analysis', 'cohort_analysis'])],
            'parameters' => 'sometimes|array',
            'format' => 'sometimes|in:json,csv,pdf',
            'email_to' => 'sometimes|email'
        ]);

        try {
            $reportType = $request->input('report_type');
            $parameters = $request->get('parameters', []);
            $format = $request->get('format', 'json');

            $report = $this->biService->generateReport($reportType, $parameters);

            if ($format === 'csv') {
                return $this->downloadReportAsCsv($report);
            } elseif ($format === 'pdf') {
                return $this->downloadReportAsPdf($report);
            }

            // Email report if requested
            if ($request->filled('email_to')) {
                $this->emailReport($report, $request->input('email_to'));
            }

            return response()->json([
                'success' => true,
                'report' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trackEvent(Request $request): JsonResponse
    {
        $request->validate([
            'event_type' => 'required|string|max:100',
            'event_data' => 'sometimes|array',
            'user_id' => 'sometimes|integer|exists:users,id'
        ]);

        try {
            $user = null;
            if ($request->filled('user_id')) {
                $user = \App\Models\User::find($request->input('user_id'));
            } elseif (Auth::check()) {
                $user = Auth::user();
            }

            $event = AnalyticsEvent::track(
                $request->input('event_type'),
                $request->get('event_data', []),
                $user
            );

            // Update related metrics
            $this->updateRelatedMetrics($request->input('event_type'), $request->get('event_data', []));

            return response()->json([
                'success' => true,
                'event_id' => $event->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPublicMetrics(): JsonResponse
    {
        try {
            // Public metrics that can be shown to anyone
            $metrics = [
                'total_jobs' => DashboardMetricsCache::getActiveJobs('all')['count'] ?? 0,
                'total_companies' => \App\Models\Company::count(),
                'this_month_applications' => DashboardMetricsCache::getApplicationStats('month')['total_applications'] ?? 0,
                'platform_health' => 'operational'
            ];

            return response()->json([
                'success' => true,
                'metrics' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get public metrics'
            ], 500);
        }
    }

    public function getTopPerformingJobs(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:week,month,quarter',
            'metric' => 'sometimes|in:views,applications,conversion_rate',
            'limit' => 'sometimes|integer|min:1|max:50'
        ]);

        try {
            $period = $request->get('period', 'month');
            $limit = $request->get('limit', 10);
            $dateRange = $this->getDateRangeForPeriod($period);

            $jobs = JobPerformanceMetric::getTopPerformingJobs(
                $dateRange[0],
                $dateRange[1],
                $limit
            );

            return response()->json([
                'success' => true,
                'top_jobs' => $jobs,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get top performing jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSearchAnalytics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:today,week,month',
            'limit' => 'sometimes|integer|min:1|max:100'
        ]);

        try {
            $period = $request->get('period', 'week');
            $limit = $request->get('limit', 20);

            $searchTerms = DashboardMetricsCache::getTopSearchTerms($period, $limit);

            return response()->json([
                'success' => true,
                'search_analytics' => $searchTerms
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get search analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function clearCache(Request $request): JsonResponse
    {
        $request->validate([
            'metric_key' => 'sometimes|string'
        ]);

        try {
            if ($request->filled('metric_key')) {
                $cleared = DashboardMetricsCache::clearByMetric($request->input('metric_key'));
            } else {
                $cleared = DashboardMetricsCache::clearAll();
            }

            return response()->json([
                'success' => true,
                'message' => "Cleared {$cleared} cached metrics"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Private helper methods
    private function getUserBreakdown(string $breakdown, string $period): array
    {
        // Implementation would depend on specific breakdown type
        return [];
    }

    private function getFilteredJobMetrics(array $filters): array
    {
        // Implementation for filtered job metrics
        return [];
    }

    private function getEngagementByUserType(string $userType, string $period): array
    {
        // Implementation for user type breakdown
        return [];
    }

    private function downloadReportAsCsv(array $report): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Implementation for CSV download
        return response()->streamDownload(function () use ($report) {
            echo "CSV implementation here";
        }, 'report.csv', ['Content-Type' => 'text/csv']);
    }

    private function downloadReportAsPdf(array $report): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Implementation for PDF download
        throw new \Exception('PDF export not implemented');
    }

    private function emailReport(array $report, string $email): void
    {
        // Implementation for emailing reports
        // This would queue an email job
    }

    private function updateRelatedMetrics(string $eventType, array $eventData): void
    {
        switch ($eventType) {
            case 'job_view':
                if (isset($eventData['job_id'])) {
                    JobPerformanceMetric::incrementViews($eventData['job_id']);
                }
                break;
            case 'job_click':
                if (isset($eventData['job_id'])) {
                    JobPerformanceMetric::incrementClicks($eventData['job_id']);
                }
                break;
            case 'job_application':
                if (isset($eventData['job_id'])) {
                    JobPerformanceMetric::incrementApplications($eventData['job_id']);
                }
                break;
            case 'job_save':
                if (isset($eventData['job_id'])) {
                    JobPerformanceMetric::incrementSaves($eventData['job_id']);
                }
                break;
        }
    }

    private function getDateRangeForPeriod(string $period): array
    {
        return match($period) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            default => [now()->startOfMonth(), now()->endOfMonth()]
        };
    }
}