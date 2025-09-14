<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAggregationSyncLog extends BaseModel
{
    protected $fillable = [
        'aggregation_source_id', 'sync_started_at', 'sync_completed_at', 'status',
        'jobs_processed', 'jobs_created', 'jobs_updated', 'jobs_skipped',
        'jobs_failed', 'error_message', 'sync_details'
    ];

    protected $casts = [
        'sync_started_at' => 'datetime',
        'sync_completed_at' => 'datetime',
        'sync_details' => 'array',
    ];

    /**
     * Get the aggregation source
     */
    public function aggregationSource(): BelongsTo
    {
        return $this->belongsTo(JobAggregationSource::class);
    }

    /**
     * Check if sync was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if sync failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get sync duration in seconds
     */
    public function getDurationSeconds(): ?int
    {
        if (!$this->sync_completed_at || !$this->sync_started_at) {
            return null;
        }

        return $this->sync_completed_at->diffInSeconds($this->sync_started_at);
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        if ($this->jobs_processed === 0) {
            return 0;
        }

        $successful = $this->jobs_created + $this->jobs_updated;
        return round(($successful / $this->jobs_processed) * 100, 2);
    }

    /**
     * Scope for successful syncs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed syncs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for recent syncs
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('sync_started_at', '>=', now()->subHours($hours));
    }
}