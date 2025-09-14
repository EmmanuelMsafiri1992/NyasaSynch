<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningPath extends BaseModel
{
    protected $fillable = [
        'title', 'slug', 'description', 'category', 'level', 'course_ids',
        'total_duration_hours', 'thumbnail_url', 'is_published', 'enrolled_count'
    ];

    protected $casts = [
        'course_ids' => 'array',
        'is_published' => 'boolean',
    ];

    /**
     * Get the courses in this learning path
     */
    public function courses()
    {
        return Course::whereIn('id', $this->course_ids ?? [])
            ->orderByRaw('FIELD(id, ' . implode(',', $this->course_ids ?? []) . ')');
    }

    /**
     * Get enrolled users
     */
    public function enrolledUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_learning_paths')
            ->withPivot(['started_at', 'completed_at', 'progress_percentage'])
            ->withTimestamps();
    }

    /**
     * Get certificates for this learning path
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Calculate total estimated hours
     */
    public function calculateTotalHours(): int
    {
        $courses = $this->courses()->get();
        return $courses->sum('duration_hours');
    }

    /**
     * Get course count
     */
    public function getCourseCount(): int
    {
        return count($this->course_ids ?? []);
    }

    /**
     * Check if user is enrolled in this path
     */
    public function isUserEnrolled($userId): bool
    {
        return $this->enrolledUsers()->where('user_id', $userId)->exists();
    }

    /**
     * Get user's progress in this learning path
     */
    public function getUserProgress($userId)
    {
        return $this->enrolledUsers()
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Calculate user's completion percentage
     */
    public function calculateUserProgress($userId): int
    {
        if (!$this->course_ids || empty($this->course_ids)) {
            return 0;
        }

        $totalCourses = count($this->course_ids);
        $completedCourses = 0;

        foreach ($this->course_ids as $courseId) {
            $enrollment = \DB::table('user_course_enrollments')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('progress_percentage', 100)
                ->exists();

            if ($enrollment) {
                $completedCourses++;
            }
        }

        return $totalCourses > 0 ? round(($completedCourses / $totalCourses) * 100) : 0;
    }

    /**
     * Scope for published learning paths
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for paths by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope for paths by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}