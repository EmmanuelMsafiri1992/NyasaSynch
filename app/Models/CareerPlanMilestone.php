<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerPlanMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'career_plan_id',
        'milestone_title',
        'description',
        'target_date',
        'completed_date',
        'status',
        'priority',
        'success_criteria',
        'notes',
        'sort_order'
    ];

    protected $casts = [
        'target_date' => 'date',
        'completed_date' => 'date',
        'success_criteria' => 'array'
    ];

    public function plan()
    {
        return $this->belongsTo(CareerPlan::class, 'career_plan_id');
    }
}