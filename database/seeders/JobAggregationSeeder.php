<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobAggregationSource;
use App\Models\AggregatedJob;
use App\Models\AggregatedCompany;
use Carbon\Carbon;

class JobAggregationSeeder extends Seeder
{
    public function run(): void
    {
        // Create aggregation sources
        $indeed = JobAggregationSource::create([
            'name' => 'Indeed',
            'slug' => 'indeed',
            'api_url' => 'https://api.indeed.com/ads/apisearch',
            'api_type' => 'indeed',
            'api_config' => [
                'version' => '2',
                'format' => 'json',
                'default_params' => [
                    'v' => '2',
                    'format' => 'json',
                    'limit' => '25',
                    'sort' => 'date',
                ]
            ],
            'field_mapping' => [
                'id_field' => 'jobkey',
                'title_field' => 'jobtitle',
                'description_field' => 'snippet',
                'company_field' => 'company',
                'location_field' => 'formattedLocation',
                'url_field' => 'url',
                'posted_date_field' => 'date',
                'salary_field' => 'salary',
                'location_param' => 'l',
                'keywords_param' => 'q',
                'category_param' => 'q'
            ],
            'rate_limit_per_hour' => 1000,
            'is_active' => true,
            'priority' => 10,
            'supported_countries' => ['US', 'GB', 'CA', 'AU', 'IN'],
            'supported_categories' => ['technology', 'healthcare', 'finance', 'education', 'marketing'],
            'last_sync_at' => now()->subHours(2),
            'jobs_synced_today' => 245
        ]);

        $linkedin = JobAggregationSource::create([
            'name' => 'LinkedIn Jobs',
            'slug' => 'linkedin',
            'api_url' => 'https://api.linkedin.com/v2/jobPostings',
            'api_type' => 'linkedin',
            'api_config' => [
                'version' => 'v2',
                'default_params' => [
                    'count' => '50',
                    'sort' => 'DD',
                ]
            ],
            'field_mapping' => [
                'id_field' => 'id',
                'title_field' => 'title',
                'description_field' => 'description.text',
                'company_field' => 'hiringOrganization.name',
                'location_field' => 'jobLocation.address.addressLocality',
                'url_field' => 'companyApplyUrl',
                'posted_date_field' => 'datePosted',
                'employment_type_field' => 'employmentType',
                'location_param' => 'locationId',
                'keywords_param' => 'keywords',
                'category_param' => 'keywords'
            ],
            'rate_limit_per_hour' => 500,
            'is_active' => true,
            'priority' => 9,
            'supported_countries' => ['US', 'GB', 'CA', 'AU', 'DE', 'FR'],
            'supported_categories' => ['technology', 'finance', 'consulting', 'sales', 'operations'],
            'last_sync_at' => now()->subHours(1),
            'jobs_synced_today' => 156
        ]);

        $glassdoor = JobAggregationSource::create([
            'name' => 'Glassdoor',
            'slug' => 'glassdoor',
            'api_url' => 'https://api.glassdoor.com/api/api.htm',
            'api_type' => 'glassdoor',
            'api_config' => [
                'version' => '1',
                'action' => 'jobs-search',
                'default_params' => [
                    'ps' => '50',
                    'format' => 'json',
                ]
            ],
            'field_mapping' => [
                'id_field' => 'jobId',
                'title_field' => 'jobTitle',
                'description_field' => 'jobDescription',
                'company_field' => 'employer.name',
                'location_field' => 'location',
                'url_field' => 'jobUrl',
                'posted_date_field' => 'ageInDays',
                'salary_field' => 'salaryEstimate',
                'location_param' => 'l',
                'keywords_param' => 'q',
                'jobs_array_path' => 'response.jobs'
            ],
            'rate_limit_per_hour' => 200,
            'is_active' => true,
            'priority' => 8,
            'supported_countries' => ['US', 'GB', 'CA'],
            'supported_categories' => ['technology', 'finance', 'healthcare', 'consulting'],
            'last_sync_at' => now()->subHours(3),
            'jobs_synced_today' => 89
        ]);

        $ziprecruiter = JobAggregationSource::create([
            'name' => 'ZipRecruiter',
            'slug' => 'ziprecruiter',
            'api_url' => 'https://api.ziprecruiter.com/jobs/v1',
            'api_type' => 'ziprecruiter',
            'api_config' => [
                'version' => 'v1',
                'default_params' => [
                    'jobs_per_page' => '100',
                    'page' => '1'
                ]
            ],
            'field_mapping' => [
                'id_field' => 'id',
                'title_field' => 'name',
                'description_field' => 'snippet',
                'company_field' => 'hiring_company.name',
                'location_field' => 'location',
                'url_field' => 'url',
                'posted_date_field' => 'posted_time',
                'category_field' => 'category',
                'location_param' => 'location',
                'keywords_param' => 'search',
                'jobs_array_path' => 'jobs'
            ],
            'rate_limit_per_hour' => 600,
            'is_active' => true,
            'priority' => 7,
            'supported_countries' => ['US'],
            'supported_categories' => ['technology', 'healthcare', 'sales', 'customer-service'],
            'last_sync_at' => now()->subHours(4),
            'jobs_synced_today' => 312
        ]);

        // Create sample companies
        $techCorp = AggregatedCompany::create([
            'name' => 'TechCorp Solutions',
            'slug' => 'techcorp-solutions',
            'description' => 'Leading technology solutions provider specializing in cloud computing and AI.',
            'logo_url' => 'https://example.com/logos/techcorp.png',
            'website_url' => 'https://techcorp.example.com',
            'industry' => 'Technology',
            'size_range' => '1000-5000',
            'headquarters' => 'San Francisco, CA',
            'rating' => 4.2,
            'review_count' => 156,
            'active_jobs_count' => 45,
            'social_links' => [
                'linkedin' => 'https://linkedin.com/company/techcorp',
                'twitter' => 'https://twitter.com/techcorp'
            ],
            'benefits' => ['Health Insurance', 'Remote Work', '401k Matching', 'Flexible Hours']
        ]);

        $healthPlus = AggregatedCompany::create([
            'name' => 'HealthPlus Medical',
            'slug' => 'healthplus-medical',
            'description' => 'Comprehensive healthcare services with focus on patient care and innovation.',
            'logo_url' => 'https://example.com/logos/healthplus.png',
            'website_url' => 'https://healthplus.example.com',
            'industry' => 'Healthcare',
            'size_range' => '5000-10000',
            'headquarters' => 'Boston, MA',
            'rating' => 4.5,
            'review_count' => 89,
            'active_jobs_count' => 23,
            'social_links' => [
                'linkedin' => 'https://linkedin.com/company/healthplus'
            ],
            'benefits' => ['Health Insurance', 'Dental & Vision', 'Paid Time Off', 'Professional Development']
        ]);

        // Create sample aggregated jobs
        $this->createSampleJobs($indeed, $techCorp, $healthPlus);
        $this->createSampleJobs($linkedin, $techCorp, $healthPlus);
        $this->createSampleJobs($glassdoor, $techCorp, $healthPlus);
        $this->createSampleJobs($ziprecruiter, $techCorp, $healthPlus);
    }

