<?php

namespace App\Services;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\UserPresence;
use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\UserOnline;
use App\Events\UserOffline;
use App\Events\TypingStarted;
use App\Events\TypingEnded;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class MessagingService
{
    public function sendMessage(
        User $sender,
        Conversation $conversation,
        string $content,
        string $type = 'text',
        array $attachments = [],
        ?Message $replyTo = null
    ): Message {
        // Check if user can send messages to this conversation
        if (!$conversation->hasParticipant($sender)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        // Create the message
        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'content' => $content,
            'type' => $type,
            'attachments' => $attachments,
            'reply_to_id' => $replyTo?->id
        ]);

        // Broadcast the message
        broadcast(new MessageSent($message))->toOthers();

        // Send push notifications to offline users
        $this->sendPushNotifications($message);

        return $message;
    }

    public function createConversation(
        User $initiator,
        array $participantIds,
        string $type = 'private',
        ?string $title = null,
        array $metadata = []
    ): Conversation {
        if ($type === 'private' && count($participantIds) !== 1) {
            throw new \Exception('Private conversations must have exactly 2 participants');
        }

        if ($type === 'private') {
            $otherUser = User::findOrFail($participantIds[0]);
            return Conversation::createPrivateConversation($initiator, $otherUser, $metadata);
        }

        return Conversation::createGroupConversation($title, $initiator, $participantIds, $metadata);
    }

    public function markConversationAsRead(User $user, Conversation $conversation): void
    {
        $conversation->markAsRead($user);

        // Broadcast read event
        broadcast(new MessageRead($user, $conversation))->toOthers();
    }

    public function uploadAttachment(UploadedFile $file, User $user): array
    {
        $allowedTypes = ['image', 'video', 'audio', 'document'];
        $mimeType = $file->getMimeType();
        $type = $this->getFileTypeFromMime($mimeType);

        if (!in_array($type, $allowedTypes)) {
            throw new \Exception('File type not allowed');
        }

        // Check file size limits
        $maxSize = $this->getMaxFileSizeForType($type);
        if ($file->getSize() > $maxSize) {
            throw new \Exception("File too large. Maximum size for {$type} files is " . formatBytes($maxSize));
        }

        // Generate unique filename
        $filename = time() . '_' . $user->id . '_' . $file->getClientOriginalName();
        $path = "messaging/{$type}s/{$filename}";

        // Store the file
        $storedPath = Storage::disk('public')->putFileAs(
            "messaging/{$type}s",
            $file,
            $filename
        );

        return [
            'type' => $type,
            'filename' => $file->getClientOriginalName(),
            'path' => $storedPath,
            'url' => Storage::disk('public')->url($storedPath),
            'size' => $file->getSize(),
            'mime_type' => $mimeType
        ];
    }

    public function deleteMessage(Message $message, User $user): void
    {
        if (!$message->canBeDeletedBy($user)) {
            throw new \Exception('You do not have permission to delete this message');
        }

        $message->softDelete();

        // Broadcast message deletion
        broadcast(new \App\Events\MessageDeleted($message))->toOthers();
    }

    public function editMessage(Message $message, User $user, string $newContent): void
    {
        if (!$message->canBeEditedBy($user)) {
            throw new \Exception('You cannot edit this message');
        }

        $message->edit($newContent);

        // Broadcast message edit
        broadcast(new \App\Events\MessageEdited($message))->toOthers();
    }

    public function addReaction(Message $message, User $user, string $emoji): void
    {
        $message->addReaction($user, $emoji);

        // Broadcast reaction
        broadcast(new \App\Events\ReactionAdded($message, $user, $emoji))->toOthers();
    }

    public function removeReaction(Message $message, User $user, string $emoji): void
    {
        $message->removeReaction($user, $emoji);

        // Broadcast reaction removal
        broadcast(new \App\Events\ReactionRemoved($message, $user, $emoji))->toOthers();
    }

    public function setUserOnline(User $user, string $socketId = null, array $deviceInfo = []): void
    {
        UserPresence::updateUserStatus($user, 'online', $socketId, $deviceInfo);

        // Broadcast user online status
        broadcast(new UserOnline($user))->toOthers();

        // Clear any typing indicators for this user
        $this->clearTypingIndicators($user);
    }

    public function setUserOffline(User $user): void
    {
        $presence = UserPresence::where('user_id', $user->id)->first();
        if ($presence) {
            $presence->setOffline();

            // Broadcast user offline status
            broadcast(new UserOffline($user))->toOthers();

            // Clear any typing indicators
            $this->clearTypingIndicators($user);
        }
    }

    public function setUserOfflineBySocketId(string $socketId): void
    {
        $presence = UserPresence::where('socket_id', $socketId)->first();
        if ($presence) {
            $user = $presence->user;
            $presence->setOffline();

            broadcast(new UserOffline($user))->toOthers();
            $this->clearTypingIndicators($user);
        }
    }

    public function startTyping(User $user, Conversation $conversation): void
    {
        $cacheKey = "typing_{$conversation->id}_{$user->id}";
        Cache::put($cacheKey, true, 30); // 30 seconds timeout

        broadcast(new TypingStarted($user, $conversation))->toOthers();
    }

    public function stopTyping(User $user, Conversation $conversation): void
    {
        $cacheKey = "typing_{$conversation->id}_{$user->id}";
        Cache::forget($cacheKey);

        broadcast(new TypingEnded($user, $conversation))->toOthers();
    }

    public function getTypingUsers(Conversation $conversation): array
    {
        $typingUsers = [];
        $participantIds = $conversation->getParticipantIds();

        foreach ($participantIds as $userId) {
            $cacheKey = "typing_{$conversation->id}_{$userId}";
            if (Cache::has($cacheKey)) {
                $typingUsers[] = User::find($userId);
            }
        }

        return array_filter($typingUsers);
    }

    public function getUnreadConversationsCount(User $user): int
    {
        return $user->conversations()
            ->withUnread($user)
            ->count();
    }

    public function getConversationsForUser(User $user, int $limit = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Conversation::forUser($user)
            ->with([
                'participants' => function ($query) {
                    $query->whereNull('conversation_participants.left_at');
                },
                'latestMessage.sender'
            ])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->unreadBy($user);
            }])
            ->orderBy('last_activity_at', 'desc')
            ->paginate($limit);
    }

    public function getConversationMessages(
        Conversation $conversation,
        User $user,
        int $limit = 50,
        ?int $beforeMessageId = null
    ): \Illuminate\Database\Eloquent\Collection {
        if (!$conversation->hasParticipant($user)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        $query = $conversation->messages()
            ->with(['sender', 'replyTo.sender', 'reactions.user'])
            ->notDeleted()
            ->latest();

        if ($beforeMessageId) {
            $query->where('id', '<', $beforeMessageId);
        }

        return $query->limit($limit)->get()->reverse()->values();
    }

    public function searchMessages(User $user, string $query, ?Conversation $conversation = null): \Illuminate\Database\Eloquent\Collection
    {
        $messageQuery = Message::whereHas('conversation.participants', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                  ->whereNull('conversation_participants.left_at');
            })
            ->where('content', 'like', "%{$query}%")
            ->notDeleted()
            ->with(['conversation', 'sender']);

        if ($conversation) {
            $messageQuery->where('conversation_id', $conversation->id);
        }

        return $messageQuery->latest()->limit(100)->get();
    }

    public function getOnlineUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return UserPresence::getOnlineUsers();
    }

    public function archiveConversation(Conversation $conversation, User $user): void
    {
        if (!$conversation->hasParticipant($user)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        $conversation->participants()->updateExistingPivot($user->id, [
            'settings' => array_merge(
                $conversation->participants()->where('users.id', $user->id)->first()->pivot->settings ?? [],
                ['archived' => true]
            )
        ]);
    }

    public function leaveConversation(Conversation $conversation, User $user): void
    {
        if (!$conversation->hasParticipant($user)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        if ($conversation->type === 'private') {
            throw new \Exception('Cannot leave a private conversation');
        }

        $conversation->removeParticipant($user);

        // Add system message
        Message::createSystemMessage($conversation, "{$user->name} left the conversation");
    }

    private function sendPushNotifications(Message $message): void
    {
        $participants = $message->conversation->participants()
            ->whereNull('conversation_participants.left_at')
            ->where('users.id', '!=', $message->sender_id)
            ->get();

        foreach ($participants as $participant) {
            $presence = UserPresence::where('user_id', $participant->id)->first();

            // Only send push notification if user is offline or away
            if (!$presence || !$presence->isOnline()) {
                // Queue push notification job
                dispatch(new \App\Jobs\SendPushNotification(
                    $participant,
                    "New message from {$message->sender->name}",
                    $message->getPreview(50),
                    ['conversation_id' => $message->conversation_id]
                ));
            }
        }
    }

    private function clearTypingIndicators(User $user): void
    {
        $conversations = $user->conversations()->pluck('conversations.id');

        foreach ($conversations as $conversationId) {
            $cacheKey = "typing_{$conversationId}_{$user->id}";
            Cache::forget($cacheKey);
        }
    }

    private function getFileTypeFromMime(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    private function getMaxFileSizeForType(string $type): int
    {
        return match($type) {
            'image' => 10 * 1024 * 1024, // 10MB
            'video' => 100 * 1024 * 1024, // 100MB
            'audio' => 50 * 1024 * 1024, // 50MB
            'document' => 25 * 1024 * 1024, // 25MB
            default => 5 * 1024 * 1024 // 5MB
        };
    }
}