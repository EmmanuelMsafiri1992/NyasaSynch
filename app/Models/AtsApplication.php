<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtsApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'ats_job_posting_id',
        'ats_candidate_id',
        'external_application_id',
        'status',
        'cover_letter',
        'attachments',
        'questionnaire_responses',
        'offered_salary',
        'applied_at',
        'status_updated_at',
        'rejection_reason',
        'interview_notes',
        'assessment_scores',
        'custom_fields'
    ];

    protected $casts = [
        'attachments' => 'array',
        'questionnaire_responses' => 'array',
        'interview_notes' => 'array',
        'assessment_scores' => 'array',
        'custom_fields' => 'array',
        'offered_salary' => 'decimal:2',
        'applied_at' => 'datetime',
        'status_updated_at' => 'datetime'
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(AtsJobPosting::class, 'ats_job_posting_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(AtsCandidate::class, 'ats_candidate_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'new' => 'New Application',
            'screening' => 'Under Screening',
            'interview' => 'Interview Stage',
            'assessment' => 'Assessment Stage',
            'offer' => 'Offer Extended',
            'hired' => 'Hired',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
            default => ucfirst($this->status)
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'new' => 'blue',
            'screening' => 'yellow',
            'interview' => 'orange',
            'assessment' => 'purple',
            'offer' => 'green',
            'hired' => 'emerald',
            'rejected' => 'red',
            'withdrawn' => 'gray',
            default => 'gray'
        };
    }

    public function getDaysInStageAttribute(): int
    {
        $baseDate = $this->status_updated_at ?? $this->applied_at;
        return $baseDate->diffInDays(now());
    }

    public function getFormattedOfferedSalaryAttribute(): ?string
    {
        if (!$this->offered_salary) {
            return null;
        }

        return '$' . number_format($this->offered_salary);
    }

    public function hasAttachments(): bool
    {
        return $this->attachments && count($this->attachments) > 0;
    }

    public function getResumeAttachmentAttribute(): ?array
    {
        if (!$this->attachments || !is_array($this->attachments)) {
            return null;
        }

        return collect($this->attachments)->first(function($attachment) {
            return str_contains(strtolower($attachment['name'] ?? ''), 'resume') ||
                   str_contains(strtolower($attachment['name'] ?? ''), 'cv');
        });
    }

    public function getCoverLetterPreviewAttribute(): ?string
    {
        if (!$this->cover_letter) {
            return null;
        }

        return strlen($this->cover_letter) > 200
            ? substr($this->cover_letter, 0, 200) . '...'
            : $this->cover_letter;
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['rejected', 'withdrawn']);
    }

    public function scopeInProcess($query)
    {
        return $query->whereIn('status', ['new', 'screening', 'interview', 'assessment']);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['hired', 'rejected', 'withdrawn']);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('applied_at', '>=', now()->subDays($days));
    }

    public function scopeByJobTitle($query, string $title)
    {
        return $query->whereHas('jobPosting', function($q) use ($title) {
            $q->where('title', 'like', '%' . $title . '%');
        });
    }

    public function scopeByCandidate($query, string $candidateName)
    {
        return $query->whereHas('candidate', function($q) use ($candidateName) {
            $q->where('first_name', 'like', '%' . $candidateName . '%')
              ->orWhere('last_name', 'like', '%' . $candidateName . '%');
        });
    }

    public function updateStatus(string $newStatus, ?string $reason = null): void
    {
        $this->update([
            'status' => $newStatus,
            'status_updated_at' => now(),
            'rejection_reason' => $newStatus === 'rejected' ? $reason : $this->rejection_reason
        ]);
    }

    public function addInterviewNote(string $note, ?string $interviewer = null): void
    {
        $notes = $this->interview_notes ?? [];
        $notes[] = [
            'note' => $note,
            'interviewer' => $interviewer,
            'created_at' => now()->toISOString()
        ];

        $this->update(['interview_notes' => $notes]);
    }

    public function setAssessmentScore(string $assessment, $score, ?string $notes = null): void
    {
        $scores = $this->assessment_scores ?? [];
        $scores[$assessment] = [
            'score' => $score,
            'notes' => $notes,
            'assessed_at' => now()->toISOString()
        ];

        $this->update(['assessment_scores' => $scores]);
    }
}