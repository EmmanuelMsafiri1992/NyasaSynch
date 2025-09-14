<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WhiteLabelApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'white_label_client_id',
        'name',
        'key_id',
        'key_secret',
        'permissions',
        'rate_limits',
        'requests_made',
        'last_used_at',
        'is_active',
        'expires_at'
    ];

    protected $casts = [
        'permissions' => 'array',
        'rate_limits' => 'array',
        'requests_made' => 'integer',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
        'expires_at' => 'datetime'
    ];

    protected $hidden = [
        'key_secret'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelClient::class, 'white_label_client_id');
    }

    public static function generateKeyPair(): array
    {
        return [
            'key_id' => 'wl_' . Str::random(16),
            'key_secret' => hash('sha256', Str::random(64))
        ];
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isRateLimited(): bool
    {
        if (!$this->rate_limits) {
            return false;
        }

        $limits = $this->rate_limits;
        $hourlyLimit = $limits['hourly'] ?? null;
        $dailyLimit = $limits['daily'] ?? null;

        if ($hourlyLimit) {
            $hourlyRequests = static::where('id', $this->id)
                ->where('last_used_at', '>=', now()->subHour())
                ->sum('requests_made');

            if ($hourlyRequests >= $hourlyLimit) {
                return true;
            }
        }

        if ($dailyLimit) {
            $dailyRequests = static::where('id', $this->id)
                ->where('last_used_at', '>=', now()->subDay())
                ->sum('requests_made');

            if ($dailyRequests >= $dailyLimit) {
                return true;
            }
        }

        return false;
    }

    public function recordUsage(): void
    {
        $this->increment('requests_made');
        $this->update(['last_used_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}