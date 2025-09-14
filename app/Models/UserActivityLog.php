<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_data',
        'activity_date'
    ];

    protected $casts = [
        'activity_data' => 'array',
        'activity_date' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log user activity
     */
    public static function logActivity(int $userId, string $activityType, array $data = []): self
    {
        return self::create([
            'user_id' => $userId,
            'activity_type' => $activityType,
            'activity_data' => $data,
            'activity_date' => now()
        ]);
    }

    /**
     * Activity types constants
     */
    const ACTIVITY_TYPES = [
        'login' => 'User Login',
        'profile_update' => 'Profile Update',
        'application_sent' => 'Job Application Sent',
        'message_sent' => 'Message Sent',
        'message_responded' => 'Message Responded',
        'resume_uploaded' => 'Resume Uploaded',
        'skill_added' => 'Skill Added',
        'profile_viewed' => 'Profile Viewed by Employer',
        'interview_scheduled' => 'Interview Scheduled',
        'interview_attended' => 'Interview Attended',
        'job_hired' => 'Hired for Job',
        'verification_completed' => 'Verification Completed'
    ];

    /**
     * Scope for recent activity
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('activity_date', '>=', now()->subDays($days));
    }

    /**
     * Scope for specific activity type
     */
    public function scopeOfType($query, string $activityType)
    {
        return $query->where('activity_type', $activityType);
    }
}