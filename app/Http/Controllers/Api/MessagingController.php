<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MessagingController extends Controller
{
    private MessagingService $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
        $this->middleware('auth:sanctum');
    }

    public function getConversations(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
            'type' => 'sometimes|in:private,group,support',
            'archived' => 'sometimes|boolean'
        ]);

        $user = Auth::user();
        $limit = $request->get('limit', 20);

        $conversations = $this->messagingService->getConversationsForUser($user, $limit);

        return response()->json([
            'success' => true,
            'conversations' => $conversations->items(),
            'pagination' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total()
            ]
        ]);
    }

    public function createConversation(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', Rule::in(['private', 'group'])],
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'title' => 'required_if:type,group|string|max:255',
            'metadata' => 'sometimes|array'
        ]);

        $user = Auth::user();

        try {
            $conversation = $this->messagingService->createConversation(
                $user,
                $request->participant_ids,
                $request->type,
                $request->title,
                $request->get('metadata', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Conversation created successfully',
                'conversation' => $conversation->load(['participants', 'latestMessage'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getConversation(Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $conversation->load([
            'participants' => function ($query) {
                $query->whereNull('conversation_participants.left_at');
            }
        ]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
            'unread_count' => $conversation->getUnreadCount($user)
        ]);
    }

    public function getMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
            'before_message_id' => 'sometimes|integer|exists:messages,id'
        ]);

        $user = Auth::user();
        $limit = $request->get('limit', 50);
        $beforeMessageId = $request->get('before_message_id');

        try {
            $messages = $this->messagingService->getConversationMessages(
                $conversation,
                $user,
                $limit,
                $beforeMessageId
            );

            return response()->json([
                'success' => true,
                'messages' => $messages,
                'has_more' => $messages->count() === $limit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:10000',
            'type' => 'sometimes|in:text,file,image,video,audio',
            'attachments' => 'sometimes|array',
            'reply_to_id' => 'sometimes|integer|exists:messages,id'
        ]);

        $user = Auth::user();

        try {
            $replyTo = null;
            if ($request->filled('reply_to_id')) {
                $replyTo = Message::find($request->reply_to_id);
                if ($replyTo->conversation_id !== $conversation->id) {
                    throw new \Exception('Reply message must be from the same conversation');
                }
            }

            $message = $this->messagingService->sendMessage(
                $user,
                $conversation,
                $request->content,
                $request->get('type', 'text'),
                $request->get('attachments', []),
                $replyTo
            );

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message->load(['sender', 'replyTo.sender'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function editMessage(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:10000'
        ]);

        $user = Auth::user();

        try {
            $this->messagingService->editMessage($message, $user, $request->content);

            return response()->json([
                'success' => true,
                'message' => 'Message updated successfully',
                'data' => $message->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function deleteMessage(Message $message): JsonResponse
    {
        $user = Auth::user();

        try {
            $this->messagingService->deleteMessage($message, $user);

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function addReaction(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'emoji' => 'required|string|max:10'
        ]);

        $user = Auth::user();

        try {
            $this->messagingService->addReaction($message, $user, $request->emoji);

            return response()->json([
                'success' => true,
                'message' => 'Reaction added successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function removeReaction(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'emoji' => 'required|string|max:10'
        ]);

        $user = Auth::user();

        try {
            $this->messagingService->removeReaction($message, $user, $request->emoji);

            return response()->json([
                'success' => true,
                'message' => 'Reaction removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function markAsRead(Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        try {
            $this->messagingService->markConversationAsRead($user, $conversation);

            return response()->json([
                'success' => true,
                'message' => 'Conversation marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function uploadAttachment(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:102400' // 100MB max
        ]);

        $user = Auth::user();

        try {
            $attachment = $this->messagingService->uploadAttachment($request->file('file'), $user);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'attachment' => $attachment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function searchMessages(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:3|max:100',
            'conversation_id' => 'sometimes|integer|exists:conversations,id'
        ]);

        $user = Auth::user();
        $conversation = null;

        if ($request->filled('conversation_id')) {
            $conversation = Conversation::find($request->conversation_id);
            if (!$conversation->hasParticipant($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        }

        $messages = $this->messagingService->searchMessages($user, $request->query, $conversation);

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'total' => $messages->count()
        ]);
    }

    public function startTyping(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $this->messagingService->startTyping($user, $conversation);

        return response()->json([
            'success' => true,
            'message' => 'Typing indicator started'
        ]);
    }

    public function stopTyping(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $this->messagingService->stopTyping($user, $conversation);

        return response()->json([
            'success' => true,
            'message' => 'Typing indicator stopped'
        ]);
    }

    public function getTypingUsers(Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $typingUsers = $this->messagingService->getTypingUsers($conversation);

        return response()->json([
            'success' => true,
            'typing_users' => $typingUsers
        ]);
    }

    public function archiveConversation(Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        try {
            $this->messagingService->archiveConversation($conversation, $user);

            return response()->json([
                'success' => true,
                'message' => 'Conversation archived successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function leaveConversation(Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        try {
            $this->messagingService->leaveConversation($conversation, $user);

            return response()->json([
                'success' => true,
                'message' => 'Left conversation successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getOnlineUsers(): JsonResponse
    {
        $onlineUsers = $this->messagingService->getOnlineUsers();

        return response()->json([
            'success' => true,
            'online_users' => $onlineUsers,
            'count' => $onlineUsers->count()
        ]);
    }

    public function getUnreadCount(): JsonResponse
    {
        $user = Auth::user();
        $count = $this->messagingService->getUnreadConversationsCount($user);

        return response()->json([
            'success' => true,
            'unread_count' => $count
        ]);
    }
}