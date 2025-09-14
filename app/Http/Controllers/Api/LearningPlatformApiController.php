<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CodingWorkspace;
use App\Models\UserModuleProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class LearningPlatformApiController extends Controller
{
    /**
     * Get all published courses
     */
    public function getCourses(Request $request): JsonResponse
    {
        $query = Course::published();

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by difficulty
        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        // Filter by type (free/premium)
        if ($request->has('type')) {
            if ($request->type === 'free') {
                $query->free();
            } elseif ($request->type === 'premium') {
                $query->premium();
            }
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $courses = $query->with(['reviews:id,course_id,rating'])
            ->orderBy('enrolled_count', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }

    /**
     * Get course details with modules
     */
    public function getCourse($id): JsonResponse
    {
        $course = Course::with(['modules' => function($query) {
            $query->orderBy('sort_order');
        }, 'reviews.user:id,name'])
            ->published()
            ->findOrFail($id);

        // Check if user is enrolled
        $isEnrolled = false;
        if (Auth::check()) {
            $isEnrolled = $course->enrolledUsers()
                ->where('user_id', Auth::id())
                ->exists();
        }

        return response()->json([
            'success' => true,
            'course' => $course,
            'is_enrolled' => $isEnrolled,
        ]);
    }

    /**
     * Enroll user in a course
     */
    public function enrollCourse($id): JsonResponse
    {
        $user = Auth::user();
        $course = Course::published()->findOrFail($id);

        // Check if already enrolled
        if ($course->enrolledUsers()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Already enrolled in this course'
            ], 400);
        }

        // Check subscription access for premium courses
        if (!$course->isFree()) {
            $subscription = $user->activeSubscription();
            if (!$subscription || !$subscription->canAccessPremiumCourses()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Premium subscription required'
                ], 403);
            }
        }

        // Enroll user
        $course->enrolledUsers()->attach($user->id, [
            'enrolled_at' => now(),
            'progress_percentage' => 0,
        ]);

        // Increment enrolled count
        $course->increment('enrolled_count');

        return response()->json([
            'success' => true,
            'message' => 'Successfully enrolled in course'
        ]);
    }

    /**
     * Get user's course progress
     */
    public function getUserProgress($courseId): JsonResponse
    {
        $user = Auth::user();
        $course = Course::findOrFail($courseId);

        $enrollment = $course->enrolledUsers()
            ->where('user_id', $user->id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Not enrolled in this course'
            ], 404);
        }

        // Get module progress
        $moduleProgress = UserModuleProgress::where('user_id', $user->id)
            ->whereIn('course_module_id', $course->modules()->pluck('id'))
            ->get()
            ->keyBy('course_module_id');

        $modules = $course->modules->map(function($module) use ($moduleProgress) {
            $progress = $moduleProgress->get($module->id);
            return [
                'id' => $module->id,
                'title' => $module->title,
                'type' => $module->type,
                'duration_minutes' => $module->duration_minutes,
                'status' => $progress ? $progress->status : 'not_started',
                'score' => $progress ? $progress->score : null,
                'time_spent' => $progress ? $progress->time_spent_minutes : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'enrollment' => $enrollment->pivot,
            'modules' => $modules,
        ]);
    }

    /**
     * Get module content
     */
    public function getModule($id): JsonResponse
    {
        $user = Auth::user();
        $module = CourseModule::with('course')->findOrFail($id);

        // Check if user has access to this module
        if (!$module->isPreview()) {
            $isEnrolled = $module->course->enrolledUsers()
                ->where('user_id', $user->id)
                ->exists();

            if (!$isEnrolled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course enrollment required'
                ], 403);
            }
        }

        // Get user progress for this module
        $progress = UserModuleProgress::where('user_id', $user->id)
            ->where('course_module_id', $module->id)
            ->first();

        // Mark as started if not already
        if (!$progress) {
            $progress = UserModuleProgress::create([
                'user_id' => $user->id,
                'course_module_id' => $module->id,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        } elseif ($progress->status === 'not_started') {
            $progress->markAsStarted();
        }

        return response()->json([
            'success' => true,
            'module' => $module,
            'progress' => $progress,
        ]);
    }

    /**
     * Update module progress
     */
    public function updateModuleProgress($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:in_progress,completed',
            'time_spent' => 'integer|min:0',
            'answers' => 'array',
            'score' => 'numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $module = CourseModule::findOrFail($id);

        $progress = UserModuleProgress::firstOrCreate([
            'user_id' => $user->id,
            'course_module_id' => $module->id,
        ]);

        $progress->status = $request->status;

        if ($request->has('time_spent')) {
            $progress->addTimeSpent($request->time_spent);
        }

        if ($request->has('answers')) {
            $progress->saveAnswers($request->answers, $request->score);
        }

        if ($request->status === 'completed' && !$progress->completed_at) {
            $progress->markAsCompleted($request->score);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }

    /**
     * Get user's coding workspaces
     */
    public function getWorkspaces(): JsonResponse
    {
        $user = Auth::user();

        $workspaces = CodingWorkspace::where('user_id', $user->id)
            ->with('courseModule:id,title')
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'workspaces' => $workspaces,
        ]);
    }

    /**
     * Create a new coding workspace
     */
    public function createWorkspace(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workspace_name' => 'required|string|max:255',
            'language' => 'required|in:javascript,python,java,php,html_css,react,node,sql',
            'course_module_id' => 'nullable|exists:course_modules,id',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $workspace = CodingWorkspace::create([
            'user_id' => Auth::id(),
            'workspace_name' => $request->workspace_name,
            'language' => $request->language,
            'course_module_id' => $request->course_module_id,
            'description' => $request->description,
            'code' => '', // Will be set to default template by model
        ]);

        // Set default template
        $workspace->code = $workspace->getDefaultTemplate();
        $workspace->save();

        return response()->json([
            'success' => true,
            'workspace' => $workspace,
        ]);
    }

    /**
     * Get workspace details
     */
    public function getWorkspace($id): JsonResponse
    {
        $user = Auth::user();

        $workspace = CodingWorkspace::where('user_id', $user->id)
            ->orWhere(function($query) {
                $query->where('is_shared', true);
            })
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'workspace' => $workspace,
        ]);
    }

    /**
     * Update workspace
     */
    public function updateWorkspace($id, Request $request): JsonResponse
    {
        $user = Auth::user();

        $workspace = CodingWorkspace::where('user_id', $user->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'workspace_name' => 'string|max:255',
            'code' => 'string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $workspace->update($request->only(['workspace_name', 'code', 'description']));

        return response()->json([
            'success' => true,
            'workspace' => $workspace,
        ]);
    }

    /**
     * Execute code in workspace
     */
    public function executeWorkspaceCode($id): JsonResponse
    {
        $user = Auth::user();

        $workspace = CodingWorkspace::where('user_id', $user->id)
            ->findOrFail($id);

        $results = $workspace->executeCode();

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Share workspace
     */
    public function shareWorkspace($id): JsonResponse
    {
        $user = Auth::user();

        $workspace = CodingWorkspace::where('user_id', $user->id)
            ->findOrFail($id);

        $shareToken = $workspace->share();

        return response()->json([
            'success' => true,
            'share_token' => $shareToken,
            'share_url' => url("/workspace/shared/{$shareToken}"),
        ]);
    }
}