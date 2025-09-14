<?php

namespace App\Services;

use App\Models\CandidateScore;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class CandidateScoringService
{
    /**
     * Calculate or update candidate score
     */
    public function calculateScore(User $user): CandidateScore
    {
        $candidateScore = CandidateScore::firstOrNew(['user_id' => $user->id]);
        
        // Load user relationship for calculations
        $candidateScore->setRelation('user', $user);
        
        // Calculate all score components
        $candidateScore->calculateTotalScore();
        
        // Save the updated scores
        $candidateScore->save();
        
        return $candidateScore;
    }

    /**
     * Batch calculate scores for multiple users
     */
    public function batchCalculateScores(Collection $users = null): int
    {
        if ($users === null) {
            $users = User::where('user_type_id', 2) // Job seekers only
                ->with('resumes')
                ->get();
        }

        $processed = 0;
        
        foreach ($users as $user) {
            try {
                $this->calculateScore($user);
                $processed++;
            } catch (\Exception $e) {
                // Log error but continue processing
                \Log::error("Failed to calculate score for user {$user->id}: " . $e->getMessage());
            }
        }

        return $processed;
    }

    /**
     * Get top candidates with scores
     */
    public function getTopCandidates(int $limit = 100, float $minScore = 70): Collection
    {
        return CandidateScore::with('user')
            ->aboveScore($minScore)
            ->topCandidates($limit)
            ->get();
    }

    /**
     * Search candidates by score and criteria
     */
    public function searchCandidates(array $criteria = []): Builder
    {
        $query = CandidateScore::with('user.resumes', 'user.city');

        // Score filtering
        if (isset($criteria['min_score'])) {
            $query->where('total_score', '>=', $criteria['min_score']);
        }

        if (isset($criteria['score_tier'])) {
            $scoreRanges = [
                'A' => [90, 100],
                'B' => [80, 89],
                'C' => [70, 79],
                'D' => [60, 69],
                'F' => [0, 59]
            ];
            
            if (isset($scoreRanges[$criteria['score_tier']])) {
                [$min, $max] = $scoreRanges[$criteria['score_tier']];
                $query->whereBetween('total_score', [$min, $max]);
            }
        }

        // Activity filtering
        if (isset($criteria['active_last_days'])) {
            $query->where('days_active_last_30', '>=', $criteria['active_last_days']);
        }

        // Verification filtering
        if (!empty($criteria['verified_only'])) {
            $query->where('email_verified', true)
                  ->where('phone_verified', true);
        }

        // Profile completion filtering
        if (isset($criteria['min_profile_completion'])) {
            $query->where('profile_completion_percentage', '>=', $criteria['min_profile_completion']);
        }

        // User criteria
        if (!empty($criteria['location'])) {
            $query->whereHas('user', function($q) use ($criteria) {
                $q->where('city_id', $criteria['location']);
            });
        }

        if (!empty($criteria['skills'])) {
            $skills = is_array($criteria['skills']) ? $criteria['skills'] : [$criteria['skills']];
            $query->whereHas('user', function($q) use ($skills) {
                foreach ($skills as $skill) {
                    $q->where('tags', 'like', "%{$skill}%");
                }
            });
        }

        return $query->orderByDesc('total_score');
    }

    /**
     * Get candidates needing score updates
     */
    public function getCandidatesNeedingUpdate(int $daysStale = 7): Collection
    {
        return CandidateScore::where(function($query) use ($daysStale) {
            $query->whereNull('last_calculated_at')
                  ->orWhere('last_calculated_at', '<', now()->subDays($daysStale));
        })->with('user')->get();
    }

    /**
     * Log activity and update relevant scores
     */
    public function logActivityAndUpdateScore(int $userId, string $activityType, array $data = []): void
    {
        // Log the activity
        UserActivityLog::logActivity($userId, $activityType, $data);

        // For certain activities, immediately update the score
        $immediateUpdateActivities = [
            'profile_update',
            'resume_uploaded',
            'verification_completed',
            'application_sent'
        ];

        if (in_array($activityType, $immediateUpdateActivities)) {
            $user = User::find($userId);
            if ($user) {
                $this->calculateScore($user);
            }
        }
    }

    /**
     * Get score improvement suggestions for a user
     */
    public function getImprovementSuggestions(User $user): array
    {
        $score = CandidateScore::where('user_id', $user->id)->first();
        if (!$score) {
            $score = $this->calculateScore($user);
        }

        $suggestions = [];

        // Profile completion suggestions
        if ($score->profile_completion_score < 80) {
            if (empty($user->photo)) {
                $suggestions[] = [
                    'type' => 'profile_completion',
                    'action' => 'Add professional photo',
                    'impact' => 'medium',
                    'points_potential' => 15
                ];
            }
            
            if (empty($user->description) || strlen($user->description) < 100) {
                $suggestions[] = [
                    'type' => 'profile_completion',
                    'action' => 'Write detailed professional summary',
                    'impact' => 'medium',
                    'points_potential' => 15
                ];
            }
            
            if ($user->resumes()->count() == 0) {
                $suggestions[] = [
                    'type' => 'profile_completion',
                    'action' => 'Upload your resume',
                    'impact' => 'high',
                    'points_potential' => 15
                ];
            }
        }

        // Verification suggestions
        if ($score->verification_score < 60) {
            if (!$score->phone_verified) {
                $suggestions[] = [
                    'type' => 'verification',
                    'action' => 'Verify your phone number',
                    'impact' => 'high',
                    'points_potential' => 20
                ];
            }
            
            if (!$score->linkedin_verified) {
                $suggestions[] = [
                    'type' => 'verification',
                    'action' => 'Connect your LinkedIn profile',
                    'impact' => 'high',
                    'points_potential' => 20
                ];
            }
        }

        // Activity suggestions
        if ($score->activity_score < 50) {
            $suggestions[] = [
                'type' => 'activity',
                'action' => 'Apply to more jobs regularly',
                'impact' => 'high',
                'points_potential' => 30
            ];
            
            $suggestions[] = [
                'type' => 'activity',
                'action' => 'Log in more frequently',
                'impact' => 'low',
                'points_potential' => 10
            ];
        }

        // Response rate suggestions
        if ($score->response_rate_score < 70 && $score->total_messages_received > 0) {
            $suggestions[] = [
                'type' => 'communication',
                'action' => 'Respond to employer messages within 24 hours',
                'impact' => 'high',
                'points_potential' => 25
            ];
        }

        return $suggestions;
    }

    /**
     * Get score analytics for admin dashboard
     */
    public function getScoreAnalytics(): array
    {
        $totalCandidates = CandidateScore::count();
        
        return [
            'total_candidates' => $totalCandidates,
            'score_distribution' => [
                'A' => CandidateScore::where('total_score', '>=', 90)->count(),
                'B' => CandidateScore::whereBetween('total_score', [80, 89])->count(),
                'C' => CandidateScore::whereBetween('total_score', [70, 79])->count(),
                'D' => CandidateScore::whereBetween('total_score', [60, 69])->count(),
                'F' => CandidateScore::where('total_score', '<', 60)->count(),
            ],
            'average_scores' => [
                'overall' => CandidateScore::avg('total_score'),
                'profile_completion' => CandidateScore::avg('profile_completion_score'),
                'activity' => CandidateScore::avg('activity_score'),
                'verification' => CandidateScore::avg('verification_score'),
                'response_rate' => CandidateScore::avg('response_rate_score'),
                'success_rate' => CandidateScore::avg('success_rate_score'),
            ],
            'verification_stats' => [
                'email_verified' => CandidateScore::where('email_verified', true)->count(),
                'phone_verified' => CandidateScore::where('phone_verified', true)->count(),
                'linkedin_verified' => CandidateScore::where('linkedin_verified', true)->count(),
                'education_verified' => CandidateScore::where('education_verified', true)->count(),
                'employment_verified' => CandidateScore::where('employment_verified', true)->count(),
            ]
        ];
    }
}