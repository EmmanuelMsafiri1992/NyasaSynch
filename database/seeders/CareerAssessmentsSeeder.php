<?php

namespace Database\Seeders;

use App\Models\CareerAssessment;
use Illuminate\Database\Seeder;

class CareerAssessmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assessments = [
            [
                'title' => 'Comprehensive Career Assessment',
                'slug' => 'comprehensive-career-assessment',
                'description' => 'A complete personality and skills evaluation with detailed career recommendations',
                'assessment_type' => 'comprehensive',
                'questions' => [
                    [
                        'id' => 1,
                        'question' => 'I enjoy working with complex data and numbers.',
                        'type' => 'scale',
                        'scale_max' => 5,
                        'scale_labels' => ['min' => 'Strongly Disagree', 'max' => 'Strongly Agree'],
                        'traits' => ['analytical' => 0.8, 'detail_oriented' => 0.6]
                    ],
                    [
                        'id' => 2,
                        'question' => 'I prefer working alone rather than in teams.',
                        'type' => 'scale',
                        'scale_max' => 5,
                        'scale_labels' => ['min' => 'Strongly Disagree', 'max' => 'Strongly Agree'],
                        'traits' => ['independent' => 0.7, 'introverted' => 0.5]
                    ],
                    [
                        'id' => 3,
                        'question' => 'I am comfortable presenting ideas to large groups.',
                        'type' => 'scale',
                        'scale_max' => 5,
                        'scale_labels' => ['min' => 'Strongly Disagree', 'max' => 'Strongly Agree'],
                        'traits' => ['communication' => 0.8, 'leadership' => 0.6, 'confident' => 0.7]
                    ],
                    [
                        'id' => 4,
                        'question' => 'I like to create new solutions to problems.',
                        'type' => 'scale',
                        'scale_max' => 5,
                        'scale_labels' => ['min' => 'Strongly Disagree', 'max' => 'Strongly Agree'],
                        'traits' => ['creative' => 0.9, 'innovative' => 0.8, 'problem_solving' => 0.7]
                    ],
                    [
                        'id' => 5,
                        'question' => 'I pay close attention to details and accuracy.',
                        'type' => 'scale',
                        'scale_max' => 5,
                        'scale_labels' => ['min' => 'Strongly Disagree', 'max' => 'Strongly Agree'],
                        'traits' => ['detail_oriented' => 0.9, 'organized' => 0.6]
                    ],
                    [
                        'id' => 6,
                        'question' => 'What type of work environment do you prefer?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['value' => '1', 'text' => 'Fast-paced startup environment'],
                            ['value' => '2', 'text' => 'Structured corporate setting'],
                            ['value' => '3', 'text' => 'Creative agency atmosphere'],
                            ['value' => '4', 'text' => 'Remote work flexibility']
                        ]
                    ],
                    [
                        'id' => 7,
                        'question' => 'I enjoy helping others solve their problems.',
                        'type' => 'scale',
                        'scale_max' => 5,
                        'scale_labels' => ['min' => 'Strongly Disagree', 'max' => 'Strongly Agree'],
                        'traits' => ['empathetic' => 0.8, 'service_oriented' => 0.9]
                    ],
                    [
                        'id' => 8,
                        'question' => 'I am motivated by competition and winning.',
                        'type' => 'scale',
                        'scale_max' => 5,
                        'scale_labels' => ['min' => 'Strongly Disagree', 'max' => 'Strongly Agree'],
                        'traits' => ['competitive' => 0.9, 'goal_oriented' => 0.7]
                    ],
                    [
                        'id' => 9,
                        'question' => 'I prefer to have clear instructions and procedures.',
                        'type' => 'scale',
                        'scale_max' => 5,
                        'scale_labels' => ['min' => 'Strongly Disagree', 'max' => 'Strongly Agree'],
                        'traits' => ['structured' => 0.8, 'organized' => 0.6]
                    ],
                    [
                        'id' => 10,
                        'question' => 'Which skills do you excel at?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['value' => '1', 'text' => 'Technical and analytical skills'],
                            ['value' => '2', 'text' => 'Communication and interpersonal skills'],
                            ['value' => '3', 'text' => 'Creative and design skills'],
                            ['value' => '4', 'text' => 'Leadership and management skills']
                        ]
                    ]
                ],
                'scoring_algorithm' => [
                    'personality_traits' => ['analytical', 'creative', 'communication', 'leadership', 'detail_oriented'],
                    'scoring_method' => 'weighted_average'
                ],
                'result_categories' => [
                    'analytical' => 'Data-driven and logical thinker',
                    'creative' => 'Innovative and artistic personality',
                    'leader' => 'Natural leader and motivator',
                    'helper' => 'Service-oriented and supportive'
                ],
                'estimated_duration' => 20,
                'total_questions' => 10,
                'is_active' => true
            ],
            [
                'title' => 'Quick Career Match',
                'slug' => 'quick-career-match',
                'description' => 'Fast personality assessment for immediate job recommendations',
                'assessment_type' => 'personality',
                'questions' => [
                    [
                        'id' => 1,
                        'question' => 'I work best with:',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['value' => '1', 'text' => 'Numbers and data'],
                            ['value' => '2', 'text' => 'People and ideas'],
                            ['value' => '3', 'text' => 'Creative projects'],
                            ['value' => '4', 'text' => 'Systems and processes']
                        ]
                    ],
                    [
                        'id' => 2,
                        'question' => 'My ideal work pace is:',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['value' => '1', 'text' => 'Fast-paced and dynamic'],
                            ['value' => '2', 'text' => 'Steady and consistent'],
                            ['value' => '3', 'text' => 'Flexible and varied'],
                            ['value' => '4', 'text' => 'Structured and planned']
                        ]
                    ],
                    [
                        'id' => 3,
                        'question' => 'I prefer to:',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['value' => '1', 'text' => 'Lead projects and teams'],
                            ['value' => '2', 'text' => 'Support and assist others'],
                            ['value' => '3', 'text' => 'Work independently'],
                            ['value' => '4', 'text' => 'Collaborate in small groups']
                        ]
                    ],
                    [
                        'id' => 4,
                        'question' => 'What motivates you most?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['value' => '1', 'text' => 'Solving complex problems'],
                            ['value' => '2', 'text' => 'Helping others succeed'],
                            ['value' => '3', 'text' => 'Creating something new'],
                            ['value' => '4', 'text' => 'Achieving targets and goals']
                        ]
                    ],
                    [
                        'id' => 5,
                        'question' => 'Your communication style is:',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['value' => '1', 'text' => 'Direct and factual'],
                            ['value' => '2', 'text' => 'Warm and personal'],
                            ['value' => '3', 'text' => 'Inspiring and visionary'],
                            ['value' => '4', 'text' => 'Detailed and thorough']
                        ]
                    ]
                ],
                'scoring_algorithm' => [
                    'personality_traits' => ['analytical', 'people_oriented', 'creative', 'organized'],
                    'scoring_method' => 'category_match'
                ],
                'result_categories' => [
                    'analyst' => 'Analytical and data-driven',
                    'people_person' => 'People-focused and collaborative',
                    'creator' => 'Creative and innovative',
                    'organizer' => 'Structured and methodical'
                ],
                'estimated_duration' => 5,
                'total_questions' => 5,
                'is_active' => true
            ]
        ];

        foreach ($assessments as $assessment) {
            CareerAssessment::create($assessment);
        }
    }
}