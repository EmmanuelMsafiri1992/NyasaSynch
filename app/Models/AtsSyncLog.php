<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtsSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ats_connection_id',
        'sync_type',
        'status',
        'filters',
        'records_processed',
        'records_created',
        'records_updated',
        'records_failed',
        'errors',
        'started_at',
        'completed_at',
        'duration_seconds'
    ];

    protected $casts = [
        'filters' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'decimal:2',
        'records_processed' => 'integer',
        'records_created' => 'integer',
        'records_updated' => 'integer',
        'records_failed' => 'integer'
    ];

    public function atsConnection(): BelongsTo
    {
        return $this->belongsTo(AtsConnection::class);
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->records_processed === 0) {
            return 0;
        }

        $successful = $this->records_processed - $this->records_failed;
        return round(($successful / $this->records_processed) * 100, 2);
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }

        if ($this->duration_seconds < 60) {
            return round($this->duration_seconds, 2) . 's';
        } elseif ($this->duration_seconds < 3600) {
            return round($this->duration_seconds / 60, 2) . 'm';
        } else {
            return round($this->duration_seconds / 3600, 2) . 'h';
        }
    }

    public function getSyncTypeDisplayAttribute(): string
    {
        return match($this->sync_type) {
            'jobs' => 'Job Postings',
            'candidates' => 'Candidates',
            'applications' => 'Applications',
            'full' => 'Full Sync',
            default => ucfirst($this->sync_type)
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'started' => 'In Progress',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => ucfirst($this->status)
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'started' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray'
        };
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('sync_type', $type);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration_seconds' => $this->started_at->diffInSeconds(now())
        ]);
    }

    public function markFailed(array $errors = []): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'errors' => array_merge($this->errors ?? [], $errors),
            'duration_seconds' => $this->started_at->diffInSeconds(now())
        ]);
    }

    public function updateProgress(int $processed, int $created = 0, int $updated = 0, int $failed = 0): void
    {
        $this->update([
            'records_processed' => $processed,
            'records_created' => $this->records_created + $created,
            'records_updated' => $this->records_updated + $updated,
            'records_failed' => $this->records_failed + $failed
        ]);
    }
}