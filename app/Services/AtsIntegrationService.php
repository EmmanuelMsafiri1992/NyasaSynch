<?php

namespace App\Services;

use App\Models\AtsConnection;
use App\Models\AtsJobPosting;
use App\Models\AtsCandidate;
use App\Models\AtsApplication;
use App\Models\AtsSyncLog;
use App\Models\AtsWebhook;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AtsIntegrationService
{
    public function createConnection(User $user, array $data): AtsConnection
    {
        return AtsConnection::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'provider' => $data['provider'],
            'api_endpoint' => $data['api_endpoint'],
            'credentials' => $data['credentials'],
            'configuration' => $data['configuration'] ?? [],
            'field_mapping' => $data['field_mapping'] ?? [],
            'is_active' => $data['is_active'] ?? true
        ]);
    }

    public function testConnection(AtsConnection $connection): array
    {
        try {
            $response = Http::withHeaders($connection->getApiHeaders())
                ->timeout(30)
                ->get($connection->getJobsEndpoint(), ['limit' => 1]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'response_time' => $response->transferStats->getTransferTime()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Connection failed: ' . $response->status(),
                    'error' => $response->body()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }

    public function syncAllConnections(array $filters = []): array
    {
        $results = [];
        $connections = AtsConnection::active()->get();

        foreach ($connections as $connection) {
            if ($connection->canSync()) {
                $results[$connection->name] = $this->syncConnection($connection, $filters);
            } else {
                $results[$connection->name] = [
                    'success' => false,
                    'error' => 'Rate limit exceeded or connection inactive'
                ];
            }
        }

        return $results;
    }

    public function syncConnection(AtsConnection $connection, array $filters = []): array
    {
        $syncLog = $this->createSyncLog($connection, 'full', $filters);

        try {
            $jobsResult = $this->syncJobs($connection, $filters, $syncLog);
            $candidatesResult = $this->syncCandidates($connection, $filters, $syncLog);
            $applicationsResult = $this->syncApplications($connection, $filters, $syncLog);

            $totalProcessed = $jobsResult['processed'] + $candidatesResult['processed'] + $applicationsResult['processed'];
            $totalCreated = $jobsResult['created'] + $candidatesResult['created'] + $applicationsResult['created'];
            $totalUpdated = $jobsResult['updated'] + $candidatesResult['updated'] + $applicationsResult['updated'];
            $totalFailed = $jobsResult['failed'] + $candidatesResult['failed'] + $applicationsResult['failed'];

            $syncLog->updateProgress($totalProcessed, $totalCreated, $totalUpdated, $totalFailed);
            $syncLog->markCompleted();

            $connection->updateSyncStats([
                'last_sync' => now()->toISOString(),
                'total_synced' => $totalProcessed,
                'success_rate' => $totalProcessed > 0 ? (($totalProcessed - $totalFailed) / $totalProcessed) * 100 : 0
            ]);

            return [
                'success' => true,
                'jobs_processed' => $jobsResult['processed'],
                'jobs_created' => $jobsResult['created'],
                'jobs_updated' => $jobsResult['updated'],
                'candidates_processed' => $candidatesResult['processed'],
                'candidates_created' => $candidatesResult['created'],
                'candidates_updated' => $candidatesResult['updated'],
                'applications_processed' => $applicationsResult['processed'],
                'applications_created' => $applicationsResult['created'],
                'applications_updated' => $applicationsResult['updated'],
                'total_failed' => $totalFailed
            ];

        } catch (\Exception $e) {
            $syncLog->markFailed([$e->getMessage()]);
            Log::error('ATS sync failed for connection ' . $connection->id, [
                'error' => $e->getMessage(),
                'connection' => $connection->name
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function syncJobs(AtsConnection $connection, array $filters, AtsSyncLog $syncLog): array
    {
        $processed = 0;
        $created = 0;
        $updated = 0;
        $failed = 0;

        try {
            $jobs = $this->fetchJobs($connection, $filters);

            foreach ($jobs as $jobData) {
                $processed++;

                try {
                    $mappedData = $this->mapJobData($connection, $jobData);

                    $existingJob = AtsJobPosting::where('ats_connection_id', $connection->id)
                        ->where('external_job_id', $mappedData['external_job_id'])
                        ->first();

                    if ($existingJob) {
                        $existingJob->update($mappedData);
                        $updated++;
                    } else {
                        AtsJobPosting::create($mappedData);
                        $created++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning('Failed to sync job', [
                        'connection' => $connection->id,
                        'job_id' => $jobData['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch jobs from ATS', [
                'connection' => $connection->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return compact('processed', 'created', 'updated', 'failed');
    }

    private function syncCandidates(AtsConnection $connection, array $filters, AtsSyncLog $syncLog): array
    {
        $processed = 0;
        $created = 0;
        $updated = 0;
        $failed = 0;

        try {
            $candidates = $this->fetchCandidates($connection, $filters);

            foreach ($candidates as $candidateData) {
                $processed++;

                try {
                    $mappedData = $this->mapCandidateData($connection, $candidateData);

                    $existingCandidate = AtsCandidate::where('ats_connection_id', $connection->id)
                        ->where('external_candidate_id', $mappedData['external_candidate_id'])
                        ->first();

                    if ($existingCandidate) {
                        $existingCandidate->update($mappedData);
                        $updated++;
                    } else {
                        AtsCandidate::create($mappedData);
                        $created++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning('Failed to sync candidate', [
                        'connection' => $connection->id,
                        'candidate_id' => $candidateData['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch candidates from ATS', [
                'connection' => $connection->id,
                'error' => $e->getMessage()
            ]);
        }

        return compact('processed', 'created', 'updated', 'failed');
    }

    private function syncApplications(AtsConnection $connection, array $filters, AtsSyncLog $syncLog): array
    {
        $processed = 0;
        $created = 0;
        $updated = 0;
        $failed = 0;

        try {
            $applications = $this->fetchApplications($connection, $filters);

            foreach ($applications as $applicationData) {
                $processed++;

                try {
                    $mappedData = $this->mapApplicationData($connection, $applicationData);

                    // Find corresponding job and candidate
                    $job = AtsJobPosting::where('ats_connection_id', $connection->id)
                        ->where('external_job_id', $mappedData['external_job_id'])
                        ->first();

                    $candidate = AtsCandidate::where('ats_connection_id', $connection->id)
                        ->where('external_candidate_id', $mappedData['external_candidate_id'])
                        ->first();

                    if ($job && $candidate) {
                        $mappedData['ats_job_posting_id'] = $job->id;
                        $mappedData['ats_candidate_id'] = $candidate->id;

                        unset($mappedData['external_job_id'], $mappedData['external_candidate_id']);

                        $existingApplication = AtsApplication::where('ats_job_posting_id', $job->id)
                            ->where('ats_candidate_id', $candidate->id)
                            ->first();

                        if ($existingApplication) {
                            $existingApplication->update($mappedData);
                            $updated++;
                        } else {
                            AtsApplication::create($mappedData);
                            $created++;
                        }
                    } else {
                        $failed++;
                        Log::warning('Failed to find job or candidate for application', [
                            'connection' => $connection->id,
                            'job_found' => $job ? true : false,
                            'candidate_found' => $candidate ? true : false
                        ]);
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning('Failed to sync application', [
                        'connection' => $connection->id,
                        'application_id' => $applicationData['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch applications from ATS', [
                'connection' => $connection->id,
                'error' => $e->getMessage()
            ]);
        }

        return compact('processed', 'created', 'updated', 'failed');
    }

    private function fetchJobs(AtsConnection $connection, array $filters = []): array
    {
        $params = $this->buildJobsParams($connection, $filters);

        $response = Http::withHeaders($connection->getApiHeaders())
            ->timeout(60)
            ->get($connection->getJobsEndpoint(), $params);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch jobs: ' . $response->status() . ' - ' . $response->body());
        }

        $data = $response->json();

        // Extract jobs array based on provider
        return $this->extractJobsArray($connection, $data);
    }

    private function fetchCandidates(AtsConnection $connection, array $filters = []): array
    {
        $params = $this->buildCandidatesParams($connection, $filters);

        $response = Http::withHeaders($connection->getApiHeaders())
            ->timeout(60)
            ->get($connection->getCandidatesEndpoint(), $params);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch candidates: ' . $response->status() . ' - ' . $response->body());
        }

        $data = $response->json();

        return $this->extractCandidatesArray($connection, $data);
    }

    private function fetchApplications(AtsConnection $connection, array $filters = []): array
    {
        $params = $this->buildApplicationsParams($connection, $filters);

        $endpoint = $connection->api_endpoint . $this->getApplicationsPath($connection);

        $response = Http::withHeaders($connection->getApiHeaders())
            ->timeout(60)
            ->get($endpoint, $params);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch applications: ' . $response->status() . ' - ' . $response->body());
        }

        $data = $response->json();

        return $this->extractApplicationsArray($connection, $data);
    }

    private function buildJobsParams(AtsConnection $connection, array $filters): array
    {
        $config = $connection->api_config;
        $params = $config['default_params'] ?? [];

        // Add filters based on provider
        if (!empty($filters['location']) && isset($connection->field_mapping['location_param'])) {
            $params[$connection->field_mapping['location_param']] = $filters['location'];
        }

        if (!empty($filters['keywords']) && isset($connection->field_mapping['keywords_param'])) {
            $params[$connection->field_mapping['keywords_param']] = $filters['keywords'];
        }

        if (!empty($filters['department'])) {
            $params['department'] = $filters['department'];
        }

        return $params;
    }

    private function buildCandidatesParams(AtsConnection $connection, array $filters): array
    {
        $config = $connection->api_config;
        return $config['default_params'] ?? [];
    }

    private function buildApplicationsParams(AtsConnection $connection, array $filters): array
    {
        $config = $connection->api_config;
        return $config['default_params'] ?? [];
    }

    private function extractJobsArray(AtsConnection $connection, array $data): array
    {
        $arrayPath = $connection->field_mapping['jobs_array_path'] ?? null;

        if ($arrayPath) {
            return data_get($data, $arrayPath, []);
        }

        // Common array keys for different providers
        $commonKeys = ['jobs', 'data', 'results', 'items', 'postings'];

        foreach ($commonKeys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        // If no array found, assume the entire response is the jobs array
        return is_array($data) && isset($data[0]) ? $data : [];
    }

    private function extractCandidatesArray(AtsConnection $connection, array $data): array
    {
        $commonKeys = ['candidates', 'data', 'results', 'items', 'people'];

        foreach ($commonKeys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        return is_array($data) && isset($data[0]) ? $data : [];
    }

    private function extractApplicationsArray(AtsConnection $connection, array $data): array
    {
        $commonKeys = ['applications', 'data', 'results', 'items'];

        foreach ($commonKeys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        return is_array($data) && isset($data[0]) ? $data : [];
    }

    private function mapJobData(AtsConnection $connection, array $jobData): array
    {
        $mapping = $connection->field_mapping;

        return [
            'ats_connection_id' => $connection->id,
            'external_job_id' => data_get($jobData, $mapping['id_field'], ''),
            'title' => data_get($jobData, $mapping['title_field'], ''),
            'description' => data_get($jobData, $mapping['description_field'], ''),
            'department' => data_get($jobData, $mapping['department_field'] ?? 'department', ''),
            'location' => data_get($jobData, $mapping['location_field'], ''),
            'employment_type' => $this->normalizeEmploymentType(data_get($jobData, $mapping['employment_type_field'] ?? 'employment_type', 'full-time')),
            'experience_level' => $this->normalizeExperienceLevel(data_get($jobData, $mapping['experience_level_field'] ?? 'experience_level', 'entry-level')),
            'salary_min' => $this->extractSalaryMin(data_get($jobData, $mapping['salary_field'] ?? 'salary')),
            'salary_max' => $this->extractSalaryMax(data_get($jobData, $mapping['salary_field'] ?? 'salary')),
            'salary_currency' => 'USD',
            'requirements' => $this->extractRequirements($jobData),
            'benefits' => $this->extractBenefits($jobData),
            'hiring_manager' => data_get($jobData, $mapping['hiring_manager_field'] ?? 'hiring_manager', ''),
            'recruiter' => data_get($jobData, $mapping['recruiter_field'] ?? 'recruiter', ''),
            'status' => $this->normalizeJobStatus(data_get($jobData, $mapping['status_field'] ?? 'status', 'active')),
            'posted_at' => $this->parseDate(data_get($jobData, $mapping['posted_date_field'] ?? 'posted_date')),
            'expires_at' => $this->parseDate(data_get($jobData, $mapping['expires_date_field'] ?? 'expires_date')),
            'custom_fields' => $this->extractCustomFields($jobData, $mapping),
            'last_updated_at' => now()
        ];
    }

    private function mapCandidateData(AtsConnection $connection, array $candidateData): array
    {
        $mapping = $connection->field_mapping;

        return [
            'ats_connection_id' => $connection->id,
            'external_candidate_id' => data_get($candidateData, $mapping['candidate_id_field'] ?? 'id', ''),
            'first_name' => data_get($candidateData, $mapping['first_name_field'] ?? 'first_name', ''),
            'last_name' => data_get($candidateData, $mapping['last_name_field'] ?? 'last_name', ''),
            'email' => data_get($candidateData, $mapping['email_field'] ?? 'email', ''),
            'phone' => data_get($candidateData, $mapping['phone_field'] ?? 'phone', ''),
            'address' => data_get($candidateData, $mapping['address_field'] ?? 'address', ''),
            'linkedin_url' => data_get($candidateData, $mapping['linkedin_field'] ?? 'linkedin_url', ''),
            'portfolio_url' => data_get($candidateData, $mapping['portfolio_field'] ?? 'portfolio_url', ''),
            'skills' => $this->extractSkills($candidateData),
            'education' => $this->extractEducation($candidateData),
            'experience' => $this->extractExperience($candidateData),
            'current_title' => data_get($candidateData, $mapping['current_title_field'] ?? 'current_title', ''),
            'current_company' => data_get($candidateData, $mapping['current_company_field'] ?? 'current_company', ''),
            'desired_salary' => $this->extractDesiredSalary(data_get($candidateData, $mapping['desired_salary_field'] ?? 'desired_salary')),
            'availability' => $this->normalizeAvailability(data_get($candidateData, $mapping['availability_field'] ?? 'availability', 'immediate')),
            'open_to_remote' => (bool) data_get($candidateData, $mapping['remote_field'] ?? 'open_to_remote', false),
            'custom_fields' => $this->extractCustomFields($candidateData, $mapping),
            'last_updated_at' => now()
        ];
    }

    private function mapApplicationData(AtsConnection $connection, array $applicationData): array
    {
        $mapping = $connection->field_mapping;

        return [
            'external_application_id' => data_get($applicationData, $mapping['application_id_field'] ?? 'id', ''),
            'external_job_id' => data_get($applicationData, $mapping['job_id_field'] ?? 'job_id', ''),
            'external_candidate_id' => data_get($applicationData, $mapping['candidate_id_field'] ?? 'candidate_id', ''),
            'status' => $this->normalizeApplicationStatus(data_get($applicationData, $mapping['status_field'] ?? 'status', 'new')),
            'cover_letter' => data_get($applicationData, $mapping['cover_letter_field'] ?? 'cover_letter', ''),
            'attachments' => $this->extractAttachments($applicationData),
            'questionnaire_responses' => $this->extractQuestionnaireResponses($applicationData),
            'offered_salary' => $this->extractOfferedSalary(data_get($applicationData, $mapping['offered_salary_field'] ?? 'offered_salary')),
            'applied_at' => $this->parseDate(data_get($applicationData, $mapping['applied_date_field'] ?? 'applied_at')),
            'status_updated_at' => $this->parseDate(data_get($applicationData, $mapping['status_updated_field'] ?? 'status_updated_at')),
            'rejection_reason' => data_get($applicationData, $mapping['rejection_reason_field'] ?? 'rejection_reason', ''),
            'interview_notes' => $this->extractInterviewNotes($applicationData),
            'assessment_scores' => $this->extractAssessmentScores($applicationData),
            'custom_fields' => $this->extractCustomFields($applicationData, $mapping)
        ];
    }

    private function createSyncLog(AtsConnection $connection, string $syncType, array $filters): AtsSyncLog
    {
        return AtsSyncLog::create([
            'ats_connection_id' => $connection->id,
            'sync_type' => $syncType,
            'status' => 'started',
            'filters' => $filters,
            'started_at' => now()
        ]);
    }

    // Helper methods for data transformation
    private function normalizeEmploymentType(string $type): string
    {
        $normalized = strtolower(trim($type));

        return match($normalized) {
            'full time', 'fulltime', 'permanent' => 'full-time',
            'part time', 'parttime' => 'part-time',
            'contractor', 'freelance' => 'contract',
            'temp', 'temporary' => 'temporary',
            'intern', 'internship' => 'internship',
            default => 'full-time'
        };
    }

    private function normalizeExperienceLevel(string $level): string
    {
        $normalized = strtolower(trim($level));

        if (str_contains($normalized, 'entry') || str_contains($normalized, 'junior') || str_contains($normalized, 'associate')) {
            return 'entry-level';
        } elseif (str_contains($normalized, 'senior') || str_contains($normalized, 'lead')) {
            return 'senior';
        } elseif (str_contains($normalized, 'executive') || str_contains($normalized, 'director') || str_contains($normalized, 'manager')) {
            return 'executive';
        } else {
            return 'mid-level';
        }
    }

    private function normalizeJobStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match($normalized) {
            'open', 'published', 'live' => 'active',
            'paused', 'hold' => 'paused',
            'closed', 'filled', 'expired' => 'closed',
            'draft', 'pending' => 'draft',
            default => 'active'
        };
    }

    private function normalizeApplicationStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match($normalized) {
            'submitted', 'applied' => 'new',
            'reviewing', 'screening', 'phone_screen' => 'screening',
            'interviewing', 'interview_scheduled', 'onsite' => 'interview',
            'testing', 'assessment', 'technical' => 'assessment',
            'offer_extended', 'offer_sent' => 'offer',
            'hired', 'accepted' => 'hired',
            'declined', 'rejected', 'not_selected' => 'rejected',
            'withdrawn', 'cancelled' => 'withdrawn',
            default => 'new'
        };
    }

    private function normalizeAvailability(string $availability): string
    {
        $normalized = strtolower(trim($availability));

        return match($normalized) {
            'immediately', 'immediate', 'asap', 'now' => 'immediate',
            '2 weeks', '2weeks', 'two weeks' => '2-weeks',
            '1 month', '1month', 'one month', '30 days' => '1-month',
            'flexible', 'negotiable', 'open' => 'flexible',
            default => 'immediate'
        };
    }

    private function parseDate($dateValue): ?string
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            return Carbon::parse($dateValue)->toDateTimeString();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function extractSalaryMin($salaryData): ?float
    {
        if (empty($salaryData)) {
            return null;
        }

        if (is_array($salaryData)) {
            return (float) ($salaryData['min'] ?? $salaryData['minimum'] ?? null);
        }

        // Extract number from string like "$50,000 - $70,000"
        if (preg_match('/\$?([\d,]+)\s*-\s*\$?([\d,]+)/', $salaryData, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        // Single number
        if (preg_match('/\$?([\d,]+)/', $salaryData, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        return null;
    }

    private function extractSalaryMax($salaryData): ?float
    {
        if (empty($salaryData)) {
            return null;
        }

        if (is_array($salaryData)) {
            return (float) ($salaryData['max'] ?? $salaryData['maximum'] ?? null);
        }

        // Extract number from string like "$50,000 - $70,000"
        if (preg_match('/\$?([\d,]+)\s*-\s*\$?([\d,]+)/', $salaryData, $matches)) {
            return (float) str_replace(',', '', $matches[2]);
        }

        return null;
    }

    private function extractDesiredSalary($salaryData): ?float
    {
        if (empty($salaryData)) {
            return null;
        }

        if (is_numeric($salaryData)) {
            return (float) $salaryData;
        }

        // Extract first number found
        if (preg_match('/\$?([\d,]+)/', $salaryData, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        return null;
    }

    private function extractOfferedSalary($salaryData): ?float
    {
        return $this->extractDesiredSalary($salaryData);
    }

    private function extractRequirements(array $data): array
    {
        $requirements = [];

        // Look for common requirement fields
        $fields = ['requirements', 'qualifications', 'skills_required', 'must_have'];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                if (is_array($data[$field])) {
                    $requirements = array_merge($requirements, $data[$field]);
                } else {
                    $requirements[] = $data[$field];
                }
            }
        }

        return array_filter($requirements);
    }

    private function extractBenefits(array $data): array
    {
        $benefits = [];

        $fields = ['benefits', 'perks', 'compensation_benefits'];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                if (is_array($data[$field])) {
                    $benefits = array_merge($benefits, $data[$field]);
                } else {
                    $benefits[] = $data[$field];
                }
            }
        }

        return array_filter($benefits);
    }

    private function extractSkills(array $data): array
    {
        $skills = [];

        $fields = ['skills', 'technologies', 'expertise', 'competencies'];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                if (is_array($data[$field])) {
                    $skills = array_merge($skills, $data[$field]);
                } else {
                    $skills[] = $data[$field];
                }
            }
        }

        return array_filter($skills);
    }

    private function extractEducation(array $data): array
    {
        if (isset($data['education']) && is_array($data['education'])) {
            return $data['education'];
        }

        return [];
    }

    private function extractExperience(array $data): array
    {
        if (isset($data['experience']) && is_array($data['experience'])) {
            return $data['experience'];
        }

        if (isset($data['work_history']) && is_array($data['work_history'])) {
            return $data['work_history'];
        }

        return [];
    }

    private function extractAttachments(array $data): array
    {
        if (isset($data['attachments']) && is_array($data['attachments'])) {
            return $data['attachments'];
        }

        if (isset($data['files']) && is_array($data['files'])) {
            return $data['files'];
        }

        return [];
    }

    private function extractQuestionnaireResponses(array $data): array
    {
        if (isset($data['questionnaire_responses']) && is_array($data['questionnaire_responses'])) {
            return $data['questionnaire_responses'];
        }

        if (isset($data['custom_questions']) && is_array($data['custom_questions'])) {
            return $data['custom_questions'];
        }

        return [];
    }

    private function extractInterviewNotes(array $data): array
    {
        if (isset($data['interview_notes']) && is_array($data['interview_notes'])) {
            return $data['interview_notes'];
        }

        return [];
    }

    private function extractAssessmentScores(array $data): array
    {
        if (isset($data['assessment_scores']) && is_array($data['assessment_scores'])) {
            return $data['assessment_scores'];
        }

        return [];
    }

    private function extractCustomFields(array $data, array $mapping): array
    {
        $customFields = [];
        $standardFields = array_values($mapping);

        foreach ($data as $key => $value) {
            if (!in_array($key, $standardFields)) {
                $customFields[$key] = $value;
            }
        }

        return $customFields;
    }

    private function getApplicationsPath(AtsConnection $connection): string
    {
        return match($connection->provider) {
            'workday' => '/applications',
            'greenhouse' => '/v1/applications',
            'lever' => '/v1/opportunities',
            'bamboohr' => '/v1/applications',
            'successfactors' => '/odata/v2/JobApplication',
            'taleo' => '/object/application/search',
            'icims' => '/customers/' . $connection->credentials['customer_id'] . '/applications',
            'jazz' => '/recruiting/applications',
            'bullhorn' => '/search/JobSubmission',
            'jobvite' => '/v2/applications',
            default => '/applications'
        };
    }

    public function processWebhook(AtsConnection $connection, array $payload): array
    {
        try {
            $webhook = AtsWebhook::create([
                'ats_connection_id' => $connection->id,
                'webhook_id' => $payload['id'] ?? uniqid(),
                'event_type' => $payload['event_type'] ?? 'unknown',
                'payload' => $payload,
                'status' => 'pending',
                'received_at' => now()
            ]);

            // Process webhook based on event type
            $this->handleWebhookEvent($connection, $webhook);

            return ['success' => true, 'webhook_id' => $webhook->id];
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'connection' => $connection->id,
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function handleWebhookEvent(AtsConnection $connection, AtsWebhook $webhook): void
    {
        try {
            $payload = $webhook->payload;

            switch ($webhook->event_type) {
                case 'job_created':
                case 'job_updated':
                    $this->handleJobWebhook($connection, $payload);
                    break;

                case 'application_submitted':
                case 'application_updated':
                    $this->handleApplicationWebhook($connection, $payload);
                    break;

                case 'candidate_created':
                case 'candidate_updated':
                    $this->handleCandidateWebhook($connection, $payload);
                    break;

                default:
                    Log::info('Unhandled webhook event type', [
                        'connection' => $connection->id,
                        'event_type' => $webhook->event_type
                    ]);
            }

            $webhook->markProcessed();
        } catch (\Exception $e) {
            $webhook->markFailed($e->getMessage());
            throw $e;
        }
    }

    private function handleJobWebhook(AtsConnection $connection, array $payload): void
    {
        $mappedData = $this->mapJobData($connection, $payload);

        $existingJob = AtsJobPosting::where('ats_connection_id', $connection->id)
            ->where('external_job_id', $mappedData['external_job_id'])
            ->first();

        if ($existingJob) {
            $existingJob->update($mappedData);
        } else {
            AtsJobPosting::create($mappedData);
        }
    }

    private function handleApplicationWebhook(AtsConnection $connection, array $payload): void
    {
        $mappedData = $this->mapApplicationData($connection, $payload);

        $job = AtsJobPosting::where('ats_connection_id', $connection->id)
            ->where('external_job_id', $mappedData['external_job_id'])
            ->first();

        $candidate = AtsCandidate::where('ats_connection_id', $connection->id)
            ->where('external_candidate_id', $mappedData['external_candidate_id'])
            ->first();

        if ($job && $candidate) {
            $mappedData['ats_job_posting_id'] = $job->id;
            $mappedData['ats_candidate_id'] = $candidate->id;

            unset($mappedData['external_job_id'], $mappedData['external_candidate_id']);

            $existingApplication = AtsApplication::where('ats_job_posting_id', $job->id)
                ->where('ats_candidate_id', $candidate->id)
                ->first();

            if ($existingApplication) {
                $existingApplication->update($mappedData);
            } else {
                AtsApplication::create($mappedData);
            }
        }
    }

    private function handleCandidateWebhook(AtsConnection $connection, array $payload): void
    {
        $mappedData = $this->mapCandidateData($connection, $payload);

        $existingCandidate = AtsCandidate::where('ats_connection_id', $connection->id)
            ->where('external_candidate_id', $mappedData['external_candidate_id'])
            ->first();

        if ($existingCandidate) {
            $existingCandidate->update($mappedData);
        } else {
            AtsCandidate::create($mappedData);
        }
    }
}