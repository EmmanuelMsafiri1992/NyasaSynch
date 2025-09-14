<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAssessmentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assessment_id',
        'answers',
        'scores',
        'primary_result',
        'secondary_results',
        'recommended_careers',
        'skill_strengths',
        'development_areas',
        'detailed_analysis',
        'completion_time_minutes',
        'user_rating',
        'user_feedback',
        'is_public'
    ];

    protected $casts = [
        'answers' => 'array',
        'scores' => 'array',
        'primary_result' => 'array',
        'secondary_results' => 'array',
        'recommended_careers' => 'array',
        'skill_strengths' => 'array',
        'development_areas' => 'array',
        'is_public' => 'boolean',
        'user_rating' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assessment()
    {
        return $this->belongsTo(CareerAssessment::class, 'assessment_id');
    }
}