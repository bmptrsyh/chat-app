<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $conversations = Conversation::whereHas('user', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->with('user')->get();

        return $this->success('Conversations retrieved successfully', $conversations);
    }

    public function show(Request $request, $id)
    {
        $conversation = Conversation::with('user', 'messages')->findOrFail($id);

        if (! $conversation->user->contains($request->user()->id)) {
            return $this->error('Unauthorized', null, 403);
        }

        return $this->success('Conversation retrieved successfully', $conversation);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'is_group' => 'required|boolean',
            'name' => 'required_if:is_group,true',
        ]);

        $authId = $request->user()->id;
        $userIds = $request->user_ids;

        // Pastikan auth user ikut
        if (! in_array($authId, $userIds)) {
            $userIds[] = $authId;
        }

        // Kalau private → harus tepat 2 user
        if (! $request->is_group && count($userIds) != 2) {
            return response()->json([
                'message' => 'Private chat must have exactly 2 users',
            ], 422);
        }

        // Sort biar konsisten
        sort($userIds);

        // Buat signature
        $signature = implode('-', $userIds);

        // Cek existing
        $existingConversation = Conversation::where('signature', $signature)->first();

        if ($existingConversation) {
            return $this->error('Conversation already exists', $existingConversation);
        }

        // Create baru
        $conversation = Conversation::create([
            'name' => $request->is_group ? $request->name : null,
            'is_group' => $request->is_group,
            'created_by' => $authId,
            'signature' => $signature,
        ]);

        $conversation->user()->attach($userIds);

        return $this->success('Conversation created successfully', $conversation);
    }
}
