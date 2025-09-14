<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AggregatedJob;
use App\Models\JobAggregationSource;
use App\Services\JobAggregationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class JobAggregationApiController extends Controller
{
    protected $jobAggregationService;

    public function __construct(JobAggregationService $jobAggregationService)
    {
        $this->jobAggregationService = $jobAggregationService;
    }

    /**
     * Get aggregated jobs with filters
     */
    public function getAggregatedJobs(Request $request): JsonResponse
    {
        $query = AggregatedJob::with(['aggregationSource:id,name,slug'])
            ->active()
            ->byRelevance();

        // Apply filters
        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('location')) {
            $query->byLocation($request->location);
        }

        if ($request->has('country')) {
            $query->byCountry($request->country);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('employment_type')) {
            $query->byEmploymentType($request->employment_type);
        }

        if ($request->has('experience_level')) {
            $query->byExperienceLevel($request->experience_level);
        }

        if ($request->has('min_salary') && $request->has('max_salary')) {
            $query->bySalaryRange($request->min_salary, $request->max_salary);
        }

        if ($request->has('posted_since')) {
            $days = (int) $request->posted_since;
            $query->recent($days);
        }

        if ($request->has('sources')) {
            $sources = explode(',', $request->sources);
            $query->whereHas('aggregationSource', function($q) use ($sources) {
                $q->whereIn('slug', $sources);
            });
        }

        // Pagination
        $perPage = min($request->get('per_page', 20), 50);
        $jobs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'jobs' => $jobs,
            'filters_applied' => $request->only([
                'search', 'location', 'country', 'category',
                'employment_type', 'experience_level', 'sources'
            ])
        ]);
    }

    /**
     * Get aggregated job details
     */
    public function getAggregatedJob($id): JsonResponse
    {
        $job = AggregatedJob::with(['aggregationSource', 'companies'])
            ->findOrFail($id);

        // Increment view count
        $job->incrementViews();

        return response()->json([
            'success' => true,
            'job' => $job
        ]);
    }

    /**
     * Save aggregated job
     */
    public function saveAggregatedJob(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $job = AggregatedJob::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if already saved
        $existing = $user->savedAggregatedJobs()->where('aggregated_job_id', $job->id)->first();

        if ($existing) {
            // Update notes if provided
            if ($request->has('notes')) {
                $user->savedAggregatedJobs()->updateExistingPivot($job->id, [
                    'notes' => $request->notes
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Job already saved, notes updated'
            ]);
        }

        // Save job
        $user->savedAggregatedJobs()->attach($job->id, [
            'notes' => $request->notes
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job saved successfully'
        ]);
    }

    /**
     * Unsave aggregated job
     */
    public function unsaveAggregatedJob($id): JsonResponse
    {
        $user = Auth::user();
        $job = AggregatedJob::findOrFail($id);

        $user->savedAggregatedJobs()->detach($job->id);

        return response()->json([
            'success' => true,
            'message' => 'Job removed from saved list'
        ]);
    }

    /**
     * Get user's saved aggregated jobs
     */
    public function getSavedAggregatedJobs(): JsonResponse
    {
        $user = Auth::user();

        $savedJobs = $user->savedAggregatedJobs()
            ->with(['aggregationSource:id,name,slug'])
            ->active()
            ->orderBy('user_saved_aggregated_jobs.created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'saved_jobs' => $savedJobs
        ]);
    }

    /**
     * Track job application
     */
    public function trackJobApplication($id): JsonResponse
    {
        $job = AggregatedJob::findOrFail($id);
        $job->incrementApplications();

        return response()->json([
            'success' => true,
            'message' => 'Application tracked',
            'redirect_url' => $job->application_url ?: $job->external_url
        ]);
    }

    /**
     * Get aggregation sources
     */
    public function getAggregationSources(): JsonResponse
    {
        $sources = JobAggregationSource::active()
            ->select(['id', 'name', 'slug', 'api_type', 'supported_countries', 'supported_categories'])
            ->withCount(['activeJobs'])
            ->byPriority()
            ->get();

        return response()->json([
            'success' => true,
            'sources' => $sources
        ]);
    }

    /**
     * Trigger manual sync (admin only)
     */
    public function triggerSync(Request $request): JsonResponse
    {
        // Check if user has admin permissions
        $user = Auth::user();
        if (!$user || !$user->can('manage-job-aggregation')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'source_id' => 'nullable|exists:job_aggregation_sources,id',
            'filters' => 'nullable|array',
            'filters.location' => 'nullable|string',
            'filters.keywords' => 'nullable|string',
            'filters.category' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->source_id) {
                $source = JobAggregationSource::findOrFail($request->source_id);
                $result = $this->jobAggregationService->syncSource($source, $request->filters ?? []);
            } else {
                $result = $this->jobAggregationService->syncAllSources($request->filters ?? []);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sync completed successfully',
                'results' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sync statistics
     */
    public function getSyncStats(): JsonResponse
    {
        $stats = $this->jobAggregationService->getSyncStats();

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Get popular categories from aggregated jobs
     */
    public function getPopularCategories(): JsonResponse
    {
        $categories = AggregatedJob::active()
            ->whereNotNull('category')
            ->selectRaw('category, COUNT(*) as job_count')
            ->groupBy('category')
            ->orderBy('job_count', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Get popular locations from aggregated jobs
     */
    public function getPopularLocations(): JsonResponse
    {
        $locations = AggregatedJob::active()
            ->whereNotNull('location')
            ->selectRaw('location, country_code, COUNT(*) as job_count')
            ->groupBy('location', 'country_code')
            ->orderBy('job_count', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'locations' => $locations
        ]);
    }

    /**
     * Get trending skills from aggregated jobs
     */
    public function getTrendingSkills(): JsonResponse
    {
        $jobs = AggregatedJob::active()
            ->whereNotNull('skills')
            ->select('skills')
            ->get();

        $skillCounts = [];

        foreach ($jobs as $job) {
            if (is_array($job->skills)) {
                foreach ($job->skills as $skill) {
                    $skill = trim(strtolower($skill));
                    $skillCounts[$skill] = ($skillCounts[$skill] ?? 0) + 1;
                }
            }
        }

        arsort($skillCounts);
        $topSkills = array_slice($skillCounts, 0, 30, true);

        $skills = collect($topSkills)->map(function($count, $skill) {
            return [
                'skill' => ucwords($skill),
                'job_count' => $count
            ];
        })->values();

        return response()->json([
            'success' => true,
            'skills' => $skills
        ]);
    }
}