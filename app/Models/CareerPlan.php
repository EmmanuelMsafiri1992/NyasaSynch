<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_name',
        'description',
        'plan_type',
        'current_situation',
        'target_goals',
        'milestones',
        'action_items',
        'skill_gaps',
        'education_goals',
        'financial_projections',
        'target_completion_date',
        'status',
        'progress_percentage',
        'last_reviewed_at'
    ];

    protected $casts = [
        'current_situation' => 'array',
        'target_goals' => 'array',
        'milestones' => 'array',
        'action_items' => 'array',
        'skill_gaps' => 'array',
        'education_goals' => 'array',
        'financial_projections' => 'array',
        'target_completion_date' => 'date',
        'last_reviewed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function milestones()
    {
        return $this->hasMany(CareerPlanMilestone::class);
    }
}