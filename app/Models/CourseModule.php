<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends BaseModel
{
    protected $fillable = [
        'course_id', 'title', 'description', 'type', 'content', 'video_url',
        'coding_template', 'quiz_data', 'duration_minutes', 'sort_order',
        'is_preview', 'files'
    ];

    protected $casts = [
        'coding_template' => 'array',
        'quiz_data' => 'array',
        'is_preview' => 'boolean',
        'files' => 'array',
    ];

    /**
     * Get the course that owns this module
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get user progress for this module
     */
    public function userProgress(): HasMany
    {
        return $this->hasMany(UserModuleProgress::class);
    }

    /**
     * Get coding workspaces for this module
     */
    public function codingWorkspaces(): HasMany
    {
        return $this->hasMany(CodingWorkspace::class);
    }

    /**
     * Check if module is video type
     */
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Check if module is coding exercise
     */
    public function isCodingExercise(): bool
    {
        return $this->type === 'coding_exercise';
    }

    /**
     * Check if module is quiz
     */
    public function isQuiz(): bool
    {
        return $this->type === 'quiz';
    }

    /**
     * Check if module is available as preview
     */
    public function isPreview(): bool
    {
        return $this->is_preview;
    }

    /**
     * Get user's progress for this module
     */
    public function getUserProgress($userId)
    {
        return $this->userProgress()->where('user_id', $userId)->first();
    }

    /**
     * Check if user has completed this module
     */
    public function isCompletedByUser($userId): bool
    {
        $progress = $this->getUserProgress($userId);
        return $progress && $progress->status === 'completed';
    }

    /**
     * Scope for modules by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for preview modules
     */
    public function scopePreview($query)
    {
        return $query->where('is_preview', true);
    }
}