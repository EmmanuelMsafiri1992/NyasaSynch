<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Web\Public\FrontController;
use App\Models\CareerAssessment;
use App\Models\UserAssessmentResult;
use App\Models\CareerGuide;
use App\Models\CareerPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerToolsController extends FrontController
{
    /**
     * Show career tools index page
     */
    public function index(): View
    {
        return view('tools.index');
    }

    /**
     * Show salary calculator tool
     */
    public function salaryCalculator(): View
    {
        return view('tools.salary-calculator');
    }

    /**
     * Show career quiz selection or assessment
     */
    public function careerQuiz(Request $request, string $type = null): View
    {
        $assessment = null;
        
        if ($type) {
            // Get or create assessment based on type
            $assessment = CareerAssessment::where('assessment_type', $type)
                ->where('is_active', true)
                ->first();
                
            if (!$assessment) {
                // Create default assessment if none exists
                $assessment = $this->createDefaultAssessment($type);
            }
        }

        return view('tools.career-quiz', compact('assessment'));
    }

    /**
     * Show quiz results
     */
    public function quizResults(string $resultId): View
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $result = UserAssessmentResult::with('assessment')
            ->where('id', $resultId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $recommendations = $this->generateRecommendations($result);

        return view('tools.quiz-results', compact('result', 'recommendations'));
    }

    /**
     * Show career guides library
     */
    public function careerGuides(Request $request): View
    {
        $query = CareerGuide::where('is_published', true);
        
        // Apply filters
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }
        
        $guides = $query->orderBy('is_featured', 'desc')
            ->orderBy('view_count', 'desc')
            ->paginate(12);
            
        $categories = CareerGuide::where('is_published', true)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        $featuredGuides = CareerGuide::where('is_published', true)
            ->where('is_featured', true)
            ->orderBy('view_count', 'desc')
            ->limit(6)
            ->get();

        return view('resources.career-guides', compact('guides', 'categories', 'featuredGuides'));
    }

    /**
     * Show individual career guide
     */
    public function showCareerGuide(CareerGuide $guide): View
    {
        if (!$guide->is_published) {
            abort(404);
        }

        // Increment view count
        $guide->increment('view_count');

        // Get related guides
        $relatedGuides = CareerGuide::where('is_published', true)
            ->where('category', $guide->category)
            ->where('id', '!=', $guide->id)
            ->orderBy('view_count', 'desc')
            ->limit(6)
            ->get();

        return view('resources.career-guide-detail', compact('guide', 'relatedGuides'));
    }

    /**
     * Show career planning dashboard
     */
    public function careerPlanning(): View
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Get user's career plans
        $careerPlans = CareerPlan::where('user_id', $user->id)
            ->with(['milestones' => function($query) {
                $query->orderBy('target_date');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get user's assessment results
        $assessmentResults = UserAssessmentResult::where('user_id', $user->id)
            ->with('assessment')
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        return view('tools.career-planning', compact('careerPlans', 'assessmentResults'));
    }

    /**
     * Show candidate profile scoring
     */
    public function profileScoring(): View
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        return view('tools.profile-scoring');
    }

    /**
     * Create default assessment for testing
     */
    private function createDefaultAssessment(string $type): CareerAssessment
    {
        $questions = $type === 'comprehensive' ? $this->getComprehensiveQuestions() : $this->getQuickQuestions();
        
        return CareerAssessment::create([
            'title' => ucfirst($type) . ' Career Assessment',
            'slug' => strtolower($type) . '-career-assessment',
            'description' => 'Discover your ideal career path with our ' . $type . ' assessment',
            'assessment_type' => $type,
            'questions' => $questions,
            'scoring_algorithm' => ['type' => 'personality_traits'],
            'result_categories' => ['default' => 'General assessment results'],
            'estimated_duration' => $type === 'comprehensive' ? 25 : 10,
            'total_questions' => count($questions),
            'is_active' => true
        ]);
    }

    /**
     * Get comprehensive assessment questions
     */
    private function getComprehensiveQuestions(): array
    {
        return [
            [
                'question' => 'How do you prefer to work on projects?',
                'type' => 'multiple_choice',
                'options' => [
                    ['text' => 'Independently with minimal supervision', 'value' => '4'],
                    ['text' => 'In a small team with close collaboration', 'value' => '3'],
                    ['text' => 'In a large team with defined roles', 'value' => '2'],
                    ['text' => 'With constant guidance and support', 'value' => '1']
                ],
                'traits' => ['independent' => 1, 'collaborative' => -0.5]
            ],
            [
                'question' => 'What motivates you most in your work?',
                'type' => 'multiple_choice',
                'options' => [
                    ['text' => 'Solving complex problems', 'value' => '4'],
                    ['text' => 'Helping others succeed', 'value' => '3'],
                    ['text' => 'Creating something new', 'value' => '2'],
                    ['text' => 'Financial rewards', 'value' => '1']
                ],
                'traits' => ['analytical' => 1, 'helpful' => 0.8, 'creative' => 0.9]
            ],
            [
                'question' => 'How do you handle stressful situations?',
                'type' => 'scale',
                'scale_min' => 1,
                'scale_max' => 5,
                'scale_labels' => [
                    'min' => 'I get overwhelmed easily',
                    'max' => 'I thrive under pressure'
                ],
                'traits' => ['stress_tolerance' => 1]
            ],
            [
                'question' => 'What work environment do you prefer?',
                'type' => 'multiple_choice',
                'options' => [
                    ['text' => 'Fast-paced, dynamic startup', 'value' => '4'],
                    ['text' => 'Structured corporate environment', 'value' => '3'],
                    ['text' => 'Creative agency or studio', 'value' => '2'],
                    ['text' => 'Remote/flexible work arrangement', 'value' => '1']
                ],
                'traits' => ['adaptable' => 1, 'structured' => 0.7]
            ],
            [
                'question' => 'How important is work-life balance to you?',
                'type' => 'scale',
                'scale_min' => 1,
                'scale_max' => 5,
                'scale_labels' => [
                    'min' => 'Not important - I live to work',
                    'max' => 'Extremely important - I work to live'
                ],
                'traits' => ['work_life_balance' => 1]
            ]
        ];
    }

    /**
     * Get quick assessment questions
     */
    private function getQuickQuestions(): array
    {
        return [
            [
                'question' => 'What describes you best?',
                'type' => 'multiple_choice',
                'options' => [
                    ['text' => 'Analytical problem solver', 'value' => '4'],
                    ['text' => 'Creative innovator', 'value' => '3'],
                    ['text' => 'People-focused leader', 'value' => '2'],
                    ['text' => 'Detail-oriented organizer', 'value' => '1']
                ],
                'traits' => ['analytical' => 1, 'creative' => 0.9, 'leadership' => 0.8, 'detail_oriented' => 0.7]
            ],
            [
                'question' => 'Your ideal work involves:',
                'type' => 'multiple_choice',
                'options' => [
                    ['text' => 'Technology and innovation', 'value' => '4'],
                    ['text' => 'Communication and relationships', 'value' => '3'],
                    ['text' => 'Analysis and research', 'value' => '2'],
                    ['text' => 'Creativity and design', 'value' => '1']
                ],
                'traits' => ['technical' => 1, 'communication' => 0.9, 'research_oriented' => 0.8, 'creative' => 0.7]
            ],
            [
                'question' => 'You prefer to work:',
                'type' => 'multiple_choice',
                'options' => [
                    ['text' => 'Independently', 'value' => '2'],
                    ['text' => 'In teams', 'value' => '1']
                ],
                'traits' => ['independent' => 1, 'collaborative' => -0.5]
            ]
        ];
    }

    /**
     * Generate recommendations based on assessment results
     */
    private function generateRecommendations(UserAssessmentResult $result): array
    {
        $recommendations = [
            'career_matches' => $result->results['career_matches'] ?? [],
            'next_steps' => [
                'Update your resume to highlight relevant skills',
                'Research companies in your target industry',
                'Network with professionals in your field of interest',
                'Consider additional training or certifications'
            ],
            'resources' => [
                [
                    'title' => 'Industry Career Guide',
                    'description' => 'Comprehensive guide to your recommended career path',
                    'url' => route('career-guides')
                ],
                [
                    'title' => 'Salary Calculator',
                    'description' => 'Research salary expectations for your target role',
                    'url' => route('salary-calculator')
                ],
                [
                    'title' => 'Career Planning Tools',
                    'description' => 'Create a personalized career development plan',
                    'url' => route('career-planning')
                ]
            ]
        ];

        return $recommendations;
    }
}