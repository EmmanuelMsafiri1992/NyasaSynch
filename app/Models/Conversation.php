<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'description',
        'metadata',
        'status',
        'last_activity_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_activity_at' => 'datetime'
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'joined_at', 'left_at', 'last_read_at', 'muted', 'pinned', 'settings'])
            ->withTimestamps();
    }

    public function activeParticipants(): BelongsToMany
    {
        return $this->participants()->whereNull('conversation_participants.left_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function getTitle(): string
    {
        if ($this->title) {
            return $this->title;
        }

        if ($this->type === 'private') {
            $participants = $this->participants()
                ->whereNull('conversation_participants.left_at')
                ->get();

            if ($participants->count() === 2) {
                return $participants->pluck('name')->implode(' & ');
            }
        }

        return "Conversation #{$this->id}";
    }

    public function addParticipant(User $user, string $role = 'participant'): void
    {
        $this->participants()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'joined_at' => now(),
                'left_at' => null
            ]
        ]);

        $this->touch('last_activity_at');
    }

    public function removeParticipant(User $user): void
    {
        $this->participants()->updateExistingPivot($user->id, [
            'left_at' => now()
        ]);
    }

    public function hasParticipant(User $user): bool
    {
        return $this->activeParticipants()->where('users.id', $user->id)->exists();
    }

    public function getUnreadCount(User $user): int
    {
        $lastReadAt = $this->participants()
            ->where('users.id', $user->id)
            ->first()?->pivot?->last_read_at;

        if (!$lastReadAt) {
            return $this->messages()->count();
        }

        return $this->messages()
            ->where('created_at', '>', $lastReadAt)
            ->where('sender_id', '!=', $user->id)
            ->count();
    }

    public function markAsRead(User $user, ?Message $lastMessage = null): void
    {
        if (!$lastMessage) {
            $lastMessage = $this->messages()->latest()->first();
        }

        if ($lastMessage) {
            $this->participants()->updateExistingPivot($user->id, [
                'last_read_at' => $lastMessage->created_at
            ]);

            // Mark individual messages as read
            $this->messages()
                ->where('created_at', '<=', $lastMessage->created_at)
                ->whereDoesntHave('reads', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->each(function ($message) use ($user) {
                    $message->reads()->create(['user_id' => $user->id]);
                });
        }
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);

        // Clear conversation cache
        $this->clearCache();
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function getParticipantIds(): array
    {
        return Cache::remember(
            "conversation_{$this->id}_participants",
            300,
            fn() => $this->activeParticipants()->pluck('users.id')->toArray()
        );
    }

    public function clearCache(): void
    {
        Cache::forget("conversation_{$this->id}_participants");
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('activeParticipants', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        });
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('last_activity_at', '>=', now()->subDays($days));
    }

    public function scopeWithUnread($query, User $user)
    {
        return $query->whereHas('messages', function ($q) use ($user) {
            $q->where('sender_id', '!=', $user->id)
                ->whereDoesntHave('reads', function ($readQuery) use ($user) {
                    $readQuery->where('user_id', $user->id);
                });
        });
    }

    // Static methods
    public static function createPrivateConversation(User $user1, User $user2, array $metadata = []): self
    {
        // Check if conversation already exists
        $existingConversation = self::where('type', 'private')
            ->whereHas('activeParticipants', function ($query) use ($user1) {
                $query->where('users.id', $user1->id);
            })
            ->whereHas('activeParticipants', function ($query) use ($user2) {
                $query->where('users.id', $user2->id);
            })
            ->first();

        if ($existingConversation) {
            return $existingConversation;
        }

        $conversation = self::create([
            'type' => 'private',
            'metadata' => $metadata,
            'last_activity_at' => now()
        ]);

        $conversation->addParticipant($user1);
        $conversation->addParticipant($user2);

        return $conversation;
    }

    public static function createGroupConversation(string $title, User $creator, array $participantIds = [], array $metadata = []): self
    {
        $conversation = self::create([
            'type' => 'group',
            'title' => $title,
            'metadata' => $metadata,
            'last_activity_at' => now()
        ]);

        $conversation->addParticipant($creator, 'admin');

        foreach ($participantIds as $userId) {
            $conversation->addParticipant(User::find($userId));
        }

        return $conversation;
    }
}