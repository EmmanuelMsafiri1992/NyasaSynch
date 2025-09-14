<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UserPresence extends Model
{
    protected $table = 'user_presence';

    protected $fillable = [
        'user_id',
        'status',
        'socket_id',
        'last_seen_at',
        'device_info'
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'device_info' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setOnline(string $socketId = null, array $deviceInfo = []): void
    {
        $this->update([
            'status' => 'online',
            'socket_id' => $socketId,
            'last_seen_at' => now(),
            'device_info' => $deviceInfo
        ]);
    }

    public function setOffline(): void
    {
        $this->update([
            'status' => 'offline',
            'socket_id' => null,
            'last_seen_at' => now()
        ]);
    }

    public function setAway(): void
    {
        $this->update([
            'status' => 'away',
            'last_seen_at' => now()
        ]);
    }

    public function setBusy(): void
    {
        $this->update([
            'status' => 'busy',
            'last_seen_at' => now()
        ]);
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['online', 'away', 'busy']);
    }

    public function getLastSeenForHumans(): string
    {
        if (!$this->last_seen_at) {
            return 'Never';
        }

        if ($this->isOnline()) {
            return 'Online now';
        }

        return $this->last_seen_at->diffForHumans();
    }

    // Scopes
    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline(Builder $query): Builder
    {
        return $query->where('status', 'offline');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['online', 'away', 'busy']);
    }

    public function scopeBySocketId(Builder $query, string $socketId): Builder
    {
        return $query->where('socket_id', $socketId);
    }

    public function scopeRecentlyActive(Builder $query, int $minutes = 15): Builder
    {
        return $query->where('last_seen_at', '>=', now()->subMinutes($minutes));
    }

    // Static methods
    public static function updateUserStatus(User $user, string $status, string $socketId = null, array $deviceInfo = []): self
    {
        return self::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => $status,
                'socket_id' => $socketId,
                'last_seen_at' => now(),
                'device_info' => $deviceInfo
            ]
        );
    }

    public static function setUserOfflineBySocketId(string $socketId): void
    {
        self::where('socket_id', $socketId)->update([
            'status' => 'offline',
            'socket_id' => null,
            'last_seen_at' => now()
        ]);
    }

    public static function getOnlineUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return self::online()
            ->with('user')
            ->get()
            ->pluck('user');
    }

    public static function getActiveUsersCount(): int
    {
        return self::active()->count();
    }
}