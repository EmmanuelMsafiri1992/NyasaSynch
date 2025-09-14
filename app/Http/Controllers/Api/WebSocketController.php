<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MessagingService;
use App\Models\User;
use App\Models\UserPresence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WebSocketController extends Controller
{
    private MessagingService $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    public function connect(Request $request)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $socketId = $request->input('socket_id');
            $deviceInfo = [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'platform' => $this->getPlatform($request->userAgent()),
                'browser' => $this->getBrowser($request->userAgent())
            ];

            // Set user as online
            $this->messagingService->setUserOnline($user, $socketId, $deviceInfo);

            return response()->json([
                'success' => true,
                'message' => 'Connected successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar_url ?? null
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('WebSocket connection error: ' . $e->getMessage());
            return response()->json(['error' => 'Connection failed'], 500);
        }
    }

    public function disconnect(Request $request)
    {
        try {
            $socketId = $request->input('socket_id');

            if ($socketId) {
                $this->messagingService->setUserOfflineBySocketId($socketId);
            } else {
                $user = Auth::guard('sanctum')->user();
                if ($user) {
                    $this->messagingService->setUserOffline($user);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Disconnected successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('WebSocket disconnection error: ' . $e->getMessage());
            return response()->json(['error' => 'Disconnection failed'], 500);
        }
    }

    public function heartbeat(Request $request)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Update last seen
            $presence = UserPresence::where('user_id', $user->id)->first();
            if ($presence) {
                $presence->update(['last_seen_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('WebSocket heartbeat error: ' . $e->getMessage());
            return response()->json(['error' => 'Heartbeat failed'], 500);
        }
    }

    public function setStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:online,away,busy,offline'
        ]);

        try {
            $user = Auth::guard('sanctum')->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $presence = UserPresence::where('user_id', $user->id)->first();
            if ($presence) {
                $presence->update(['status' => $request->status, 'last_seen_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'status' => $request->status
            ]);
        } catch (\Exception $e) {
            Log::error('WebSocket status update error: ' . $e->getMessage());
            return response()->json(['error' => 'Status update failed'], 500);
        }
    }

    private function getPlatform(string $userAgent): string
    {
        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows';
        } elseif (strpos($userAgent, 'Macintosh') !== false) {
            return 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false) {
            return 'iOS';
        }

        return 'Unknown';
    }

    private function getBrowser(string $userAgent): string
    {
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'Opera') !== false) {
            return 'Opera';
        }

        return 'Unknown';
    }
}