<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, $conversation)
    {
        $messages = Message::where('conversation_id', $conversation)
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->success('Messages retrieved successfully', MessageResource::collection($messages));
    }

    public function store(Request $request, $conversation)
    {
        // Validasi: Harus ada salah satu (message atau file)
        $request->validate([
            'message' => 'required_without:file|nullable|string',
            'file' => 'required_without:message|nullable|file|max:10240', // Max 10MB
        ]);

        // Tentukan tipe pesan
        $type = $request->hasFile('file') ? 'file' : 'text';

        // Handle file upload
        $fileName = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            // Penamaan file: user_id + timestamp + original name
            $fileName = $request->user()->id . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('messages', $fileName, 'public');
        }

        $message = Message::create([
            'conversation_id' => $conversation,
            'sender_id' => $request->user()->id,
            'message' => $request->message,
            'file' => $fileName,
            'type' => $type,
        ]);

        return $this->success('Message sent successfully', MessageResource::make($message));
    }
}
