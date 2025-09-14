<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends BaseModel
{
    protected $fillable = [
        'title', 'slug', 'description', 'short_description', 'category', 'difficulty', 'type',
        'price', 'language', 'duration_hours', 'learning_objectives', 'prerequisites',
        'instructor_name', 'instructor_bio', 'thumbnail_url', 'video_intro_url',
        'has_certificate', 'is_published', 'enrolled_count', 'rating', 'reviews_count'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'learning_objectives' => 'array',
        'prerequisites' => 'array',
        'has_certificate' => 'boolean',
        'is_published' => 'boolean',
        'rating' => 'decimal:2',
    ];

    /**
     * Get the course modules
     */
    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort_order');
    }

    /**
     * Get enrolled users
     */
    public function enrolledUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_course_enrollments')
            ->withPivot(['enrolled_at', 'started_at', 'completed_at', 'progress_percentage', 'current_score'])
            ->withTimestamps();
    }

    /**
     * Get course reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    /**
     * Get certificates for this course
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Check if course is free
     */
    public function isFree(): bool
    {
        return $this->type === 'free' || $this->price == 0;
    }

    /**
     * Get average rating
     */
    public function getAverageRating(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Get total duration in minutes
     */
    public function getTotalDurationMinutes(): int
    {
        return $this->modules()->sum('duration_minutes');
    }

    /**
     * Scope for published courses
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for courses by difficulty
     */
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Scope for courses by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for free courses
     */
    public function scopeFree($query)
    {
        return $query->where('type', 'free');
    }

    /**
     * Scope for premium courses
     */
    public function scopePremium($query)
    {
        return $query->whereIn('type', ['premium', 'enterprise']);
    }
}