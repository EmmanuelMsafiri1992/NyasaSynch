<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelUsage extends Model
{
    use HasFactory;

    protected $table = 'white_label_usage';

    protected $fillable = [
        'white_label_client_id',
        'usage_date',
        'metric_type',
        'metric_value',
        'metadata'
    ];

    protected $casts = [
        'usage_date' => 'date',
        'metric_value' => 'integer',
        'metadata' => 'array'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelClient::class, 'white_label_client_id');
    }

    public function scopeByMetric($query, string $metric)
    {
        return $query->where('metric_type', $metric);
    }

    public function scopeByDate($query, string $date)
    {
        return $query->where('usage_date', $date);
    }

    public function scopeDateRange($query, string $start, string $end)
    {
        return $query->whereBetween('usage_date', [$start, $end]);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('usage_date', '>=', now()->subDays($days));
    }
}