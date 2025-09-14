<?php

namespace App\Services;

use App\Models\UserLearningProgress;
use App\Models\LearningPath;
use App\Models\Course;

class LearningService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get user's recent learning progress
     */
    public function getUserRecentProgress(int $userId, int $limit = 5): array
    {
        // For now, return empty array if tables don't exist
        try {
            return UserLearningProgress::where('user_id', $userId)
                ->with(['course', 'lesson'])
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get user's learning statistics
     */
    public function getUserLearningStats(int $userId): array
    {
        // For now, return basic stats
        try {
            $totalCourses = Course::count();
            $completedCourses = UserLearningProgress::where('user_id', $userId)
                ->where('status', 'completed')
                ->distinct('course_id')
                ->count();

            return [
                'total_courses' => $totalCourses,
                'completed_courses' => $completedCourses,
                'in_progress' => UserLearningProgress::where('user_id', $userId)
                    ->where('status', 'in_progress')
                    ->distinct('course_id')
                    ->count(),
                'completion_rate' => $totalCourses > 0 ? round(($completedCourses / $totalCourses) * 100, 1) : 0
            ];
        } catch (\Exception $e) {
            return [
                'total_courses' => 0,
                'completed_courses' => 0,
                'in_progress' => 0,
                'completion_rate' => 0
            ];
        }
    }

    /**
     * Mark a lesson as viewed by user
     */
    public function markLessonAsViewed(int $userId, int $lessonId): bool
    {
        // For now, just return true as if it worked
        try {
            UserLearningProgress::updateOrCreate(
                ['user_id' => $userId, 'lesson_id' => $lessonId],
                ['status' => 'viewed', 'viewed_at' => now()]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
