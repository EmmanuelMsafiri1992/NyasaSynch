<?php

namespace App\Services;

use App\Models\SalaryData;
use App\Models\CostOfLiving;
use App\Models\UserSalarySubmission;
use Illuminate\Support\Collection;

class SalaryCalculatorService
{
    /**
     * Calculate salary estimate for a job title and location
     */
    public function calculateSalary(array $params): array
    {
        $jobTitle = $params['job_title'] ?? '';
        $location = $params['location'] ?? '';
        $experience = (int)($params['experience'] ?? 0);
        $company_size = $params['company_size'] ?? null;
        
        // Get base salary data
        $query = SalaryData::forJobTitle($jobTitle)
            ->forExperience($experience)
            ->recent()
            ->verified();
            
        if ($location) {
            $query->forLocation($location);
        }
        
        if ($company_size) {
            $query->where('company_size', $company_size);
        }
        
        $salaryData = $query->orderByDesc('confidence_score')
            ->orderByDesc('sample_size')
            ->get();
            
        if ($salaryData->isEmpty()) {
            // Fallback to less strict search
            $salaryData = SalaryData::forJobTitle($jobTitle)
                ->recent()
                ->orderByDesc('confidence_score')
                ->limit(10)
                ->get();
        }
        
        if ($salaryData->isEmpty()) {
            return [
                'error' => 'No salary data found for the specified criteria',
                'suggestions' => $this->getSuggestedJobTitles($jobTitle)
            ];
        }
        
        // Calculate weighted average based on confidence scores
        $weightedSalaries = $this->calculateWeightedAverages($salaryData);
        
        // Adjust for cost of living if location specified
        if ($location) {
            $costOfLiving = CostOfLiving::getForLocation($location);
            if ($costOfLiving) {
                $weightedSalaries['cost_of_living_adjusted'] = [
                    'min' => round($weightedSalaries['min'] * ($costOfLiving->index_score / 100), 2),
                    'max' => round($weightedSalaries['max'] * ($costOfLiving->index_score / 100), 2),
                    'median' => round($weightedSalaries['median'] * ($costOfLiving->index_score / 100), 2),
                    'cost_of_living_index' => $costOfLiving->index_score
                ];
            }
        }
        
        return [
            'job_title' => $jobTitle,
            'location' => $location,
            'experience_years' => $experience,
            'salary_data' => $weightedSalaries,
            'data_points' => $salaryData->count(),
            'confidence_level' => $this->calculateConfidenceLevel($salaryData),
            'market_insights' => $this->getMarketInsights($salaryData, $experience),
            'salary_breakdown' => $this->getSalaryBreakdown($salaryData),
            'recommendations' => $this->getSalaryRecommendations($weightedSalaries, $experience)
        ];
    }
    
    /**
     * Calculate weighted averages from salary data
     */
    private function calculateWeightedAverages(Collection $salaryData): array
    {
        $totalWeight = 0;
        $weightedMin = 0;
        $weightedMax = 0;
        $weightedMedian = 0;
        $totalBonus = 0;
        
        foreach ($salaryData as $data) {
            $weight = $data->confidence_score * sqrt($data->sample_size);
            $totalWeight += $weight;
            
            $weightedMin += $data->salary_min * $weight;
            $weightedMax += $data->salary_max * $weight;
            $weightedMedian += ($data->salary_median ?? (($data->salary_min + $data->salary_max) / 2)) * $weight;
            $totalBonus += ($data->bonus_average ?? 0) * $weight;
        }
        
        if ($totalWeight == 0) {
            return [
                'min' => 0,
                'max' => 0,
                'median' => 0,
                'bonus_average' => 0
            ];
        }
        
        return [
            'min' => round($weightedMin / $totalWeight, 2),
            'max' => round($weightedMax / $totalWeight, 2),
            'median' => round($weightedMedian / $totalWeight, 2),
            'bonus_average' => round($totalBonus / $totalWeight, 2),
            'total_compensation_min' => round(($weightedMin + $totalBonus) / $totalWeight, 2),
            'total_compensation_max' => round(($weightedMax + $totalBonus) / $totalWeight, 2)
        ];
    }
    
