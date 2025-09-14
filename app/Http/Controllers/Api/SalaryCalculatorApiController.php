<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SalaryCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SalaryCalculatorApiController extends Controller
{
    protected SalaryCalculatorService $salaryService;

    public function __construct(SalaryCalculatorService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    /**
     * Calculate salary estimate
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'job_title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'experience' => 'nullable|integer|min:0|max:50',
            'company_size' => 'nullable|string|in:startup,small,medium,large,enterprise'
        ]);

        try {
            $params = [
                'job_title' => $request->input('job_title'),
                'location' => $request->input('location'),
                'experience' => (int)$request->input('experience', 0),
                'company_size' => $request->input('company_size')
            ];

            $result = $this->salaryService->calculateSalary($params);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to calculate salary estimate',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Compare salaries across locations
     */
    public function compareLocations(Request $request): JsonResponse
    {
        $request->validate([
            'job_title' => 'required|string|max:255',
            'locations' => 'required|array|min:2|max:5',
            'locations.*' => 'string|max:255',
            'experience' => 'nullable|integer|min:0|max:50'
        ]);

        try {
            $jobTitle = $request->input('job_title');
            $locations = $request->input('locations');
            $experience = (int)$request->input('experience', 0);

            $result = $this->salaryService->compareSalariesByLocation($jobTitle, $locations, $experience);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to compare salaries',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Submit salary data from user
     */
    public function submitSalaryData(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $request->validate([
            'job_title' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'location_city' => 'required|string|max:255',
            'location_country' => 'required|string|max:255',
            'years_experience' => 'required|integer|min:0|max:50',
            'annual_salary' => 'required|numeric|min:0|max:10000000',
            'currency' => 'nullable|string|size:3',
            'bonus' => 'nullable|numeric|min:0',
            'additional_compensation' => 'nullable|string|max:1000',
            'skills' => 'nullable|array',
            'is_anonymous' => 'nullable|boolean'
        ]);

        try {
            $data = $request->all();
            $data['currency'] = $data['currency'] ?? 'USD';
            $data['is_anonymous'] = $data['is_anonymous'] ?? true;

            $submission = $this->salaryService->submitSalaryData(auth()->id(), $data);

            return response()->json([
                'message' => 'Salary data submitted successfully',
                'submission_id' => $submission->id
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to submit salary data',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Get popular job titles for autocomplete
     */
    public function popularJobTitles(Request $request): JsonResponse
    {
        $search = $request->input('q', '');
        
        try {
            // Get most common job titles from salary data
            $titles = \App\Models\SalaryData::query()
                ->when($search, function ($query, $search) {
                    $query->where('job_title', 'like', "%{$search}%")
                          ->orWhere('normalized_title', 'like', "%{$search}%");
                })
                ->select('job_title')
                ->groupBy('job_title')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(10)
                ->pluck('job_title');

            return response()->json(['titles' => $titles]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch job titles',
                'titles' => []
            ], 500);
        }
    }

    /**
     * Get salary trends for a job title
     */
    public function salaryTrends(Request $request): JsonResponse
    {
        $request->validate([
            'job_title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'period' => 'nullable|integer|min:6|max:60' // months
        ]);

        try {
            $jobTitle = $request->input('job_title');
            $location = $request->input('location');
            $periodMonths = (int)$request->input('period', 24);

            $query = \App\Models\SalaryData::forJobTitle($jobTitle)
                ->where('data_collected_at', '>=', now()->subMonths($periodMonths))
                ->orderBy('data_collected_at');

            if ($location) {
                $query->forLocation($location);
            }

            $trends = $query->get()
                ->groupBy(function ($item) {
                    return $item->data_collected_at->format('Y-m');
                })
                ->map(function ($group) {
                    return [
                        'period' => $group->first()->data_collected_at->format('M Y'),
                        'avg_salary' => round($group->avg('salary_median'), 2),
                        'data_points' => $group->count()
                    ];
                })
                ->values();

            return response()->json([
                'job_title' => $jobTitle,
                'location' => $location,
                'period_months' => $periodMonths,
                'trends' => $trends,
                'summary' => [
                    'total_data_points' => $trends->sum('data_points'),
                    'trend_direction' => $this->calculateTrendDirection($trends),
                    'average_growth' => $this->calculateAverageGrowth($trends)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch salary trends',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Calculate trend direction from data points
     */
    private function calculateTrendDirection($trends)
    {
        if ($trends->count() < 2) return 'insufficient_data';

        $first = $trends->first()['avg_salary'];
        $last = $trends->last()['avg_salary'];

        $changePercent = (($last - $first) / $first) * 100;

        if ($changePercent > 5) return 'increasing';
        if ($changePercent < -5) return 'decreasing';
        return 'stable';
    }

    /**
     * Calculate average monthly growth rate
     */
    private function calculateAverageGrowth($trends)
    {
        if ($trends->count() < 2) return 0;

        $totalGrowth = 0;
        $periods = 0;

        for ($i = 1; $i < $trends->count(); $i++) {
            $current = $trends[$i]['avg_salary'];
            $previous = $trends[$i - 1]['avg_salary'];

            if ($previous > 0) {
                $growth = (($current - $previous) / $previous) * 100;
                $totalGrowth += $growth;
                $periods++;
            }
        }

        return $periods > 0 ? round($totalGrowth / $periods, 2) : 0;
    }
}