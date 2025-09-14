<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'assessment_type',
        'questions',
        'scoring_algorithm',
        'result_categories',
        'estimated_duration',
        'total_questions',
        'is_active',
        'completion_count',
        'average_rating'
    ];

    protected $casts = [
        'questions' => 'array',
        'scoring_algorithm' => 'array',
        'result_categories' => 'array',
        'is_active' => 'boolean',
        'average_rating' => 'decimal:2'
    ];

    public function userResults()
    {
        return $this->hasMany(UserAssessmentResult::class, 'assessment_id');
    }
}