    /**
     * Calculate confidence level based on data quality
     */
    private function calculateConfidenceLevel(Collection $salaryData): array
    {
        $avgConfidence = $salaryData->avg('confidence_score');
        $totalSamples = $salaryData->sum('sample_size');
        $dataRecency = $salaryData->where('data_collected_at', '>=', now()->subMonths(6))->count();
        
        $confidenceScore = (
            ($avgConfidence * 0.4) +
            (min(100, $totalSamples * 2) * 0.3) +
            (($dataRecency / $salaryData->count()) * 100 * 0.3)
        );
        
        $level = 'Low';
        if ($confidenceScore >= 80) $level = 'High';
        elseif ($confidenceScore >= 60) $level = 'Medium';
        
        return [
            'score' => round($confidenceScore, 1),
            'level' => $level,
            'factors' => [
                'data_quality' => round($avgConfidence, 1),
                'sample_size' => $totalSamples,
                'data_recency' => round(($dataRecency / $salaryData->count()) * 100, 1)
            ]
        ];
    }
    
    /**
     * Get market insights
     */
    private function getMarketInsights(Collection $salaryData, int $experience): array
    {
        $insights = [];
        
        // Industry analysis
        $industries = $salaryData->groupBy('industry')
            ->map(function($group) {
                return [
                    'count' => $group->count(),
                    'avg_salary' => round($group->avg('salary_median'), 2)
                ];
            })->sortByDesc('avg_salary');
        
        if ($industries->count() > 1) {
            $topIndustry = $industries->first();
            $insights[] = "Highest paying industry: " . $industries->keys()->first() . 
                         " (avg: $" . number_format($topIndustry['avg_salary']) . ")";
        }
        
        // Company size analysis
        $companySizes = $salaryData->whereNotNull('company_size')
            ->groupBy('company_size')
            ->map(function($group) {
                return round($group->avg('salary_median'), 2);
            })->sortDesc();
        
        if ($companySizes->count() > 1) {
            $topSize = $companySizes->keys()->first();
            $insights[] = "Best paying company size: $topSize " .
                         "(avg: $" . number_format($companySizes->first()) . ")";
        }
        
        // Experience growth potential
        $experienceData = SalaryData::forJobTitle($salaryData->first()->job_title)
            ->recent()
            ->get()
            ->groupBy(function($item) {
                return floor($item->years_experience_min / 5) * 5; // Group by 5-year brackets
            });
        
        if ($experienceData->count() > 1) {
            $currentBracket = floor($experience / 5) * 5;
            $nextBracket = $currentBracket + 5;
            
            $current = $experienceData->get($currentBracket);
            $next = $experienceData->get($nextBracket);
            
            if ($current && $next) {
                $growth = $next->avg('salary_median') - $current->avg('salary_median');
                if ($growth > 0) {
                    $insights[] = "Salary growth potential: $" . number_format($growth) . 
                                 " increase expected with 5+ years experience";
                }
            }
        }
        
        return $insights;
    }
    
    /**
     * Get salary breakdown by components
     */
    private function getSalaryBreakdown(Collection $salaryData): array
    {
        $hasEquity = $salaryData->whereNotNull('equity_percentage')->count();
        $hasBonuses = $salaryData->whereNotNull('bonus_average')->count();
        
        $breakdown = [
            'base_salary_percentage' => 100
        ];
        
        if ($hasBonuses > 0) {
            $avgBonus = $salaryData->whereNotNull('bonus_average')->avg('bonus_average');
            $avgSalary = $salaryData->avg('salary_median');
            
            if ($avgSalary > 0) {
                $bonusPercentage = ($avgBonus / $avgSalary) * 100;
                $breakdown['bonus_percentage'] = round($bonusPercentage, 1);
                $breakdown['base_salary_percentage'] = round(100 - $bonusPercentage, 1);
            }
        }
        
        if ($hasEquity > 0) {
            $avgEquity = $salaryData->whereNotNull('equity_percentage')->avg('equity_percentage');
            $breakdown['equity_percentage'] = round($avgEquity, 2);
        }
        
        return $breakdown;
    }
    
