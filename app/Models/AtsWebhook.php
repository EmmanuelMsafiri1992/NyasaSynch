<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtsWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'ats_connection_id',
        'webhook_id',
        'event_type',
        'payload',
        'status',
        'error_message',
        'retry_count',
        'received_at',
        'processed_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'retry_count' => 'integer',
        'received_at' => 'datetime',
        'processed_at' => 'datetime'
    ];

    public function atsConnection(): BelongsTo
    {
        return $this->belongsTo(AtsConnection::class);
    }

    public function getEventTypeDisplayAttribute(): string
    {
        return match($this->event_type) {
            'job_created' => 'Job Created',
            'job_updated' => 'Job Updated',
            'job_closed' => 'Job Closed',
            'application_submitted' => 'Application Submitted',
            'application_updated' => 'Application Updated',
            'candidate_created' => 'Candidate Created',
            'candidate_updated' => 'Candidate Updated',
            'interview_scheduled' => 'Interview Scheduled',
            'offer_extended' => 'Offer Extended',
            'hire_completed' => 'Hire Completed',
            default => ucwords(str_replace('_', ' ', $this->event_type))
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processed' => 'Processed',
            'failed' => 'Failed',
            default => ucfirst($this->status)
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'processed' => 'green',
            'failed' => 'red',
            default => 'gray'
        };
    }

    public function getProcessingTimeAttribute(): ?int
    {
        if (!$this->processed_at || !$this->received_at) {
            return null;
        }

        return $this->received_at->diffInSeconds($this->processed_at);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('received_at', '>=', now()->subHours($hours));
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', 'failed')
                    ->where('retry_count', '<', 3);
    }

    public function markProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
            'error_message' => null
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'processed_at' => now()
        ]);
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }

    public function resetForRetry(): void
    {
        $this->update([
            'status' => 'pending',
            'error_message' => null,
            'processed_at' => null
        ]);
    }

    public function getPayloadSummary(): array
    {
        if (!$this->payload || !is_array($this->payload)) {
            return [];
        }

        // Extract key information based on event type
        return match($this->event_type) {
            'job_created', 'job_updated' => [
                'job_id' => $this->payload['job_id'] ?? null,
                'title' => $this->payload['title'] ?? null,
                'department' => $this->payload['department'] ?? null,
                'status' => $this->payload['status'] ?? null
            ],
            'application_submitted', 'application_updated' => [
                'application_id' => $this->payload['application_id'] ?? null,
                'job_id' => $this->payload['job_id'] ?? null,
                'candidate_id' => $this->payload['candidate_id'] ?? null,
                'status' => $this->payload['status'] ?? null
            ],
            'candidate_created', 'candidate_updated' => [
                'candidate_id' => $this->payload['candidate_id'] ?? null,
                'name' => ($this->payload['first_name'] ?? '') . ' ' . ($this->payload['last_name'] ?? ''),
                'email' => $this->payload['email'] ?? null
            ],
            default => array_slice($this->payload, 0, 5, true) // First 5 keys for other events
        };
    }
}