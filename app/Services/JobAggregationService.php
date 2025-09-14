<?php

namespace App\Services;

use App\Models\JobAggregationSource;
use App\Models\AggregatedJob;
use App\Models\JobAggregationSyncLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class JobAggregationService
{
    /**
     * Sync jobs from all active sources
     */
    public function syncAllSources(array $filters = []): array
    {
        $sources = JobAggregationSource::active()
            ->byPriority()
            ->get();

        $results = [];

        foreach ($sources as $source) {
            try {
                $result = $this->syncSource($source, $filters);
                $results[$source->slug] = $result;
            } catch (\Exception $e) {
                Log::error("Failed to sync source {$source->slug}: " . $e->getMessage());
                $results[$source->slug] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'jobs_processed' => 0
                ];
            }
        }

        return $results;
    }

    /**
     * Sync jobs from a specific source
     */
    public function syncSource(JobAggregationSource $source, array $filters = []): array
    {
        if (!$source->canSync()) {
            return [
                'success' => false,
                'error' => 'Source cannot sync due to rate limits or inactive status',
                'jobs_processed' => 0
            ];
        }

        $syncLog = JobAggregationSyncLog::create([
            'aggregation_source_id' => $source->id,
            'sync_started_at' => now(),
            'status' => 'running'
        ]);

        try {
            $jobs = $this->fetchJobsFromSource($source, $filters);
            $result = $this->processJobs($source, $jobs, $syncLog);

            $syncLog->update([
                'sync_completed_at' => now(),
                'status' => 'completed',
                'jobs_processed' => $result['jobs_processed'],
                'jobs_created' => $result['jobs_created'],
                'jobs_updated' => $result['jobs_updated'],
                'jobs_skipped' => $result['jobs_skipped'],
                'jobs_failed' => $result['jobs_failed'],
                'sync_details' => $result
            ]);

            $source->updateSyncStatus();

            return array_merge($result, ['success' => true]);

        } catch (\Exception $e) {
            $syncLog->update([
                'sync_completed_at' => now(),
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Fetch jobs from external API
     */
    protected function fetchJobsFromSource(JobAggregationSource $source, array $filters = []): array
    {
        $url = $source->api_url;
        $headers = $source->getApiHeaders();
        $params = $source->getApiParams($filters);

        switch ($source->api_type) {
            case 'indeed':
                return $this->fetchFromIndeed($source, $url, $headers, $params);

            case 'linkedin':
                return $this->fetchFromLinkedIn($source, $url, $headers, $params);

            case 'glassdoor':
                return $this->fetchFromGlassdoor($source, $url, $headers, $params);

            case 'ziprecruiter':
                return $this->fetchFromZipRecruiter($source, $url, $headers, $params);

            case 'json_api':
                return $this->fetchFromJsonApi($source, $url, $headers, $params);

            case 'generic_rss':
                return $this->fetchFromRss($source, $url, $headers, $params);

            default:
                return $this->fetchFromGenericApi($source, $url, $headers, $params);
        }
    }

    /**
     * Fetch from Indeed API
     */
    protected function fetchFromIndeed(JobAggregationSource $source, string $url, array $headers, array $params): array
    {
        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->get($url, $params);

        if (!$response->successful()) {
            throw new \Exception("Indeed API request failed: " . $response->body());
        }

        $data = $response->json();

        return $data['results'] ?? [];
    }

    /**
     * Fetch from LinkedIn API
     */
    protected function fetchFromLinkedIn(JobAggregationSource $source, string $url, array $headers, array $params): array
    {
        // LinkedIn API implementation
        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->get($url, $params);

        if (!$response->successful()) {
            throw new \Exception("LinkedIn API request failed: " . $response->body());
        }

        $data = $response->json();

        return $data['elements'] ?? [];
    }

    /**
     * Fetch from generic JSON API
     */
    protected function fetchFromJsonApi(JobAggregationSource $source, string $url, array $headers, array $params): array
    {
        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->get($url, $params);

        if (!$response->successful()) {
            throw new \Exception("JSON API request failed: " . $response->body());
        }

        $data = $response->json();

        // Use configured path to extract jobs array
        $jobsPath = $source->field_mapping['jobs_array_path'] ?? 'jobs';

        return data_get($data, $jobsPath, []);
    }

    /**
     * Fetch from RSS feed
     */
    protected function fetchFromRss(JobAggregationSource $source, string $url, array $headers, array $params): array
    {
        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->get($url, $params);

        if (!$response->successful()) {
            throw new \Exception("RSS feed request failed: " . $response->body());
        }

        $xml = simplexml_load_string($response->body());
        $jobs = [];

        foreach ($xml->channel->item as $item) {
            $jobs[] = [
                'title' => (string) $item->title,
                'description' => (string) $item->description,
                'link' => (string) $item->link,
                'pubDate' => (string) $item->pubDate,
                'category' => (string) $item->category,
            ];
        }

        return $jobs;
    }

    /**
     * Fallback for other API types
     */
    protected function fetchFromGenericApi(JobAggregationSource $source, string $url, array $headers, array $params): array
    {
        // Mock data for demonstration
        return [
            [
                'id' => 'ext_' . uniqid(),
                'title' => 'Software Developer - Remote',
                'company' => 'Tech Company Inc',
                'location' => 'Remote',
                'description' => 'We are looking for a skilled software developer...',
                'posted_date' => now()->subDays(2)->toISOString(),
                'url' => 'https://example.com/job/123'
            ],
            [
                'id' => 'ext_' . uniqid(),
                'title' => 'Data Scientist',
                'company' => 'Analytics Corp',
                'location' => 'New York, NY',
                'description' => 'Join our data science team...',
                'posted_date' => now()->subDays(1)->toISOString(),
                'url' => 'https://example.com/job/124'
            ]
        ];
    }

    /**
     * Process fetched jobs and save to database
     */
    protected function processJobs(JobAggregationSource $source, array $jobs, JobAggregationSyncLog $syncLog): array
    {
        $result = [
            'jobs_processed' => 0,
            'jobs_created' => 0,
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
            'jobs_failed' => 0,
        ];

        foreach ($jobs as $jobData) {
            $result['jobs_processed']++;

            try {
                $mappedData = $this->mapJobData($source, $jobData);

                if (!$this->validateJobData($mappedData)) {
                    $result['jobs_skipped']++;
                    continue;
                }

                $existingJob = AggregatedJob::where('aggregation_source_id', $source->id)
                    ->where('external_id', $mappedData['external_id'])
                    ->first();

                if ($existingJob) {
                    $existingJob->update($mappedData);
                    $result['jobs_updated']++;
                } else {
                    AggregatedJob::create($mappedData);
                    $result['jobs_created']++;
                }

            } catch (\Exception $e) {
                Log::error("Failed to process job from {$source->slug}: " . $e->getMessage(), [
                    'job_data' => $jobData,
                    'error' => $e->getMessage()
                ]);
                $result['jobs_failed']++;
            }
        }

        return $result;
    }

    /**
     * Map external job data to our schema
     */
    protected function mapJobData(JobAggregationSource $source, array $jobData): array
    {
        $mapping = $source->field_mapping;

        $mapped = [
            'aggregation_source_id' => $source->id,
            'external_id' => data_get($jobData, $mapping['id_field']),
            'title' => data_get($jobData, $mapping['title_field']),
            'description' => data_get($jobData, $mapping['description_field']),
            'company_name' => data_get($jobData, $mapping['company_field']),
            'location' => data_get($jobData, $mapping['location_field']),
            'external_url' => data_get($jobData, $mapping['url_field']),
            'posted_at' => $this->parseDate(data_get($jobData, $mapping['posted_date_field'])),
            'is_active' => true,
            'raw_data' => $jobData,
        ];

        // Optional fields
        if (isset($mapping['salary_field'])) {
            $mapped['salary_range'] = data_get($jobData, $mapping['salary_field']);
        }

        if (isset($mapping['category_field'])) {
            $mapped['category'] = data_get($jobData, $mapping['category_field']);
        }

        if (isset($mapping['employment_type_field'])) {
            $mapped['employment_type'] = data_get($jobData, $mapping['employment_type_field']);
        }

        if (isset($mapping['skills_field'])) {
            $skills = data_get($jobData, $mapping['skills_field']);
            $mapped['skills'] = is_array($skills) ? $skills : explode(',', $skills ?? '');
        }

        // Extract country code from location
        $mapped['country_code'] = $this->extractCountryCode($mapped['location']);

        return array_filter($mapped); // Remove null values
    }

    /**
     * Validate job data before saving
     */
    protected function validateJobData(array $jobData): bool
    {
        return !empty($jobData['external_id']) &&
               !empty($jobData['title']) &&
               !empty($jobData['company_name']) &&
               !empty($jobData['external_url']);
    }

    /**
     * Parse various date formats
     */
    protected function parseDate($date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract country code from location string
     */
    protected function extractCountryCode(string $location): string
    {
        // Simple mapping - in production, you'd use a more sophisticated approach
        $countryMappings = [
            'USA' => 'US',
            'United States' => 'US',
            'UK' => 'GB',
            'United Kingdom' => 'GB',
            'Canada' => 'CA',
            'Australia' => 'AU',
            'Germany' => 'DE',
            'France' => 'FR',
            'India' => 'IN',
            'Singapore' => 'SG',
            'Remote' => 'US', // Default for remote jobs
        ];

        foreach ($countryMappings as $country => $code) {
            if (stripos($location, $country) !== false) {
                return $code;
            }
        }

        return 'US'; // Default
    }

    /**
     * Clean up old jobs
     */
    public function cleanupOldJobs(int $daysOld = 30): int
    {
        return AggregatedJob::where('posted_at', '<', now()->subDays($daysOld))
            ->orWhere('expires_at', '<', now())
            ->delete();
    }

    /**
     * Get sync statistics
     */
    public function getSyncStats(): array
    {
        return [
            'total_sources' => JobAggregationSource::count(),
            'active_sources' => JobAggregationSource::active()->count(),
            'total_jobs' => AggregatedJob::count(),
            'active_jobs' => AggregatedJob::active()->count(),
            'jobs_today' => AggregatedJob::whereDate('created_at', today())->count(),
            'last_sync' => JobAggregationSyncLog::latest('sync_started_at')->first()?->sync_started_at,
        ];
    }
}