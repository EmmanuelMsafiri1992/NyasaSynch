<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Web\Public\FrontController;
use App\Models\LearningCourse;
use App\Models\LearningLesson;
use App\Models\UserCourseProgress;
use App\Services\LearningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class LearningController extends FrontController
{
    protected LearningService $learningService;

    public function __construct(LearningService $learningService)
    {
        parent::__construct();
        $this->learningService = $learningService;
    }

    /**
     * Show learning platform dashboard
     */
    public function index()
    {
        $data = [];

        // Meta Tags
        MetaTag::set('title', 'Learning Platform - ' . config('settings.app.name'));
        MetaTag::set('description', 'Advance your career with our comprehensive learning platform');

        // Get user's enrolled courses
        $user = Auth::user();
        $data['enrolledCourses'] = UserCourseProgress::with('course')
            ->where('user_id', $user->id)
            ->get();

        // Get recommended courses
        $data['recommendedCourses'] = LearningCourse::active()
            ->where('is_featured', true)
            ->limit(6)
            ->get();

        // Get recent progress
        $data['recentProgress'] = $this->learningService->getUserRecentProgress($user->id, 5);

        // Get learning statistics
        $data['stats'] = $this->learningService->getUserLearningStats($user->id);

        return view('learning.index', $data);
    }

    /**
     * Show all courses
     */
    public function courses(Request $request)
    {
        $data = [];

        // Meta Tags
        MetaTag::set('title', 'Courses - Learning Platform');
        MetaTag::set('description', 'Browse our comprehensive course catalog');

        // Get filters
        $category = $request->get('category');
        $level = $request->get('level');
        $search = $request->get('search');

        // Build query
        $query = LearningCourse::active()->with(['category', 'instructor']);

        if ($category) {
            $query->where('category_id', $category);
        }

        if ($level) {
            $query->where('difficulty_level', $level);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $data['courses'] = $query->paginate(12);
        $data['categories'] = \App\Models\LearningCategory::active()->get();
        $data['levels'] = ['beginner', 'intermediate', 'advanced'];
        $data['filters'] = compact('category', 'level', 'search');

        return view('learning.courses', $data);
    }

    /**
     * Show specific course
     */
    public function showCourse($courseId)
    {
        $data = [];

        $course = LearningCourse::with(['lessons', 'instructor', 'category'])
            ->findOrFail($courseId);

        // Meta Tags
        MetaTag::set('title', $course->title . ' - Learning Platform');
        MetaTag::set('description', $course->description);

        $data['course'] = $course;

        // Get user progress if authenticated
        if (Auth::check()) {
            $data['progress'] = UserCourseProgress::where([
                'user_id' => Auth::id(),
                'course_id' => $course->id
            ])->first();

            $data['completedLessons'] = $this->learningService
                ->getUserCompletedLessons(Auth::id(), $course->id);
        }

        // Get related courses
        $data['relatedCourses'] = LearningCourse::active()
            ->where('category_id', $course->category_id)
            ->where('id', '!=', $course->id)
            ->limit(4)
            ->get();

        return view('learning.course-show', $data);
    }

    /**
     * Show specific lesson
     */
    public function showLesson($courseId, $lessonId)
    {
        $data = [];

        $course = LearningCourse::findOrFail($courseId);
        $lesson = LearningLesson::where('course_id', $course->id)
            ->findOrFail($lessonId);

        // Meta Tags
        MetaTag::set('title', $lesson->title . ' - ' . $course->title);
        MetaTag::set('description', $lesson->description);

        $data['course'] = $course;
        $data['lesson'] = $lesson;

        // Get all lessons for navigation
        $data['lessons'] = LearningLesson::where('course_id', $course->id)
            ->orderBy('order_index')
            ->get();

        // Mark lesson as viewed if authenticated
        if (Auth::check()) {
            $this->learningService->markLessonAsViewed(Auth::id(), $lesson->id);

            // Get user progress
            $data['progress'] = $this->learningService
                ->getUserCourseProgress(Auth::id(), $course->id);
        }

        return view('learning.lesson-show', $data);
    }

    /**
     * Show IDE interface
     */
    public function ide()
    {
        $data = [];

        // Meta Tags
        MetaTag::set('title', 'Code Editor - Learning Platform');
        MetaTag::set('description', 'Practice coding with our online IDE');

        // Get supported languages
        $data['languages'] = [
            'javascript' => 'JavaScript',
            'python' => 'Python',
            'php' => 'PHP',
            'java' => 'Java',
            'html' => 'HTML/CSS'
        ];

        // Get user's recent code snippets
        if (Auth::check()) {
            $data['recentSnippets'] = \App\Models\CodeSnippet::where('user_id', Auth::id())
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();
        }

        return view('learning.ide', $data);
    }

    /**
     * Show assessments
     */
    public function assessments()
    {
        $data = [];

        // Meta Tags
        MetaTag::set('title', 'Assessments - Learning Platform');
        MetaTag::set('description', 'Test your knowledge with our assessments');

        // Get available assessments
        $data['assessments'] = \App\Models\Assessment::active()
            ->with('category')
            ->paginate(12);

        // Get user's completed assessments if authenticated
        if (Auth::check()) {
            $data['userAssessments'] = \App\Models\UserAssessment::where('user_id', Auth::id())
                ->with('assessment')
                ->get()
                ->keyBy('assessment_id');
        }

        return view('learning.assessments', $data);
    }
}