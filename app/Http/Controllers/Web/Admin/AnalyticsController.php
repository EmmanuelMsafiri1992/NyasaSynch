<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Services\BusinessIntelligenceService;

class AnalyticsController extends PanelController
{
    protected BusinessIntelligenceService $biService;

    public function __construct(BusinessIntelligenceService $biService)
    {
        parent::__construct();
        $this->biService = $biService;
    }

    /**
     * Analytics Dashboard
     */
    public function dashboard()
    {
        $data = [
            'title' => 'Analytics Dashboard',
            'overview' => $this->biService->getDashboardOverview(['period' => 'month'])
        ];

        return view('admin.analytics.dashboard', $data);
    }

    /**
     * User Analytics
     */
    public function users()
    {
        $data = [
            'title' => 'User Analytics',
            'userMetrics' => $this->biService->getUserMetrics('month')
        ];

        return view('admin.analytics.users', $data);
    }

    /**
     * Job Analytics
     */
    public function jobs()
    {
        $data = [
            'title' => 'Job Analytics',
            'jobMetrics' => $this->biService->getJobMetrics('month')
        ];

        return view('admin.analytics.jobs', $data);
    }

    /**
     * Revenue Analytics
     */
    public function revenue()
    {
        $data = [
            'title' => 'Revenue Analytics'
        ];

        return view('admin.analytics.revenue', $data);
    }

    /**
     * Reports
     */
    public function reports()
    {
        $data = [
            'title' => 'Reports'
        ];

        return view('admin.analytics.reports', $data);
    }

    /**
     * Generate Report
     */
    public function generateReport()
    {
        // Implementation for report generation
        return response()->json(['success' => true]);
    }
}