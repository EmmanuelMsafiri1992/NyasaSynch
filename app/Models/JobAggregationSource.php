<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobAggregationSource extends BaseModel
{
    protected $fillable = [
        'name', 'slug', 'api_url', 'api_type', 'api_config', 'api_key',
        'field_mapping', 'rate_limit_per_hour', 'is_active', 'priority',
        'supported_countries', 'supported_categories', 'last_sync_at',
        'jobs_synced_today'
    ];

    protected $casts = [
        'api_config' => 'array',
        'field_mapping' => 'array',
        'is_active' => 'boolean',
        'supported_countries' => 'array',
        'supported_categories' => 'array',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get aggregated jobs from this source
     */
    public function aggregatedJobs(): HasMany
    {
        return $this->hasMany(AggregatedJob::class, 'aggregation_source_id');
    }

    /**
     * Get sync logs for this source
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(JobAggregationSyncLog::class, 'aggregation_source_id');
    }

    /**
     * Get active aggregated jobs
     */
    public function activeJobs(): HasMany
    {
        return $this->aggregatedJobs()->where('is_active', true);
    }

    /**
     * Check if source is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if we can sync (within rate limits)
     */
    public function canSync(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $lastSync = $this->last_sync_at;
        $cooldownMinutes = $this->getCooldownMinutes();

        if ($lastSync && $lastSync->addMinutes($cooldownMinutes)->isFuture()) {
            return false;
        }

        return $this->jobs_synced_today < $this->rate_limit_per_hour * 24;
    }

    /**
     * Get cooldown minutes between syncs
     */
    public function getCooldownMinutes(): int
    {
        return max(1, 60 / $this->rate_limit_per_hour);
    }

    /**
     * Update sync status
     */
    public function updateSyncStatus(): void
    {
        $this->last_sync_at = now();
        $this->increment('jobs_synced_today');
        $this->save();
    }

    /**
     * Reset daily counter
     */
    public function resetDailyCounter(): void
    {
        $this->jobs_synced_today = 0;
        $this->save();
    }

    /**
     * Get API headers for authentication
     */
    public function getApiHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'Nyasajob/1.0',
        ];

        if ($this->api_key) {
            switch ($this->api_type) {
                case 'indeed':
                    $headers['Authorization'] = 'Bearer ' . $this->api_key;
                    break;
                case 'linkedin':
                    $headers['Authorization'] = 'Bearer ' . $this->api_key;
                    break;
                case 'glassdoor':
                    $headers['X-API-Key'] = $this->api_key;
                    break;
                default:
                    $headers['Authorization'] = 'Bearer ' . $this->api_key;
            }
        }

        return $headers;
    }

    /**
     * Get API parameters
     */
    public function getApiParams(array $filters = []): array
    {
        $params = $this->api_config['default_params'] ?? [];

        // Add filters
        if (isset($filters['location'])) {
            $params[$this->field_mapping['location_param']] = $filters['location'];
        }

        if (isset($filters['keywords'])) {
            $params[$this->field_mapping['keywords_param']] = $filters['keywords'];
        }

        if (isset($filters['category'])) {
            $params[$this->field_mapping['category_param']] = $filters['category'];
        }

        return $params;
    }

    /**
     * Scope for active sources
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Scope by API type
     */
    public function scopeByApiType($query, $type)
    {
        return $query->where('api_type', $type);
    }
}