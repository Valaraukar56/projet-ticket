<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\ProfanityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Récupérer les messages récents
     */
    public function index(Request $request): JsonResponse
    {
        $lastId = $request->query('last_id', 0);

        $messages = ChatMessage::with('user:id,name')
            ->when($lastId > 0, function ($query) use ($lastId) {
                $query->where('id', '>', $lastId);
            })
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'username' => $msg->user->name,
                    'message' => $msg->message,
                    'created_at' => $msg->created_at->format('H:i'),
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    /**
     * Envoyer un message
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => __('messages.unauthenticated')], 401);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $cleaned = (new ProfanityService())->clean($validated['message']);

        $message = ChatMessage::create([
            'user_id' => $user->id,
            'message' => $cleaned,
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'username' => $user->name,
                'message' => $message->message,
                'created_at' => $message->created_at->format('H:i'),
            ],
        ]);
    }
}
