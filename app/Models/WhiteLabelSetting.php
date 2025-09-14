<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'white_label_client_id',
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
        'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelClient::class, 'white_label_client_id');
    }

    public function getValueAttribute()
    {
        return match($this->setting_type) {
            'json' => json_decode($this->setting_value, true),
            'boolean' => (bool) $this->setting_value,
            'number' => (float) $this->setting_value,
            'integer' => (int) $this->setting_value,
            default => $this->setting_value
        };
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('setting_key', $key);
    }
}