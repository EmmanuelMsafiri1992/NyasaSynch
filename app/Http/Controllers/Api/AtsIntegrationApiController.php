<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AtsConnection;
use App\Models\AtsJobPosting;
use App\Models\AtsCandidate;
use App\Models\AtsApplication;
use App\Services\AtsIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AtsIntegrationApiController extends Controller
{
    private AtsIntegrationService $atsService;

    public function __construct(AtsIntegrationService $atsService)
    {
        $this->atsService = $atsService;
        $this->middleware('auth:sanctum');
    }

    public function getConnections(Request $request): JsonResponse
    {
        $connections = AtsConnection::where('user_id', Auth::id())
            ->with(['jobPostings' => function($query) {
                $query->active()->limit(5);
            }])
            ->get();

        return response()->json([
            'success' => true,
            'connections' => $connections
        ]);
    }

    public function createConnection(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'provider' => ['required', Rule::in(['workday', 'greenhouse', 'lever', 'bamboohr', 'successfactors', 'taleo', 'icims', 'jazz', 'bullhorn', 'jobvite'])],
            'api_endpoint' => 'required|url',
            'credentials' => 'required|array',
            'configuration' => 'sometimes|array',
            'field_mapping' => 'sometimes|array',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            $connection = $this->atsService->createConnection(Auth::user(), $request->all());

            return response()->json([
                'success' => true,
                'message' => 'ATS connection created successfully',
                'connection' => $connection
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ATS connection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testConnection(Request $request, AtsConnection $connection): JsonResponse
    {
        if ($connection->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $result = $this->atsService->testConnection($connection);

        return response()->json($result);
    }

    public function syncConnection(Request $request, AtsConnection $connection): JsonResponse
    {
        if ($connection->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $filters = $request->validate([
            'location' => 'sometimes|string',
            'keywords' => 'sometimes|string',
            'department' => 'sometimes|string'
        ]);

        try {
            $result = $this->atsService->syncConnection($connection, $filters);

            return response()->json([
                'success' => true,
                'message' => 'Sync completed successfully',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getJobPostings(Request $request): JsonResponse
    {
        $request->validate([
            'connection_id' => 'sometimes|exists:ats_connections,id',
            'status' => 'sometimes|in:active,paused,closed,draft',
            'location' => 'sometimes|string',
            'department' => 'sometimes|string',
            'experience_level' => 'sometimes|in:entry-level,mid-level,senior,executive',
            'employment_type' => 'sometimes|in:full-time,part-time,contract,temporary,internship',
            'salary_min' => 'sometimes|numeric',
            'salary_max' => 'sometimes|numeric',
            'search' => 'sometimes|string',
            'recent_days' => 'sometimes|integer|min:1|max:365',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $query = AtsJobPosting::query()
            ->whereHas('atsConnection', function($q) {
                $q->where('user_id', Auth::id());
            })
            ->with(['atsConnection', 'applications']);

        // Apply filters
        if ($request->filled('connection_id')) {
            $query->where('ats_connection_id', $request->connection_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('location')) {
            $query->byLocation($request->location);
        }

        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        if ($request->filled('experience_level')) {
            $query->byExperienceLevel($request->experience_level);
        }

        if ($request->filled('employment_type')) {
            $query->byEmploymentType($request->employment_type);
        }

        if ($request->filled('salary_min') || $request->filled('salary_max')) {
            $query->salaryRange($request->salary_min, $request->salary_max);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('recent_days')) {
            $query->recent($request->recent_days);
        }

        $perPage = $request->get('per_page', 15);
        $jobPostings = $query->latest('posted_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'job_postings' => $jobPostings
        ]);
    }

    public function getJobPosting(AtsJobPosting $jobPosting): JsonResponse
    {
        if ($jobPosting->atsConnection->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $jobPosting->load(['atsConnection', 'applications.candidate']);

        return response()->json([
            'success' => true,
            'job_posting' => $jobPosting
        ]);
    }

    public function getCandidates(Request $request): JsonResponse
    {
        $request->validate([
            'connection_id' => 'sometimes|exists:ats_connections,id',
            'skills' => 'sometimes|array',
            'location' => 'sometimes|string',
            'availability' => 'sometimes|in:immediate,2-weeks,1-month,flexible',
            'open_to_remote' => 'sometimes|boolean',
            'salary_min' => 'sometimes|numeric',
            'salary_max' => 'sometimes|numeric',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $query = AtsCandidate::query()
            ->whereHas('atsConnection', function($q) {
                $q->where('user_id', Auth::id());
            })
            ->with(['atsConnection', 'applications.jobPosting']);

        // Apply filters
        if ($request->filled('connection_id')) {
            $query->where('ats_connection_id', $request->connection_id);
        }

        if ($request->filled('skills') && is_array($request->skills)) {
            $query->bySkills($request->skills);
        }

        if ($request->filled('location')) {
            $query->byLocation($request->location);
        }

        if ($request->filled('availability')) {
            $query->byAvailability($request->availability);
        }

        if ($request->filled('open_to_remote')) {
            $query->openToRemote();
        }

        if ($request->filled('salary_min') || $request->filled('salary_max')) {
            $query->bySalaryRange($request->salary_min, $request->salary_max);
        }

        $perPage = $request->get('per_page', 15);
        $candidates = $query->latest('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'candidates' => $candidates
        ]);
    }

    public function getCandidate(AtsCandidate $candidate): JsonResponse
    {
        if ($candidate->atsConnection->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $candidate->load(['atsConnection', 'applications.jobPosting']);

        return response()->json([
            'success' => true,
            'candidate' => $candidate
        ]);
    }

    public function getApplications(Request $request): JsonResponse
    {
        $request->validate([
            'connection_id' => 'sometimes|exists:ats_connections,id',
            'job_posting_id' => 'sometimes|exists:ats_job_postings,id',
            'candidate_id' => 'sometimes|exists:ats_candidates,id',
            'status' => 'sometimes|in:new,screening,interview,assessment,offer,hired,rejected,withdrawn',
            'job_title' => 'sometimes|string',
            'candidate_name' => 'sometimes|string',
            'recent_days' => 'sometimes|integer|min:1|max:365',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $query = AtsApplication::query()
            ->whereHas('jobPosting.atsConnection', function($q) {
                $q->where('user_id', Auth::id());
            })
            ->with(['jobPosting.atsConnection', 'candidate']);

        // Apply filters
        if ($request->filled('connection_id')) {
            $query->whereHas('jobPosting', function($q) use ($request) {
                $q->where('ats_connection_id', $request->connection_id);
            });
        }

        if ($request->filled('job_posting_id')) {
            $query->where('ats_job_posting_id', $request->job_posting_id);
        }

        if ($request->filled('candidate_id')) {
            $query->where('ats_candidate_id', $request->candidate_id);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('job_title')) {
            $query->byJobTitle($request->job_title);
        }

        if ($request->filled('candidate_name')) {
            $query->byCandidate($request->candidate_name);
        }

        if ($request->filled('recent_days')) {
            $query->recent($request->recent_days);
        }

        $perPage = $request->get('per_page', 15);
        $applications = $query->latest('applied_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'applications' => $applications
        ]);
    }

    public function getApplication(AtsApplication $application): JsonResponse
    {
        if ($application->jobPosting->atsConnection->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $application->load(['jobPosting.atsConnection', 'candidate']);

        return response()->json([
            'success' => true,
            'application' => $application
        ]);
    }

    public function updateApplicationStatus(Request $request, AtsApplication $application): JsonResponse
    {
        if ($application->jobPosting->atsConnection->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => ['required', Rule::in(['new', 'screening', 'interview', 'assessment', 'offer', 'hired', 'rejected', 'withdrawn'])],
            'rejection_reason' => 'required_if:status,rejected|string|max:500',
            'notes' => 'sometimes|string|max:1000'
        ]);

        try {
            $application->updateStatus($request->status, $request->rejection_reason);

            if ($request->filled('notes')) {
                $application->addInterviewNote($request->notes, Auth::user()->name);
            }

            return response()->json([
                'success' => true,
                'message' => 'Application status updated successfully',
                'application' => $application->fresh(['jobPosting', 'candidate'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update application status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addInterviewNote(Request $request, AtsApplication $application): JsonResponse
    {
        if ($application->jobPosting->atsConnection->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'note' => 'required|string|max:1000',
            'interviewer' => 'sometimes|string|max:255'
        ]);

        try {
            $application->addInterviewNote(
                $request->note,
                $request->get('interviewer', Auth::user()->name)
            );

            return response()->json([
                'success' => true,
                'message' => 'Interview note added successfully',
                'application' => $application->fresh(['jobPosting', 'candidate'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add interview note',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function setAssessmentScore(Request $request, AtsApplication $application): JsonResponse
    {
        if ($application->jobPosting->atsConnection->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'assessment' => 'required|string|max:255',
            'score' => 'required|numeric|min:0|max:100',
            'notes' => 'sometimes|string|max:500'
        ]);

        try {
            $application->setAssessmentScore(
                $request->assessment,
                $request->score,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Assessment score added successfully',
                'application' => $application->fresh(['jobPosting', 'candidate'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add assessment score',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSyncLogs(Request $request): JsonResponse
    {
        $request->validate([
            'connection_id' => 'sometimes|exists:ats_connections,id',
            'sync_type' => 'sometimes|in:jobs,candidates,applications,full',
            'status' => 'sometimes|in:started,completed,failed',
            'recent_hours' => 'sometimes|integer|min:1|max:168',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $query = \App\Models\AtsSyncLog::query()
            ->whereHas('atsConnection', function($q) {
                $q->where('user_id', Auth::id());
            })
            ->with('atsConnection');

        // Apply filters
        if ($request->filled('connection_id')) {
            $query->where('ats_connection_id', $request->connection_id);
        }

        if ($request->filled('sync_type')) {
            $query->byType($request->sync_type);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('recent_hours')) {
            $query->recent($request->recent_hours);
        }

        $perPage = $request->get('per_page', 15);
        $syncLogs = $query->latest('started_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'sync_logs' => $syncLogs
        ]);
    }

    public function getWebhooks(Request $request): JsonResponse
    {
        $request->validate([
            'connection_id' => 'sometimes|exists:ats_connections,id',
            'event_type' => 'sometimes|string',
            'status' => 'sometimes|in:pending,processed,failed',
            'recent_hours' => 'sometimes|integer|min:1|max:168',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $query = \App\Models\AtsWebhook::query()
            ->whereHas('atsConnection', function($q) {
                $q->where('user_id', Auth::id());
            })
            ->with('atsConnection');

        // Apply filters
        if ($request->filled('connection_id')) {
            $query->where('ats_connection_id', $request->connection_id);
        }

        if ($request->filled('event_type')) {
            $query->byEventType($request->event_type);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('recent_hours')) {
            $query->recent($request->recent_hours);
        }

        $perPage = $request->get('per_page', 15);
        $webhooks = $query->latest('received_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'webhooks' => $webhooks
        ]);
    }

    public function webhook(Request $request, AtsConnection $connection): JsonResponse
    {
        try {
            $result = $this->atsService->processWebhook($connection, $request->all());

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStats(Request $request): JsonResponse
    {
        $connections = AtsConnection::where('user_id', Auth::id())->get();

        $stats = [
            'total_connections' => $connections->count(),
            'active_connections' => $connections->where('is_active', true)->count(),
            'total_job_postings' => 0,
            'active_job_postings' => 0,
            'total_candidates' => 0,
            'total_applications' => 0,
            'applications_by_status' => [
                'new' => 0,
                'screening' => 0,
                'interview' => 0,
                'assessment' => 0,
                'offer' => 0,
                'hired' => 0,
                'rejected' => 0,
                'withdrawn' => 0
            ],
            'recent_sync_activity' => []
        ];

        foreach ($connections as $connection) {
            // Job postings stats
            $stats['total_job_postings'] += $connection->jobPostings()->count();
            $stats['active_job_postings'] += $connection->jobPostings()->active()->count();

            // Candidates stats
            $stats['total_candidates'] += $connection->candidates()->count();

            // Applications stats
            $applicationStats = $connection->jobPostings()
                ->withCount(['applications' => function($query) {
                    // Group by status would be better but this is simpler
                }])
                ->get()
                ->sum('applications_count');

            $stats['total_applications'] += $applicationStats;

            // Applications by status
            $applications = AtsApplication::whereHas('jobPosting', function($query) use ($connection) {
                $query->where('ats_connection_id', $connection->id);
            })->get();

            foreach ($applications as $application) {
                $stats['applications_by_status'][$application->status]++;
            }

            // Recent sync activity
            $recentSyncs = $connection->syncLogs()
                ->recent(72) // Last 3 days
                ->latest('started_at')
                ->limit(5)
                ->get();

            $stats['recent_sync_activity'] = array_merge(
                $stats['recent_sync_activity'],
                $recentSyncs->toArray()
            );
        }

        // Sort recent sync activity by date
        usort($stats['recent_sync_activity'], function($a, $b) {
            return strtotime($b['started_at']) - strtotime($a['started_at']);
        });

        $stats['recent_sync_activity'] = array_slice($stats['recent_sync_activity'], 0, 10);

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}