    /**
     * Get salary recommendations and negotiation tips
     */
    private function getSalaryRecommendations(array $salaryData, int $experience): array
    {
        $recommendations = [];
        
        $min = $salaryData['min'];
        $max = $salaryData['max'];
        $median = $salaryData['median'];
        
        // Negotiation range
        $recommendedMin = $median * 0.95;
        $recommendedMax = $median * 1.15;
        
        $recommendations['negotiation_range'] = [
            'min' => round($recommendedMin, 2),
            'max' => round($recommendedMax, 2),
            'target' => $median
        ];
        
        // Experience-based advice
        if ($experience < 2) {
            $recommendations['advice'][] = "As an entry-level candidate, focus on learning opportunities and company culture.";
            $recommendations['target_percentile'] = "25th-50th percentile of the range";
        } elseif ($experience < 5) {
            $recommendations['advice'][] = "With your experience, aim for the median salary range.";
            $recommendations['target_percentile'] = "50th-75th percentile of the range";
        } else {
            $recommendations['advice'][] = "Your experience level justifies aiming for the higher end of the range.";
            $recommendations['target_percentile'] = "75th-90th percentile of the range";
        }
        
        // Market conditions
        $recommendations['advice'][] = "Research the company's financial health and recent funding rounds.";
        $recommendations['advice'][] = "Consider total compensation including benefits, equity, and work-life balance.";
        
        return $recommendations;
    }
    
    /**
     * Get suggested job titles for failed searches
     */
    private function getSuggestedJobTitles(string $jobTitle): array
    {
        $words = explode(' ', strtolower($jobTitle));
        $suggestions = [];
        
        foreach ($words as $word) {
            if (strlen($word) > 3) {
                $matches = SalaryData::where('job_title', 'like', "%{$word}%")
                    ->distinct('job_title')
                    ->limit(5)
                    ->pluck('job_title')
                    ->toArray();
                
                $suggestions = array_merge($suggestions, $matches);
            }
        }
        
        return array_unique($suggestions);
    }
    
    /**
     * Submit salary data from users
     */
    public function submitSalaryData(int $userId, array $data): UserSalarySubmission
    {
        return UserSalarySubmission::create([
            'user_id' => $userId,
            'job_title' => $data['job_title'],
            'company_name' => $data['company_name'] ?? null,
            'location_city' => $data['location_city'],
            'location_country' => $data['location_country'],
            'years_experience' => $data['years_experience'],
            'annual_salary' => $data['annual_salary'],
            'currency' => $data['currency'] ?? 'USD',
            'bonus' => $data['bonus'] ?? null,
            'additional_compensation' => $data['additional_compensation'] ?? null,
            'skills' => $data['skills'] ?? null,
            'is_anonymous' => $data['is_anonymous'] ?? true,
            'is_verified' => false
        ]);
    }
    
    /**
     * Compare salaries across different locations
     */
    public function compareSalariesByLocation(string $jobTitle, array $locations, int $experience = 0): array
    {
        $results = [];
        
        foreach ($locations as $location) {
            $salaryData = $this->calculateSalary([
                'job_title' => $jobTitle,
                'location' => $location,
                'experience' => $experience
            ]);
            
            $results[] = [
                'location' => $location,
                'salary_data' => $salaryData['salary_data'] ?? [],
                'confidence_level' => $salaryData['confidence_level'] ?? []
            ];
        }
        
        // Sort by median salary descending
        usort($results, function($a, $b) {
            $aMedian = $a['salary_data']['median'] ?? 0;
            $bMedian = $b['salary_data']['median'] ?? 0;
            return $bMedian <=> $aMedian;
        });
        
        return [
            'job_title' => $jobTitle,
            'experience_years' => $experience,
            'location_comparison' => $results,
            'analysis' => $this->analyzeLocationComparison($results)
        ];
    }
    
    /**
     * Analyze location comparison results
     */
    private function analyzeLocationComparison(array $results): array
    {
        if (count($results) < 2) return [];
        
        $highest = $results[0];
        $lowest = end($results);
        
        $analysis = [];
        
        if (isset($highest['salary_data']['median']) && isset($lowest['salary_data']['median'])) {
            $difference = $highest['salary_data']['median'] - $lowest['salary_data']['median'];
            $percentageDiff = ($difference / $lowest['salary_data']['median']) * 100;
            
            $analysis[] = "Highest paying location: {$highest['location']} " .
                         "($" . number_format($highest['salary_data']['median']) . ")";
            
            $analysis[] = "Salary difference between highest and lowest: $" . 
                         number_format($difference) . " (" . number_format($percentageDiff, 1) . "% higher)";
        }
        
        return $analysis;
    }
}