<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class AtsConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'provider',
        'api_endpoint',
        'credentials',
        'configuration',
        'is_active',
        'last_sync_at',
        'sync_stats',
        'field_mapping'
    ];

    protected $casts = [
        'credentials' => 'array', // Changed from encrypted:array to array for seeding
        'configuration' => 'array',
        'sync_stats' => 'array',
        'field_mapping' => 'array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(AtsJobPosting::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(AtsCandidate::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(AtsSyncLog::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(AtsWebhook::class);
    }

    public function fieldMappings(): HasMany
    {
        return $this->hasMany(AtsFieldMapping::class);
    }

    public function getProviderNameAttribute(): string
    {
        return match($this->provider) {
            'workday' => 'Workday',
            'greenhouse' => 'Greenhouse',
            'lever' => 'Lever',
            'bamboohr' => 'BambooHR',
            'successfactors' => 'SAP SuccessFactors',
            'taleo' => 'Oracle Taleo',
            'icims' => 'iCIMS',
            'jazz' => 'JazzHR',
            'bullhorn' => 'Bullhorn',
            'jobvite' => 'Jobvite',
            default => ucfirst($this->provider)
        };
    }

    public function canSync(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check rate limits based on provider
        $rateLimits = [
            'workday' => 100, // requests per hour
            'greenhouse' => 200,
            'lever' => 150,
            'bamboohr' => 50,
            'successfactors' => 75,
            'taleo' => 100,
            'icims' => 120,
            'jazz' => 180,
            'bullhorn' => 250,
            'jobvite' => 160
        ];

        $hourlyLimit = $rateLimits[$this->provider] ?? 100;

        // Check recent sync logs for rate limiting
        $recentSyncs = $this->syncLogs()
            ->where('started_at', '>=', now()->subHour())
            ->count();

        return $recentSyncs < $hourlyLimit;
    }

    public function getApiHeaders(): array
    {
        $credentials = $this->credentials;

        return match($this->provider) {
            'workday' => [
                'Authorization' => 'Basic ' . base64_encode($credentials['username'] . ':' . $credentials['password']),
                'Content-Type' => 'application/json'
            ],
            'greenhouse' => [
                'Authorization' => 'Basic ' . base64_encode($credentials['api_key'] . ':'),
                'Content-Type' => 'application/json'
            ],
            'lever' => [
                'Authorization' => 'Bearer ' . $credentials['api_key'],
                'Content-Type' => 'application/json'
            ],
            'bamboohr' => [
                'Authorization' => 'Basic ' . base64_encode($credentials['api_key'] . ':x'),
                'Content-Type' => 'application/json'
            ],
            'successfactors' => [
                'Authorization' => 'Bearer ' . $credentials['oauth_token'],
                'Content-Type' => 'application/json'
            ],
            'taleo' => [
                'Cookie' => 'authToken=' . $credentials['auth_token'],
                'Content-Type' => 'application/json'
            ],
            'icims' => [
                'Authorization' => 'Bearer ' . $credentials['access_token'],
                'Content-Type' => 'application/json'
            ],
            'jazz' => [
                'Authorization' => 'Basic ' . base64_encode($credentials['api_key'] . ':'),
                'Content-Type' => 'application/json'
            ],
            'bullhorn' => [
                'BhRestToken' => $credentials['rest_token'],
                'Content-Type' => 'application/json'
            ],
            'jobvite' => [
                'Authorization' => 'Bearer ' . $credentials['api_key'],
                'Content-Type' => 'application/json'
            ],
            default => [
                'Content-Type' => 'application/json'
            ]
        };
    }

    public function getJobsEndpoint(): string
    {
        return match($this->provider) {
            'workday' => $this->api_endpoint . '/jobs',
            'greenhouse' => $this->api_endpoint . '/v1/jobs',
            'lever' => $this->api_endpoint . '/v1/postings',
            'bamboohr' => $this->api_endpoint . '/v1/meta/jobs',
            'successfactors' => $this->api_endpoint . '/odata/v2/JobRequisition',
            'taleo' => $this->api_endpoint . '/object/requisition/search',
            'icims' => $this->api_endpoint . '/customers/' . $this->credentials['customer_id'] . '/jobs',
            'jazz' => $this->api_endpoint . '/recruiting/jobs',
            'bullhorn' => $this->api_endpoint . '/search/JobOrder',
            'jobvite' => $this->api_endpoint . '/v2/jobs',
            default => $this->api_endpoint . '/jobs'
        };
    }

    public function getCandidatesEndpoint(): string
    {
        return match($this->provider) {
            'workday' => $this->api_endpoint . '/candidates',
            'greenhouse' => $this->api_endpoint . '/v1/candidates',
            'lever' => $this->api_endpoint . '/v1/opportunities',
            'bamboohr' => $this->api_endpoint . '/v1/applicants',
            'successfactors' => $this->api_endpoint . '/odata/v2/Candidate',
            'taleo' => $this->api_endpoint . '/object/candidate/search',
            'icims' => $this->api_endpoint . '/customers/' . $this->credentials['customer_id'] . '/people',
            'jazz' => $this->api_endpoint . '/recruiting/applicants',
            'bullhorn' => $this->api_endpoint . '/search/Candidate',
            'jobvite' => $this->api_endpoint . '/v2/candidates',
            default => $this->api_endpoint . '/candidates'
        };
    }

    public function updateSyncStats(array $stats): void
    {
        $this->update([
            'sync_stats' => array_merge($this->sync_stats ?? [], $stats),
            'last_sync_at' => now()
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}