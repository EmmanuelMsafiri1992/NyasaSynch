<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareerAssessment;
use App\Models\UserAssessmentResult;
use App\Models\CareerPlan;
use App\Models\CareerPlanMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CareerAssessmentApiController extends Controller
{
    /**
     * Get available career assessments (index method for route)
     */
    public function index(Request $request): JsonResponse
    {
        return $this->getAssessments($request);
    }

    /**
     * Get available career assessments
     */
    public function getAssessments(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type');
            
            $query = CareerAssessment::where('is_active', true);
            
            if ($type) {
                $query->where('assessment_type', $type);
            }
            
            $assessments = $query->orderBy('id')
                ->get(['id', 'title as name', 'description', 'assessment_type as type', 'estimated_duration', 'total_questions as question_count']);
            
            return response()->json(['assessments' => $assessments]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch assessments',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Start a new assessment
     */
    public function startAssessment(Request $request): JsonResponse
    {
        $request->validate([
            'assessment_id' => 'required|exists:career_assessments,id',
            'type' => 'required|string|in:comprehensive,quick'
        ]);

        try {
            $assessment = CareerAssessment::findOrFail($request->input('assessment_id'));
            
            // Create user assessment result record
            $userResult = UserAssessmentResult::create([
                'user_id' => auth()->id(),
                'assessment_id' => $assessment->id,
                'started_at' => now(),
                'status' => 'in_progress'
            ]);

            return response()->json([
                'session_id' => $userResult->id,
                'assessment' => [
                    'id' => $assessment->id,
                    'name' => $assessment->name,
                    'type' => $assessment->type,
                    'questions' => $assessment->questions,
                    'total_questions' => count($assessment->questions ?? [])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to start assessment',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Submit assessment answers
     */
    public function submitAssessment(Request $request): JsonResponse
    {
        $request->validate([
            'assessment_id' => 'required|exists:career_assessments,id',
            'answers' => 'required|array'
        ]);

        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $assessment = CareerAssessment::findOrFail($request->input('assessment_id'));
            $answers = $request->input('answers');
            
            // Calculate results based on assessment type and answers
            $results = $this->calculateAssessmentResults($assessment, $answers);
            
            // Save or update user assessment result
            $userResult = UserAssessmentResult::updateOrCreate([
                'user_id' => auth()->id(),
                'assessment_id' => $assessment->id
            ], [
                'answers' => $answers,
                'results' => $results,
                'primary_result' => $results['primary_career_match'] ?? null,
                'completed_at' => now(),
                'status' => 'completed'
            ]);

            return response()->json([
                'result_id' => $userResult->id,
                'message' => 'Assessment completed successfully',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to submit assessment',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Get assessment results
     */
    public function getResults(Request $request): JsonResponse
    {
        $request->validate([
            'result_id' => 'required|exists:user_assessment_results,id'
        ]);

        try {
            $result = UserAssessmentResult::with('assessment')
                ->where('id', $request->input('result_id'))
                ->where('user_id', auth()->id())
                ->firstOrFail();

            return response()->json([
                'assessment_name' => $result->assessment->name,
                'completed_at' => $result->completed_at,
                'results' => $result->results,
                'primary_result' => $result->primary_result,
                'recommendations' => $this->generateRecommendations($result->results)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch results',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Create career plan based on assessment results
     */
    public function createCareerPlan(Request $request): JsonResponse
    {
        $request->validate([
            'result_id' => 'required|exists:user_assessment_results,id',
            'target_role' => 'required|string|max:255',
            'timeline_years' => 'required|integer|min:1|max:10',
            'focus_areas' => 'nullable|array'
        ]);

        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $result = UserAssessmentResult::where('id', $request->input('result_id'))
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $plan = CareerPlan::create([
                'user_id' => auth()->id(),
                'assessment_result_id' => $result->id,
                'target_role' => $request->input('target_role'),
                'current_role' => $request->input('current_role'),
                'timeline_years' => $request->input('timeline_years'),
                'focus_areas' => $request->input('focus_areas', []),
                'plan_data' => $this->generatePlanData($result->results, $request->all()),
                'status' => 'active'
            ]);

            // Create initial milestones
            $this->createPlanMilestones($plan);

            return response()->json([
                'plan_id' => $plan->id,
                'message' => 'Career plan created successfully',
                'plan' => $plan->load('milestones')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create career plan',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Get user's career plans
     */
    public function getCareerPlans(): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $plans = CareerPlan::where('user_id', auth()->id())
                ->with(['milestones' => function($query) {
                    $query->orderBy('target_date');
                }])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['plans' => $plans]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch career plans',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Update milestone progress
     */
    public function updateMilestone(Request $request): JsonResponse
    {
        $request->validate([
            'milestone_id' => 'required|exists:career_plan_milestones,id',
            'status' => 'required|string|in:pending,in_progress,completed,deferred',
            'notes' => 'nullable|string|max:1000'
        ]);

        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $milestone = CareerPlanMilestone::whereHas('plan', function($query) {
                $query->where('user_id', auth()->id());
            })->findOrFail($request->input('milestone_id'));

            $milestone->update([
                'status' => $request->input('status'),
                'notes' => $request->input('notes'),
                'completed_at' => $request->input('status') === 'completed' ? now() : null
            ]);

            return response()->json([
                'message' => 'Milestone updated successfully',
                'milestone' => $milestone
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update milestone',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Calculate assessment results based on answers
     */
    private function calculateAssessmentResults(CareerAssessment $assessment, array $answers): array
    {
        $results = [
            'personality_traits' => [],
            'skill_strengths' => [],
            'career_matches' => [],
            'development_areas' => []
        ];

        // Personality trait scoring
        $traitScores = [];
        foreach ($assessment->questions as $questionIndex => $question) {
            if (!isset($answers[$questionIndex])) continue;
            
            $answer = $answers[$questionIndex];
            
            // Score based on question category and answer
            if (isset($question['traits'])) {
                foreach ($question['traits'] as $trait => $weight) {
                    if (!isset($traitScores[$trait])) {
                        $traitScores[$trait] = 0;
                    }
                    $traitScores[$trait] += (int)$answer * $weight;
                }
            }
        }

        // Normalize scores and identify top traits
        foreach ($traitScores as $trait => $score) {
            $normalizedScore = min(100, max(0, $score));
            $results['personality_traits'][$trait] = $normalizedScore;
        }

        // Generate career matches based on top traits
        $results['career_matches'] = $this->generateCareerMatches($results['personality_traits']);
        $results['primary_career_match'] = $results['career_matches'][0]['title'] ?? null;

        return $results;
    }

    /**
     * Show specific assessment type (show method for route)
     */
    public function show(Request $request, $type): JsonResponse
    {
        try {
            $assessment = CareerAssessment::where('assessment_type', $type)
                ->where('is_active', true)
                ->first();
                
            if (!$assessment) {
                return response()->json(['error' => 'Assessment type not found'], 404);
            }
            
            return response()->json(['assessment' => $assessment]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch assessment',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Submit assessment (submit method for route)
     */
    public function submit(Request $request): JsonResponse
    {
        return $this->submitAssessment($request);
    }

    /**
     * Get assessment results (results method for route)
     */
    public function results(Request $request, $id): JsonResponse
    {
        try {
            $result = UserAssessmentResult::with('assessment')
                ->where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            return response()->json([
                'assessment_name' => $result->assessment->name,
                'completed_at' => $result->completed_at,
                'results' => $result->results,
                'primary_result' => $result->primary_result,
                'recommendations' => $this->generateRecommendations($result->results)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch results',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Generate career matches based on personality traits
     */
    private function generateCareerMatches(array $traits): array
    {
        $careerDatabase = [
            'Software Engineer' => ['analytical' => 90, 'creative' => 70, 'detail_oriented' => 85],
            'Marketing Manager' => ['creative' => 85, 'communication' => 90, 'leadership' => 80],
            'Data Scientist' => ['analytical' => 95, 'detail_oriented' => 90, 'research_oriented' => 85],
            'Project Manager' => ['leadership' => 85, 'communication' => 80, 'organized' => 90],
            'UX Designer' => ['creative' => 90, 'empathetic' => 85, 'detail_oriented' => 75],
            'Sales Representative' => ['communication' => 90, 'persuasive' => 85, 'competitive' => 80]
        ];

        $matches = [];
        foreach ($careerDatabase as $career => $requirements) {
            $matchScore = 0;
            $totalWeight = 0;

            foreach ($requirements as $trait => $weight) {
                $userScore = $traits[$trait] ?? 50;
                $matchScore += ($userScore * $weight / 100);
                $totalWeight += $weight;
            }

            if ($totalWeight > 0) {
                $finalScore = ($matchScore / $totalWeight) * 100;
                $matches[] = [
                    'title' => $career,
                    'match_percentage' => round($finalScore, 1),
                    'key_strengths' => array_keys($requirements)
                ];
            }
        }

        // Sort by match percentage
        usort($matches, function($a, $b) {
            return $b['match_percentage'] <=> $a['match_percentage'];
        });

        return array_slice($matches, 0, 5);
    }

    /**
     * Generate recommendations based on results
     */
    private function generateRecommendations(array $results): array
    {
        $recommendations = [
            'immediate_actions' => [],
            'skill_development' => [],
            'networking_tips' => [],
            'resources' => []
        ];

        if (!empty($results['career_matches'])) {
            $topMatch = $results['career_matches'][0];
            
            $recommendations['immediate_actions'][] = "Research {$topMatch['title']} job requirements in your area";
            $recommendations['immediate_actions'][] = "Update your resume to highlight relevant skills";
            
            $recommendations['skill_development'][] = "Focus on developing skills relevant to {$topMatch['title']}";
            $recommendations['networking_tips'][] = "Join professional groups for {$topMatch['title']} professionals";
        }

        return $recommendations;
    }

    /**
     * Generate plan data based on results and user inputs
     */
    private function generatePlanData(array $results, array $inputs): array
    {
        return [
            'assessment_summary' => $results,
            'target_role' => $inputs['target_role'],
            'timeline_years' => $inputs['timeline_years'],
            'focus_areas' => $inputs['focus_areas'] ?? [],
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Create initial milestones for a career plan
     */
    private function createPlanMilestones(CareerPlan $plan): void
    {
        $timelineYears = $plan->timeline_years;
        $milestones = [];

        // Generate milestones based on timeline
        for ($year = 1; $year <= $timelineYears; $year++) {
            $milestones[] = [
                'career_plan_id' => $plan->id,
                'title' => "Year {$year} Milestone",
                'description' => "Key objectives and skills to develop in year {$year}",
                'target_date' => now()->addYears($year)->endOfYear(),
                'milestone_type' => 'annual',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Add quarterly milestones for first year
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $milestones[] = [
                'career_plan_id' => $plan->id,
                'title' => "Q{$quarter} Checkpoint",
                'description' => "Quarterly progress review and skill development",
                'target_date' => now()->addMonths($quarter * 3)->endOfMonth(),
                'milestone_type' => 'quarterly',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        CareerPlanMilestone::insert($milestones);
    }
}