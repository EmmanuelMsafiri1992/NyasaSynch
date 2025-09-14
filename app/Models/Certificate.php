<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends BaseModel
{
    protected $fillable = [
        'user_id', 'course_id', 'learning_path_id', 'certificate_id', 'title',
        'description', 'score', 'instructor_name', 'issued_at', 'expires_at',
        'verification_data', 'pdf_url'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'verification_data' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($certificate) {
            if (!$certificate->certificate_id) {
                $certificate->certificate_id = 'CERT-' . strtoupper(Str::random(12));
            }

            if (!$certificate->issued_at) {
                $certificate->issued_at = now();
            }
        });
    }

    /**
     * Get the user who earned this certificate
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course this certificate is for
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the learning path this certificate is for
     */
    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    /**
     * Check if certificate is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if certificate is valid
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Get verification URL
     */
    public function getVerificationUrl(): string
    {
        return url("/certificates/verify/{$this->certificate_id}");
    }

    /**
     * Generate PDF certificate
     */
    public function generatePdf(): string
    {
        // This would integrate with a PDF generation service
        // For now, return a placeholder URL
        $filename = "certificate_{$this->certificate_id}.pdf";
        $this->pdf_url = "/certificates/pdf/{$filename}";
        $this->save();

        return $this->pdf_url;
    }

    /**
     * Get certificate type (course or learning path)
     */
    public function getType(): string
    {
        if ($this->course_id) {
            return 'course';
        } elseif ($this->learning_path_id) {
            return 'learning_path';
        }

        return 'unknown';
    }

    /**
     * Get certificate subject (course or learning path title)
     */
    public function getSubject(): string
    {
        if ($this->course) {
            return $this->course->title;
        } elseif ($this->learningPath) {
            return $this->learningPath->title;
        }

        return $this->title;
    }

    /**
     * Scope for certificates by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for valid certificates
     */
    public function scopeValid($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for course certificates
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope for learning path certificates
     */
    public function scopeForLearningPath($query, $pathId)
    {
        return $query->where('learning_path_id', $pathId);
    }
}