<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CandidateScoringApiController extends Controller
{
    /**
     * Score candidate profile completeness
     */
    public function scoreProfile(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $user = auth()->user();
            $score = $this->calculateProfileScore($user);
            
            return response()->json([
                'overall_score' => $score['overall_score'],
                'max_score' => $score['max_score'],
                'percentage' => $score['percentage'],
                'breakdown' => $score['breakdown'],
                'recommendations' => $score['recommendations']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to calculate profile score',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Score candidate match for specific job
     */
    public function scoreJobMatch(Request $request): JsonResponse
    {
        $request->validate([
            'job_id' => 'required|exists:posts,id'
        ]);

        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $user = auth()->user();
            $job = Post::findOrFail($request->input('job_id'));
            
            $matchScore = $this->calculateJobMatchScore($user, $job);
            
            return response()->json([
                'job_title' => $job->title,
                'company_name' => $job->company_name ?? 'N/A',
                'match_score' => $matchScore['match_score'],
                'max_score' => $matchScore['max_score'],
                'match_percentage' => $matchScore['percentage'],
                'strengths' => $matchScore['strengths'],
                'gaps' => $matchScore['gaps'],
                'recommendations' => $matchScore['recommendations']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to calculate job match score',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Get recommended jobs based on candidate profile
     */
    public function getRecommendedJobs(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $user = auth()->user();
            $limit = $request->input('limit', 10);
            
            // Get recent active jobs
            $jobs = Post::where('active', 1)
                ->where('reviewed', 1)
                ->where('archived', 0)
                ->when($user->city_id, function($query) use ($user) {
                    $query->where('city_id', $user->city_id);
                })
                ->latest()
                ->limit($limit * 3) // Get more to filter down
                ->get();

            $scoredJobs = [];
            foreach ($jobs as $job) {
                $matchScore = $this->calculateJobMatchScore($user, $job);
                
                if ($matchScore['percentage'] >= 40) { // Only show jobs with 40%+ match
                    $scoredJobs[] = [
                        'job_id' => $job->id,
                        'title' => $job->title,
                        'company_name' => $job->company_name ?? 'N/A',
                        'location' => $job->city->name ?? 'N/A',
                        'match_percentage' => $matchScore['percentage'],
                        'key_strengths' => array_slice($matchScore['strengths'], 0, 3),
                        'posted_date' => $job->created_at->toDateString(),
                        'salary_range' => $job->salary_min && $job->salary_max ? 
                            "{$job->salary_min} - {$job->salary_max}" : null
                    ];
                }
            }

            // Sort by match percentage
            usort($scoredJobs, function($a, $b) {
                return $b['match_percentage'] <=> $a['match_percentage'];
            });

            return response()->json([
                'recommended_jobs' => array_slice($scoredJobs, 0, $limit),
                'total_analyzed' => count($jobs),
                'total_matches' => count($scoredJobs)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get job recommendations',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Analyze candidate skills and suggest improvements
     */
    public function analyzeSkills(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $user = auth()->user();
            $analysis = $this->performSkillsAnalysis($user);
            
            return response()->json([
                'current_skills' => $analysis['current_skills'],
                'skill_categories' => $analysis['skill_categories'],
                'trending_skills' => $analysis['trending_skills'],
                'skill_gaps' => $analysis['skill_gaps'],
                'development_recommendations' => $analysis['development_recommendations'],
                'market_demand' => $analysis['market_demand']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to analyze skills',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Get candidate ranking compared to similar profiles
     */
    public function getCandidateRanking(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $user = auth()->user();
            $ranking = $this->calculateCandidateRanking($user);
            
            return response()->json([
                'overall_ranking' => $ranking['overall_ranking'],
                'percentile' => $ranking['percentile'],
                'comparison_pool_size' => $ranking['comparison_pool_size'],
                'ranking_factors' => $ranking['ranking_factors'],
                'improvement_areas' => $ranking['improvement_areas'],
                'strengths' => $ranking['strengths']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to calculate ranking',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Calculate comprehensive profile score
     */
    private function calculateProfileScore(User $user): array
    {
        $scores = [
            'basic_info' => 0,
            'contact_info' => 0,
            'experience' => 0,
            'education' => 0,
            'skills' => 0,
            'certifications' => 0,
            'portfolio' => 0
        ];

        $maxScores = [
            'basic_info' => 20,
            'contact_info' => 15,
            'experience' => 25,
            'education' => 15,
            'skills' => 15,
            'certifications' => 5,
            'portfolio' => 5
        ];

        $recommendations = [];

        // Basic Information
        if ($user->name) $scores['basic_info'] += 5;
        if ($user->about) $scores['basic_info'] += 10;
        if ($user->avatar) $scores['basic_info'] += 5;
        
        if ($scores['basic_info'] < $maxScores['basic_info']) {
            $recommendations[] = 'Complete your basic profile information';
        }

        // Contact Information  
        if ($user->email) $scores['contact_info'] += 5;
        if ($user->phone) $scores['contact_info'] += 5;
        if ($user->city_id) $scores['contact_info'] += 5;
        
        if ($scores['contact_info'] < $maxScores['contact_info']) {
            $recommendations[] = 'Add complete contact information';
        }

        // Experience (check user's posts/applications history)
        $experienceScore = min(25, $user->created_at->diffInMonths(now()) * 2);
        $scores['experience'] = $experienceScore;

        // Skills - check if user has skills data
        if ($user->skills && count($user->skills ?? []) > 0) {
            $scores['skills'] = min(15, count($user->skills) * 3);
        } else {
            $recommendations[] = 'Add your professional skills';
        }

        $totalScore = array_sum($scores);
        $maxTotalScore = array_sum($maxScores);
        $percentage = round(($totalScore / $maxTotalScore) * 100, 1);

        return [
            'overall_score' => $totalScore,
            'max_score' => $maxTotalScore,
            'percentage' => $percentage,
            'breakdown' => array_map(function($category, $score) use ($maxScores) {
                return [
                    'score' => $score,
                    'max_score' => $maxScores[$category],
                    'percentage' => round(($score / $maxScores[$category]) * 100, 1)
                ];
            }, array_keys($scores), $scores),
            'recommendations' => $recommendations
        ];
    }

    /**
     * Calculate job match score
     */
    private function calculateJobMatchScore(User $user, Post $job): array
    {
        $matchFactors = [
            'location' => 0,
            'experience_level' => 0,
            'skills' => 0,
            'education' => 0,
            'industry' => 0
        ];

        $maxScores = [
            'location' => 20,
            'experience_level' => 25,
            'skills' => 30,
            'education' => 15,
            'industry' => 10
        ];

        $strengths = [];
        $gaps = [];
        $recommendations = [];

        // Location Match
        if ($user->city_id && $user->city_id == $job->city_id) {
            $matchFactors['location'] = 20;
            $strengths[] = 'Location match';
        } else {
            $gaps[] = 'Different location';
        }

        // Experience Level (basic estimation)
        $userExperience = $user->created_at->diffInYears(now());
        if ($userExperience >= 1) {
            $matchFactors['experience_level'] = min(25, $userExperience * 8);
            if ($userExperience >= 2) {
                $strengths[] = 'Relevant experience level';
            }
        } else {
            $gaps[] = 'Limited experience';
            $recommendations[] = 'Gain more experience through internships or projects';
        }

        // Skills matching (simplified)
        $userSkills = $user->skills ?? [];
        if (count($userSkills) > 0) {
            $matchFactors['skills'] = min(30, count($userSkills) * 6);
            $strengths[] = 'Relevant skills';
        } else {
            $gaps[] = 'Skills not specified';
            $recommendations[] = 'Add your professional skills to your profile';
        }

        // Industry/Category Match
        if ($user->category_id && $user->category_id == $job->category_id) {
            $matchFactors['industry'] = 10;
            $strengths[] = 'Industry match';
        }

        $totalScore = array_sum($matchFactors);
        $maxTotalScore = array_sum($maxScores);
        $percentage = round(($totalScore / $maxTotalScore) * 100, 1);

        return [
            'match_score' => $totalScore,
            'max_score' => $maxTotalScore,
            'percentage' => $percentage,
            'strengths' => $strengths,
            'gaps' => $gaps,
            'recommendations' => $recommendations
        ];
    }

    /**
     * Perform comprehensive skills analysis
     */
    private function performSkillsAnalysis(User $user): array
    {
        $currentSkills = $user->skills ?? [];
        
        // Common skill categories
        $skillCategories = [
            'Technical' => ['Programming', 'Web Development', 'Database Management', 'Cloud Computing'],
            'Leadership' => ['Team Management', 'Project Management', 'Strategic Planning'],
            'Communication' => ['Public Speaking', 'Written Communication', 'Negotiation'],
            'Creative' => ['Design', 'Content Creation', 'Marketing', 'Branding'],
            'Analytical' => ['Data Analysis', 'Research', 'Problem Solving', 'Statistics']
        ];

        // Trending skills (simplified)
        $trendingSkills = [
            'Artificial Intelligence',
            'Machine Learning',
            'Cloud Computing',
            'Cybersecurity',
            'Digital Marketing',
            'Data Science',
            'UX/UI Design',
            'Remote Work Management'
        ];

        $skillGaps = [];
        $marketDemand = [];

        // Analyze skill gaps based on user's industry
        if (count($currentSkills) < 5) {
            $skillGaps[] = 'Limited skill inventory - add more skills to improve profile';
        }

        // Generate market demand data (simplified)
        foreach ($trendingSkills as $skill) {
            $marketDemand[] = [
                'skill' => $skill,
                'demand_level' => 'High',
                'growth_rate' => rand(15, 40) . '%',
                'avg_salary_boost' => rand(10, 25) . '%'
            ];
        }

        return [
            'current_skills' => $currentSkills,
            'skill_categories' => $skillCategories,
            'trending_skills' => $trendingSkills,
            'skill_gaps' => $skillGaps,
            'development_recommendations' => [
                'Focus on high-demand technical skills',
                'Develop leadership and communication abilities',
                'Stay updated with industry trends',
                'Consider professional certifications'
            ],
            'market_demand' => array_slice($marketDemand, 0, 5)
        ];
    }

    /**
     * Calculate candidate ranking
     */
    private function calculateCandidateRanking(User $user): array
    {
        $profileScore = $this->calculateProfileScore($user);
        
        // Get comparison pool (simplified)
        $comparisonPoolSize = User::count();
        
        // Calculate ranking factors
        $rankingFactors = [
            'Profile Completeness' => $profileScore['percentage'],
            'Account Age' => min(100, $user->created_at->diffInMonths(now()) * 10),
            'Activity Level' => rand(60, 90), // Simplified
            'Industry Experience' => rand(70, 95) // Simplified
        ];

        $averageScore = array_sum($rankingFactors) / count($rankingFactors);
        $percentile = min(99, max(1, round($averageScore)));

        return [
            'overall_ranking' => round($comparisonPoolSize * (100 - $percentile) / 100),
            'percentile' => $percentile,
            'comparison_pool_size' => $comparisonPoolSize,
            'ranking_factors' => $rankingFactors,
            'improvement_areas' => [
                'Complete all profile sections',
                'Add more professional skills',
                'Engage more with the platform',
                'Update profile regularly'
            ],
            'strengths' => array_keys(array_filter($rankingFactors, function($score) {
                return $score >= 80;
            }))
        ];
    }
}