    private function createSampleJobs($source, $techCorp, $healthPlus)
    {
        $jobs = [
            [
                'external_id' => 'ext_' . $source->slug . '_001',
                'title' => 'Senior Software Engineer',
                'description' => 'We are seeking a Senior Software Engineer to join our dynamic development team. You will be responsible for designing and implementing scalable software solutions using modern technologies including cloud platforms, microservices, and agile methodologies.',
                'company_name' => 'TechCorp Solutions',
                'company_logo_url' => 'https://example.com/logos/techcorp.png',
                'location' => 'San Francisco, CA',
                'country_code' => 'US',
                'city' => 'San Francisco',
                'salary_range' => '$120,000 - $180,000',
                'salary_min' => 120000,
                'salary_max' => 180000,
                'salary_currency' => 'USD',
                'employment_type' => 'Full-time',
                'experience_level' => 'Senior',
                'category' => 'Technology',
                'skills' => ['JavaScript', 'React', 'Node.js', 'AWS', 'Docker', 'Kubernetes'],
                'external_url' => 'https://' . $source->slug . '.com/job/senior-software-engineer-001',
                'application_url' => 'https://' . $source->slug . '.com/apply/001',
                'posted_at' => now()->subDays(2),
                'expires_at' => now()->addDays(28),
                'is_active' => true,
                'is_featured' => true,
                'views_count' => 234,
                'applications_count' => 12,
            ],
            [
                'external_id' => 'ext_' . $source->slug . '_002',
                'title' => 'Data Scientist',
                'description' => 'Join our data science team to work on cutting-edge machine learning projects. You will analyze large datasets, build predictive models, and help drive data-driven decision making across the organization.',
                'company_name' => 'TechCorp Solutions',
                'company_logo_url' => 'https://example.com/logos/techcorp.png',
                'location' => 'Remote',
                'country_code' => 'US',
                'city' => null,
                'salary_range' => '$100,000 - $150,000',
                'salary_min' => 100000,
                'salary_max' => 150000,
                'salary_currency' => 'USD',
                'employment_type' => 'Full-time',
                'experience_level' => 'Mid-level',
                'category' => 'Data Science',
                'skills' => ['Python', 'R', 'SQL', 'Machine Learning', 'TensorFlow', 'Pandas'],
                'external_url' => 'https://' . $source->slug . '.com/job/data-scientist-002',
                'application_url' => 'https://' . $source->slug . '.com/apply/002',
                'posted_at' => now()->subDays(1),
                'expires_at' => now()->addDays(29),
                'is_active' => true,
                'is_featured' => false,
                'views_count' => 156,
                'applications_count' => 8,
            ],
            [
                'external_id' => 'ext_' . $source->slug . '_003',
                'title' => 'Registered Nurse - ICU',
                'description' => 'We are looking for a dedicated Registered Nurse to join our Intensive Care Unit. The ideal candidate will provide exceptional patient care in a fast-paced critical care environment.',
                'company_name' => 'HealthPlus Medical',
                'company_logo_url' => 'https://example.com/logos/healthplus.png',
                'location' => 'Boston, MA',
                'country_code' => 'US',
                'city' => 'Boston',
                'salary_range' => '$75,000 - $95,000',
                'salary_min' => 75000,
                'salary_max' => 95000,
                'salary_currency' => 'USD',
                'employment_type' => 'Full-time',
                'experience_level' => 'Entry-level',
                'category' => 'Healthcare',
                'skills' => ['Patient Care', 'Critical Care', 'IV Therapy', 'Electronic Health Records'],
                'external_url' => 'https://' . $source->slug . '.com/job/rn-icu-003',
                'application_url' => 'https://' . $source->slug . '.com/apply/003',
                'posted_at' => now()->subHours(12),
                'expires_at' => now()->addDays(30),
                'is_active' => true,
                'is_featured' => false,
                'views_count' => 89,
                'applications_count' => 5,
            ],
            [
                'external_id' => 'ext_' . $source->slug . '_004',
                'title' => 'DevOps Engineer',
                'description' => 'We need a skilled DevOps Engineer to help us build and maintain our cloud infrastructure. You will work with containerization, CI/CD pipelines, and infrastructure as code.',
                'company_name' => 'TechCorp Solutions',
                'company_logo_url' => 'https://example.com/logos/techcorp.png',
                'location' => 'New York, NY',
                'country_code' => 'US',
                'city' => 'New York',
                'salary_range' => '$110,000 - $160,000',
                'salary_min' => 110000,
                'salary_max' => 160000,
                'salary_currency' => 'USD',
                'employment_type' => 'Full-time',
                'experience_level' => 'Mid-level',
                'category' => 'Technology',
                'skills' => ['AWS', 'Docker', 'Kubernetes', 'Terraform', 'Jenkins', 'Git'],
                'external_url' => 'https://' . $source->slug . '.com/job/devops-engineer-004',
                'application_url' => 'https://' . $source->slug . '.com/apply/004',
                'posted_at' => now()->subHours(6),
                'expires_at' => now()->addDays(25),
                'is_active' => true,
                'is_featured' => true,
                'views_count' => 312,
                'applications_count' => 18,
            ]
        ];

        foreach ($jobs as $jobData) {
            $jobData['aggregation_source_id'] = $source->id;
            $job = AggregatedJob::create($jobData);

            // Associate with companies
            if ($jobData['company_name'] === 'TechCorp Solutions') {
                $job->companies()->attach($techCorp->id);
            } elseif ($jobData['company_name'] === 'HealthPlus Medical') {
                $job->companies()->attach($healthPlus->id);
            }
        }

        // Update company job counts
        $techCorp->updateJobsCount();
        $healthPlus->updateJobsCount();
    }
}