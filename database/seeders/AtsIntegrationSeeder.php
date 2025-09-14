<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AtsConnection;
use App\Models\AtsJobPosting;
use App\Models\AtsCandidate;
use App\Models\AtsApplication;
use App\Models\AtsFieldMapping;
use App\Models\User;

class AtsIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        // Find a test user or create one
        $testUser = User::where('email', 'admin@demo.com')->first();
        if (!$testUser) {
            $testUser = User::first();
        }

        if (!$testUser) {
            $this->command->info('No users found. Skipping ATS Integration seeding.');
            return;
        }

        // Create ATS Connections
        $greenhouse = AtsConnection::create([
            'user_id' => $testUser->id,
            'name' => 'TechCorp Greenhouse',
            'provider' => 'greenhouse',
            'api_endpoint' => 'https://harvest-api.greenhouse.io',
            'credentials' => [
                'api_key' => 'demo_greenhouse_api_key_' . str()->random(32),
            ],
            'configuration' => [
                'version' => 'v1',
                'default_params' => [
                    'per_page' => '50',
                    'page' => '1'
                ]
            ],
            'field_mapping' => [
                'id_field' => 'id',
                'title_field' => 'name',
                'description_field' => 'content',
                'location_field' => 'location.name',
                'department_field' => 'departments[0].name',
                'status_field' => 'status',
                'posted_date_field' => 'created_at',
                'hiring_manager_field' => 'hiring_manager.name',
                'jobs_array_path' => 'jobs'
            ],
            'is_active' => true,
            'last_sync_at' => now()->subHours(2),
            'sync_stats' => [
                'jobs_synced' => 45,
                'candidates_synced' => 23,
                'applications_synced' => 78,
                'last_sync_duration' => 45.2,
                'success_rate' => 96.5
            ]
        ]);

        $workday = AtsConnection::create([
            'user_id' => $testUser->id,
            'name' => 'HR Solutions Workday',
            'provider' => 'workday',
            'api_endpoint' => 'https://services1.myworkday.com/ccx/service',
            'credentials' => [
                'username' => 'demo_user@company.com',
                'password' => 'demo_password_' . str()->random(16),
                'tenant' => 'demo_tenant'
            ],
            'configuration' => [
                'version' => '1',
                'default_params' => [
                    'count' => '25'
                ]
            ],
            'field_mapping' => [
                'id_field' => 'Job_Posting_ID',
                'title_field' => 'Job_Title',
                'description_field' => 'Job_Description',
                'location_field' => 'Primary_Location',
                'department_field' => 'Company.Organization_Name',
                'status_field' => 'Job_Posting_Status',
                'posted_date_field' => 'Posted_Date'
            ],
            'is_active' => true,
            'last_sync_at' => now()->subHours(4),
            'sync_stats' => [
                'jobs_synced' => 32,
                'candidates_synced' => 18,
                'applications_synced' => 54,
                'last_sync_duration' => 67.8,
                'success_rate' => 94.2
            ]
        ]);

        $lever = AtsConnection::create([
            'user_id' => $testUser->id,
            'name' => 'StartupCorp Lever',
            'provider' => 'lever',
            'api_endpoint' => 'https://api.lever.co/v1',
            'credentials' => [
                'api_key' => 'demo_lever_key_' . str()->random(40),
            ],
            'configuration' => [
                'version' => 'v1',
                'default_params' => [
                    'limit' => '100'
                ]
            ],
            'field_mapping' => [
                'id_field' => 'id',
                'title_field' => 'text',
                'description_field' => 'content.description',
                'location_field' => 'location',
                'department_field' => 'team',
                'status_field' => 'state',
                'posted_date_field' => 'createdAt'
            ],
            'is_active' => false, // Inactive for demo
            'last_sync_at' => now()->subDays(3),
            'sync_stats' => [
                'jobs_synced' => 12,
                'candidates_synced' => 8,
                'applications_synced' => 21,
                'last_sync_duration' => 23.4,
                'success_rate' => 88.9
            ]
        ]);

        // Create field mappings for Greenhouse
        $this->createFieldMappings($greenhouse);
        $this->createFieldMappings($workday);

        // Create sample job postings
        $this->createSampleJobPostings($greenhouse);
        $this->createSampleJobPostings($workday);

        // Create sample candidates
        $this->createSampleCandidates($greenhouse);
        $this->createSampleCandidates($workday);

        // Create sample applications
        $this->createSampleApplications($greenhouse);
        $this->createSampleApplications($workday);
    }

    private function createFieldMappings(AtsConnection $connection): void
    {
        $mappings = [];

        if ($connection->provider === 'greenhouse') {
            $mappings = [
                // Job mappings
                ['entity_type' => 'job', 'local_field' => 'title', 'ats_field' => 'name', 'field_type' => 'string', 'is_required' => true],
                ['entity_type' => 'job', 'local_field' => 'description', 'ats_field' => 'content', 'field_type' => 'string', 'is_required' => true],
                ['entity_type' => 'job', 'local_field' => 'location', 'ats_field' => 'location.name', 'field_type' => 'string', 'is_required' => false],
                ['entity_type' => 'job', 'local_field' => 'department', 'ats_field' => 'departments[0].name', 'field_type' => 'string', 'is_required' => false],

                // Candidate mappings
                ['entity_type' => 'candidate', 'local_field' => 'first_name', 'ats_field' => 'first_name', 'field_type' => 'string', 'is_required' => true],
                ['entity_type' => 'candidate', 'local_field' => 'last_name', 'ats_field' => 'last_name', 'field_type' => 'string', 'is_required' => true],
                ['entity_type' => 'candidate', 'local_field' => 'email', 'ats_field' => 'email_addresses[0].value', 'field_type' => 'string', 'is_required' => true],

                // Application mappings
                ['entity_type' => 'application', 'local_field' => 'status', 'ats_field' => 'status', 'field_type' => 'string', 'is_required' => true],
                ['entity_type' => 'application', 'local_field' => 'applied_at', 'ats_field' => 'applied_at', 'field_type' => 'date', 'is_required' => true],
            ];
        } elseif ($connection->provider === 'workday') {
            $mappings = [
                // Job mappings
                ['entity_type' => 'job', 'local_field' => 'title', 'ats_field' => 'Job_Title', 'field_type' => 'string', 'is_required' => true],
                ['entity_type' => 'job', 'local_field' => 'description', 'ats_field' => 'Job_Description', 'field_type' => 'string', 'is_required' => true],
                ['entity_type' => 'job', 'local_field' => 'location', 'ats_field' => 'Primary_Location', 'field_type' => 'string', 'is_required' => false],

                // Candidate mappings
                ['entity_type' => 'candidate', 'local_field' => 'first_name', 'ats_field' => 'Personal_Data.Name_Data.First_Name', 'field_type' => 'string', 'is_required' => true],
                ['entity_type' => 'candidate', 'local_field' => 'last_name', 'ats_field' => 'Personal_Data.Name_Data.Last_Name', 'field_type' => 'string', 'is_required' => true],
                ['entity_type' => 'candidate', 'local_field' => 'email', 'ats_field' => 'Personal_Data.Contact_Data.Email_Address_Data[0].Email_Address', 'field_type' => 'string', 'is_required' => true],
            ];
        }

        foreach ($mappings as $mapping) {
            AtsFieldMapping::create(array_merge([
                'ats_connection_id' => $connection->id,
                'transformation_rules' => []
            ], $mapping));
        }
    }

    private function createSampleJobPostings(AtsConnection $connection): void
    {
        $jobs = [
            [
                'external_job_id' => 'ext_' . $connection->provider . '_job_001',
                'title' => 'Senior Full Stack Engineer',
                'description' => 'We are looking for a Senior Full Stack Engineer to join our growing engineering team. You will be responsible for building scalable web applications using modern technologies including React, Node.js, and cloud platforms.',
                'department' => 'Engineering',
                'location' => 'San Francisco, CA',
                'employment_type' => 'full-time',
                'experience_level' => 'senior',
                'salary_min' => 150000,
                'salary_max' => 200000,
                'salary_currency' => 'USD',
                'requirements' => ['JavaScript', 'React', 'Node.js', 'PostgreSQL', 'AWS'],
                'benefits' => ['Health Insurance', '401k Match', 'Remote Flexible', 'Stock Options'],
                'hiring_manager' => 'Sarah Johnson',
                'recruiter' => 'Mike Chen',
                'status' => 'active',
                'posted_at' => now()->subDays(3),
                'expires_at' => now()->addDays(27),
                'applications_count' => 15
            ],
            [
                'external_job_id' => 'ext_' . $connection->provider . '_job_002',
                'title' => 'Product Manager',
                'description' => 'Join our product team to drive the strategy and execution of our core platform features. You will work closely with engineering, design, and business stakeholders to deliver exceptional user experiences.',
                'department' => 'Product',
                'location' => 'New York, NY',
                'employment_type' => 'full-time',
                'experience_level' => 'mid-level',
                'salary_min' => 120000,
                'salary_max' => 160000,
                'salary_currency' => 'USD',
                'requirements' => ['Product Management', 'SQL', 'Analytics', 'Agile', 'Roadmap Planning'],
                'benefits' => ['Health Insurance', 'Dental', 'Vision', 'Unlimited PTO'],
                'hiring_manager' => 'David Park',
                'recruiter' => 'Lisa Wong',
                'status' => 'active',
                'posted_at' => now()->subDays(1),
                'expires_at' => now()->addDays(29),
                'applications_count' => 8
            ],
            [
                'external_job_id' => 'ext_' . $connection->provider . '_job_003',
                'title' => 'Data Scientist',
                'description' => 'We are seeking a Data Scientist to join our analytics team. You will work on machine learning models, statistical analysis, and data visualization to drive business insights and product improvements.',
                'department' => 'Data & Analytics',
                'location' => 'Remote',
                'employment_type' => 'full-time',
                'experience_level' => 'mid-level',
                'salary_min' => 130000,
                'salary_max' => 170000,
                'salary_currency' => 'USD',
                'requirements' => ['Python', 'R', 'SQL', 'Machine Learning', 'Statistics', 'Tableau'],
                'benefits' => ['Remote Work', 'Learning Budget', 'Conference Attendance', 'Health Insurance'],
                'hiring_manager' => 'Jennifer Liu',
                'recruiter' => 'Alex Rodriguez',
                'status' => 'paused',
                'posted_at' => now()->subDays(7),
                'expires_at' => now()->addDays(23),
                'applications_count' => 22
            ]
        ];

        foreach ($jobs as $jobData) {
            AtsJobPosting::create(array_merge([
                'ats_connection_id' => $connection->id,
                'custom_fields' => [
                    'priority' => 'high',
                    'team_size' => rand(5, 20),
                    'remote_ok' => rand(0, 1) ? true : false
                ]
            ], $jobData));
        }
    }

    private function createSampleCandidates(AtsConnection $connection): void
    {
        $candidates = [
            [
                'external_candidate_id' => 'ext_' . $connection->provider . '_cand_001',
                'first_name' => 'Emily',
                'last_name' => 'Johnson',
                'email' => 'emily.johnson@email.com',
                'phone' => '+1-555-0101',
                'address' => 'Seattle, WA, USA',
                'linkedin_url' => 'https://linkedin.com/in/emilyjohnson',
                'skills' => ['JavaScript', 'React', 'Node.js', 'Python', 'AWS', 'Docker'],
                'education' => [
                    [
                        'degree' => 'Bachelor of Science',
                        'field' => 'Computer Science',
                        'school' => 'University of Washington',
                        'graduation_year' => 2018
                    ]
                ],
                'experience' => [
                    [
                        'title' => 'Software Engineer',
                        'company' => 'Tech Startup Inc',
                        'start_date' => '2020-01-01',
                        'end_date' => 'current',
                        'description' => 'Full-stack development using React and Node.js'
                    ],
                    [
                        'title' => 'Junior Developer',
                        'company' => 'WebDev Solutions',
                        'start_date' => '2018-06-01',
                        'end_date' => '2019-12-31',
                        'description' => 'Frontend development and UI/UX implementation'
                    ]
                ],
                'current_title' => 'Software Engineer',
                'current_company' => 'Tech Startup Inc',
                'desired_salary' => 155000,
                'availability' => 'flexible',
                'open_to_remote' => true
            ],
            [
                'external_candidate_id' => 'ext_' . $connection->provider . '_cand_002',
                'first_name' => 'Marcus',
                'last_name' => 'Chen',
                'email' => 'marcus.chen@email.com',
                'phone' => '+1-555-0102',
                'address' => 'Austin, TX, USA',
                'linkedin_url' => 'https://linkedin.com/in/marcuschen',
                'portfolio_url' => 'https://marcuschen.dev',
                'skills' => ['Product Management', 'SQL', 'Tableau', 'A/B Testing', 'Agile', 'Scrum'],
                'education' => [
                    [
                        'degree' => 'Master of Business Administration',
                        'field' => 'Technology Management',
                        'school' => 'Stanford University',
                        'graduation_year' => 2017
                    ],
                    [
                        'degree' => 'Bachelor of Engineering',
                        'field' => 'Industrial Engineering',
                        'school' => 'UC Berkeley',
                        'graduation_year' => 2015
                    ]
                ],
                'experience' => [
                    [
                        'title' => 'Senior Product Manager',
                        'company' => 'Growth Corp',
                        'start_date' => '2019-03-01',
                        'end_date' => 'current',
                        'description' => 'Led product strategy for B2B SaaS platform'
                    ]
                ],
                'current_title' => 'Senior Product Manager',
                'current_company' => 'Growth Corp',
                'desired_salary' => 145000,
                'availability' => '1-month',
                'open_to_remote' => false
            ],
            [
                'external_candidate_id' => 'ext_' . $connection->provider . '_cand_003',
                'first_name' => 'Priya',
                'last_name' => 'Patel',
                'email' => 'priya.patel@email.com',
                'phone' => '+1-555-0103',
                'address' => 'Boston, MA, USA',
                'linkedin_url' => 'https://linkedin.com/in/priyapatel',
                'skills' => ['Python', 'R', 'Machine Learning', 'TensorFlow', 'SQL', 'Statistics', 'Data Visualization'],
                'education' => [
                    [
                        'degree' => 'PhD',
                        'field' => 'Data Science',
                        'school' => 'MIT',
                        'graduation_year' => 2020
                    ]
                ],
                'experience' => [
                    [
                        'title' => 'Data Scientist',
                        'company' => 'AI Research Lab',
                        'start_date' => '2020-09-01',
                        'end_date' => 'current',
                        'description' => 'Research and development of machine learning models'
                    ]
                ],
                'current_title' => 'Data Scientist',
                'current_company' => 'AI Research Lab',
                'desired_salary' => 140000,
                'availability' => '2-weeks',
                'open_to_remote' => true
            ]
        ];

        foreach ($candidates as $candidateData) {
            AtsCandidate::create(array_merge([
                'ats_connection_id' => $connection->id,
                'custom_fields' => [
                    'source' => ['LinkedIn', 'Company Website', 'Referral'][rand(0, 2)],
                    'resume_score' => rand(70, 95),
                    'interview_preference' => ['phone', 'video', 'in-person'][rand(0, 2)]
                ]
            ], $candidateData));
        }
    }

    private function createSampleApplications(AtsConnection $connection): void
    {
        $jobs = AtsJobPosting::where('ats_connection_id', $connection->id)->get();
        $candidates = AtsCandidate::where('ats_connection_id', $connection->id)->get();

        if ($jobs->isEmpty() || $candidates->isEmpty()) {
            return;
        }

        $statuses = ['new', 'screening', 'interview', 'assessment', 'offer', 'hired', 'rejected'];
        $applicationCount = 0;

        foreach ($jobs as $job) {
            $candidatesForJob = $candidates->shuffle()->take(rand(2, 4));

            foreach ($candidatesForJob as $candidate) {
                $applicationCount++;
                $status = $statuses[array_rand($statuses)];

                AtsApplication::create([
                    'ats_job_posting_id' => $job->id,
                    'ats_candidate_id' => $candidate->id,
                    'external_application_id' => 'ext_app_' . $connection->provider . '_' . sprintf('%03d', $applicationCount),
                    'status' => $status,
                    'cover_letter' => "I am very excited about the opportunity to join {$connection->name} as a {$job->title}. My background in " .
                                    implode(', ', array_slice($candidate->skills ?? [], 0, 3)) .
                                    " makes me an ideal candidate for this position.",
                    'attachments' => [
                        [
                            'name' => 'Resume_' . str_replace(' ', '_', $candidate->full_name) . '.pdf',
                            'url' => 'https://example.com/resumes/' . $candidate->id . '.pdf',
                            'type' => 'resume'
                        ]
                    ],
                    'questionnaire_responses' => [
                        'years_experience' => rand(2, 10),
                        'willing_to_relocate' => rand(0, 1) ? 'yes' : 'no',
                        'salary_expectation' => $candidate->desired_salary,
                        'start_date' => now()->addWeeks(rand(2, 8))->format('Y-m-d')
                    ],
                    'offered_salary' => $status === 'offer' ? $job->salary_min + rand(0, $job->salary_max - $job->salary_min) : null,
                    'applied_at' => now()->subDays(rand(1, 14)),
                    'status_updated_at' => now()->subDays(rand(0, 7)),
                    'rejection_reason' => $status === 'rejected' ?
                        ['Skills mismatch', 'Experience requirements not met', 'Cultural fit concerns', 'Position filled'][rand(0, 3)] : null,
                    'interview_notes' => $status === 'interview' ? [
                        [
                            'note' => 'Strong technical background. Good communication skills.',
                            'interviewer' => 'Technical Lead',
                            'created_at' => now()->subDays(rand(1, 5))->toISOString()
                        ]
                    ] : [],
                    'assessment_scores' => $status === 'assessment' ? [
                        'technical' => [
                            'score' => rand(70, 95),
                            'notes' => 'Solid performance on coding challenges',
                            'assessed_at' => now()->subDays(rand(1, 3))->toISOString()
                        ],
                        'cultural_fit' => [
                            'score' => rand(75, 90),
                            'notes' => 'Good alignment with company values',
                            'assessed_at' => now()->subDays(rand(1, 3))->toISOString()
                        ]
                    ] : [],
                    'custom_fields' => [
                        'referral_bonus_eligible' => rand(0, 1) ? true : false,
                        'interview_rounds_completed' => rand(0, 4),
                        'hiring_manager_rating' => rand(3, 5)
                    ]
                ]);
            }
        }
    }
}