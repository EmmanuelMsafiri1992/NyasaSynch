<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseReview extends BaseModel
{
    protected $fillable = [
        'user_id', 'course_id', 'rating', 'review', 'is_verified_purchase', 'is_published'
    ];

    protected $casts = [
        'is_verified_purchase' => 'boolean',
        'is_published' => 'boolean',
    ];

    /**
     * Get the user who wrote the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course being reviewed
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Check if review is from a verified purchaser/enrollee
     */
    public function isVerified(): bool
    {
        return $this->is_verified_purchase;
    }

    /**
     * Check if review is published
     */
    public function isPublished(): bool
    {
        return $this->is_published;
    }

    /**
     * Scope for published reviews
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for verified reviews
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope for reviews by rating
     */
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }
}