<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserModuleProgress extends BaseModel
{
    protected $fillable = [
        'user_id', 'course_module_id', 'status', 'time_spent_minutes',
        'answers', 'score', 'notes', 'started_at', 'completed_at'
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course module
     */
    public function courseModule(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class);
    }

    /**
     * Mark as started
     */
    public function markAsStarted(): void
    {
        if ($this->status === 'not_started') {
            $this->status = 'in_progress';
            $this->started_at = now();
            $this->save();
        }
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted($score = null): void
    {
        $this->status = 'completed';
        $this->completed_at = now();

        if ($score !== null) {
            $this->score = $score;
        }

        $this->save();
    }

    /**
     * Add time spent
     */
    public function addTimeSpent(int $minutes): void
    {
        $this->time_spent_minutes += $minutes;
        $this->save();
    }

    /**
     * Save quiz/exercise answers
     */
    public function saveAnswers(array $answers, $score = null): void
    {
        $this->answers = $answers;

        if ($score !== null) {
            $this->score = $score;
        }

        $this->save();
    }

    /**
     * Check if completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Get completion percentage (for individual module)
     */
    public function getCompletionPercentage(): int
    {
        switch ($this->status) {
            case 'completed':
                return 100;
            case 'in_progress':
                return 50; // Could be more sophisticated based on actual progress
            default:
                return 0;
        }
    }

    /**
     * Scope for completed modules
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for in progress modules
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
}