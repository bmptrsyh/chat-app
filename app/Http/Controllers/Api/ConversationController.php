<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $conversations = Conversation::whereHas('users', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->whereHas('messages')->with(['users', 'lastMessage'])->get();

        return $this->success('Conversations retrieved successfully', ConversationResource::collection($conversations));
    }

    public function myConversations(Request $request)
    {
        $conversations = Conversation::whereHas('users', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->pluck('id');

        $allowed_conversations = $conversations->map(fn($id)=>'chat_'.$id);

        return $this->success('My conversations retrieved successfully', ['allowed_conversations' => $allowed_conversations]);
    }

    public function show(Request $request, $id)
    {
        $conversation = Conversation::with(['users', 'lastMessage', 'messages'])->findOrFail($id);

        if (! $conversation->users->contains($request->user()->id)) {
            return $this->error('Unauthorized', null, 403);
        }

        return $this->success('Conversation retrieved successfully', ConversationResource::make($conversation));
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
            return $this->error('Conversation already exists', ConversationResource::make($existingConversation), 400);
        }

        // Create baru
        $conversation = Conversation::create([
            'name' => $request->is_group ? $request->name : null,
            'is_group' => $request->is_group,
            'created_by' => $authId,
            'signature' => $signature,
        ]);

        $conversation->users()->attach($userIds);

        // Load relasi agar data lengkap untuk frontend
        $conversation->load(['users', 'lastMessage']);

        return $this->success('Conversation created successfully', ConversationResource::make($conversation));
    }

    public function updateAvatar(Request $request, Conversation $conversation)
    {
        // Validasi field avatar
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        // validasi apakah user yang login adalah user yang terdaftar di dalam conversation
        if ($conversation->users()->where('user_id', $request->user()->id)->doesntExist()) {
            return $this->error('Unauthorized', null, 403);
        }

        if (!$conversation->is_group) {
            return $this->error('Cannot update avatar for private chat', null, 403);
        }

        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = $request->user()->id . '_' . time() . '.' . $avatar->getClientOriginalExtension();
            $avatarPath = $avatar->storeAs('avatars/group', $avatarName, 'public');

            // remove old avatar if exists
            if ($conversation->avatar) {
                Storage::delete('public/' . $conversation->avatar);
            }

            $avatarUpdateBy = $request->user()->id;
            $conversation->update([
                'avatar' => $avatarPath, 
                'avatar_updated_by' => $avatarUpdateBy,
                'updated_at' => now()
            ]);
            return $this->success('Avatar updated successfully', ConversationResource::make($conversation));
        }

        return $this->error('No avatar provided', null, 422);
    }
}
