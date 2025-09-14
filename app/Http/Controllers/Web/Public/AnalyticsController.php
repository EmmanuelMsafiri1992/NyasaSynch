<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Web\Public\FrontController;
use App\Services\BusinessIntelligenceService;
use App\Models\Post;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laraven\LaravelMetaTags\Facades\MetaTag;

class AnalyticsController extends FrontController
{
    protected BusinessIntelligenceService $biService;

    public function __construct(BusinessIntelligenceService $biService)
    {
        parent::__construct();
        $this->biService = $biService;
    }

    /**
     * Show analytics dashboard
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();

        // Check if user has permission to view analytics
        if (!$user->hasRole(['admin', 'employer'])) {
            abort(403, 'Access denied');
        }

        $data = [];

        // Meta Tags
        MetaTag::set('title', 'Analytics Dashboard - ' . config('settings.app.name'));
        MetaTag::set('description', 'View comprehensive analytics and insights');

        $period = $request->get('period', 'week');

        try {
            // Get dashboard overview
            $data['overview'] = $this->biService->getDashboardOverview(['period' => $period]);

            // Get user metrics
            $data['userMetrics'] = $this->biService->getUserMetrics($period);

            // Get job metrics (filtered by user's company if employer)
            if ($user->hasRole('employer') && $user->company_id) {
                $data['jobMetrics'] = $this->biService->getJobMetrics($period, [
                    'company_id' => $user->company_id
                ]);
            } else {
                $data['jobMetrics'] = $this->biService->getJobMetrics($period);
            }

            // Get traffic analytics
            $data['trafficMetrics'] = $this->biService->getTrafficMetrics($period);

            // Get engagement metrics
            $data['engagementMetrics'] = $this->biService->getEngagementMetrics($period);

        } catch (\Exception $e) {
            $data['error'] = 'Unable to load analytics data: ' . $e->getMessage();
        }

        $data['period'] = $period;
        $data['availablePeriods'] = [
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
            'quarter' => 'This Quarter',
            'year' => 'This Year'
        ];

        return view('analytics.dashboard', $data);
    }

    /**
     * Show job analytics
     */
    public function jobAnalytics(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'employer'])) {
            abort(403, 'Access denied');
        }

        $data = [];

        // Meta Tags
        MetaTag::set('title', 'Job Analytics - Analytics Dashboard');
        MetaTag::set('description', 'Detailed job performance analytics');

        $period = $request->get('period', 'month');

        try {
            // Get job metrics
            $filters = ['period' => $period];
            if ($user->hasRole('employer') && $user->company_id) {
                $filters['company_id'] = $user->company_id;
            }

            $data['jobMetrics'] = $this->biService->getJobMetrics($period, $filters);

            // Get top performing jobs
            $data['topJobs'] = $this->biService->getTopPerformingJobs($period, 10);

            // Get job performance trends
            $data['performanceTrends'] = $this->biService->getJobPerformanceTrends($period);

            // Get jobs list for detailed view
            $jobsQuery = Post::with(['category', 'city'])
                ->where('active', 1);

            if ($user->hasRole('employer') && $user->company_id) {
                $jobsQuery->where('company_id', $user->company_id);
            }

            $data['jobs'] = $jobsQuery->paginate(10);

        } catch (\Exception $e) {
            $data['error'] = 'Unable to load job analytics: ' . $e->getMessage();
        }

        $data['period'] = $period;

        return view('analytics.jobs', $data);
    }

    /**
     * Show application analytics
     */
    public function applicationAnalytics(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'employer'])) {
            abort(403, 'Access denied');
        }

        $data = [];

        // Meta Tags
        MetaTag::set('title', 'Application Analytics - Analytics Dashboard');
        MetaTag::set('description', 'Detailed application analytics and trends');

        $period = $request->get('period', 'month');

        try {
            // Get application metrics
            $data['applicationStats'] = $this->biService->getApplicationAnalytics($period);

            // Get conversion funnel
            $data['conversionFunnel'] = $this->biService->getApplicationConversionFunnel($period);

            // Get application trends by source
            $data['applicationSources'] = $this->biService->getApplicationSources($period);

            // Get top converting posts
            $data['topConvertingPosts'] = $this->biService->getTopConvertingPosts($period, 10);

        } catch (\Exception $e) {
            $data['error'] = 'Unable to load application analytics: ' . $e->getMessage();
        }

        $data['period'] = $period;

        return view('analytics.applications', $data);
    }

    /**
     * Show reports page
     */
    public function reports(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'employer'])) {
            abort(403, 'Access denied');
        }

        $data = [];

        // Meta Tags
        MetaTag::set('title', 'Reports - Analytics Dashboard');
        MetaTag::set('description', 'Generate and view detailed reports');

        // Get available report types
        $data['reportTypes'] = [
            'user_acquisition' => [
                'title' => 'User Acquisition Report',
                'description' => 'Detailed analysis of user growth and acquisition channels'
            ],
            'job_performance' => [
                'title' => 'Job Performance Report',
                'description' => 'Comprehensive job posting performance analysis'
            ],
            'revenue_analysis' => [
                'title' => 'Revenue Analysis Report',
                'description' => 'Revenue trends and financial insights'
            ],
            'engagement_analysis' => [
                'title' => 'User Engagement Report',
                'description' => 'User behavior and engagement patterns'
            ],
            'cohort_analysis' => [
                'title' => 'Cohort Analysis Report',
                'description' => 'User retention and lifecycle analysis'
            ]
        ];

        // Get recent reports
        $data['recentReports'] = collect(); // Would fetch from database in real implementation

        return view('analytics.reports', $data);
    }
}