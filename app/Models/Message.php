<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'attachments',
        'metadata',
        'is_edited',
        'edited_at',
        'is_deleted',
        'deleted_at',
        'reply_to_id'
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $appends = ['formatted_content', 'read_count', 'reaction_counts'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    public function deliveryStatus(): HasMany
    {
        return $this->hasMany(MessageDeliveryStatus::class);
    }

    public function getFormattedContentAttribute(): string
    {
        if ($this->is_deleted) {
            return '<em>This message was deleted</em>';
        }

        $content = $this->content;

        // Format mentions
        $content = preg_replace_callback('/@(\w+)/', function ($matches) {
            $username = $matches[1];
            $user = User::where('username', $username)->first();
            if ($user) {
                return "<span class=\"mention\" data-user-id=\"{$user->id}\">@{$username}</span>";
            }
            return $matches[0];
        }, $content);

        // Format URLs
        $content = preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<a href="$1" target="_blank" rel="noopener">$1</a>',
            $content
        );

        // Format basic markdown
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
        $content = preg_replace('/`(.*?)`/', '<code>$1</code>', $content);

        return $content;
    }

    public function getReadCountAttribute(): int
    {
        return $this->reads()->count();
    }

    public function getReactionCountsAttribute(): array
    {
        return $this->reactions()
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->pluck('count', 'emoji')
            ->toArray();
    }

    public function addReaction(User $user, string $emoji): MessageReaction
    {
        return $this->reactions()->updateOrCreate(
            ['user_id' => $user->id, 'emoji' => $emoji]
        );
    }

    public function removeReaction(User $user, string $emoji): bool
    {
        return $this->reactions()
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->delete() > 0;
    }

    public function hasReaction(User $user, string $emoji): bool
    {
        return $this->reactions()
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->exists();
    }

    public function markAsRead(User $user): void
    {
        $this->reads()->firstOrCreate(['user_id' => $user->id]);
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function edit(string $newContent): void
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now()
        ]);
    }

    public function softDelete(): void
    {
        $this->update([
            'is_deleted' => true,
            'deleted_at' => now()
        ]);
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->sender_id === $user->id &&
               $this->created_at->diffInMinutes() <= 15 &&
               !$this->is_deleted;
    }

    public function canBeDeletedBy(User $user): bool
    {
        // Sender can delete within 24 hours, admins can always delete
        return ($this->sender_id === $user->id && $this->created_at->diffInHours() <= 24) ||
               $this->conversation->participants()
                   ->where('users.id', $user->id)
                   ->wherePivotIn('role', ['admin', 'moderator'])
                   ->exists();
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    public function getAttachmentsByType(string $type): array
    {
        return collect($this->attachments ?? [])
            ->filter(fn($attachment) => $attachment['type'] === $type)
            ->values()
            ->toArray();
    }

    public function getPreview(int $length = 100): string
    {
        if ($this->is_deleted) {
            return 'Message deleted';
        }

        if ($this->type !== 'text') {
            return match($this->type) {
                'image' => '📷 Image',
                'video' => '🎥 Video',
                'audio' => '🎵 Audio',
                'file' => '📎 File',
                'system' => '⚙️ System message',
                default => 'Message'
            };
        }

        return Str::limit(strip_tags($this->content), $length);
    }

    public function createDeliveryStatus(User $user, string $status = 'sent'): void
    {
        $this->deliveryStatus()->updateOrCreate(
            ['user_id' => $user->id],
            ['status' => $status, 'delivered_at' => $status === 'delivered' ? now() : null]
        );
    }

    public function getDeliveryStatusFor(User $user): ?string
    {
        return $this->deliveryStatus()
            ->where('user_id', $user->id)
            ->value('status');
    }

    // Scopes
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->where('is_deleted', false);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeWithAttachments(Builder $query): Builder
    {
        return $query->whereNotNull('attachments');
    }

    public function scopeReplies(Builder $query): Builder
    {
        return $query->whereNotNull('reply_to_id');
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('reply_to_id');
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeUnreadBy(Builder $query, User $user): Builder
    {
        return $query->whereDoesntHave('reads', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function scopeFromUser(Builder $query, User $user): Builder
    {
        return $query->where('sender_id', $user->id);
    }

    // Static methods
    public static function createSystemMessage(Conversation $conversation, string $content, array $metadata = []): self
    {
        return self::create([
            'conversation_id' => $conversation->id,
            'sender_id' => 1, // System user ID
            'content' => $content,
            'type' => 'system',
            'metadata' => $metadata
        ]);
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            // Update conversation activity
            $message->conversation->updateActivity();

            // Create delivery status for all participants
            $participants = $message->conversation->getParticipantIds();
            foreach ($participants as $userId) {
                if ($userId != $message->sender_id) {
                    $message->createDeliveryStatus(User::find($userId));
                }
            }
        });

        static::updated(function ($message) {
            if ($message->wasChanged('content')) {
                $message->conversation->updateActivity();
            }
        });
    }
}