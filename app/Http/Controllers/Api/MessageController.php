<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, $conversation)
    {
        $messages = Message::where('conversation_id', $conversation)
            ->with('sender')
            ->orderBy('created_at','desc')
            ->paginate(10);

        if(count($messages) == 0) {
            return $this->error('Messages not found', null, 404);
        }

        return $this->success('Messages retrieved successfully', $messages);
    }

    public function store(Request $request, $conversation)
    {
        $request->validate([
            'message' => 'required',
            'type' => 'required',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation,
            'sender_id' => $request->user()->id,
            'message' => $request->message,
            'type' => $request->type,
        ]);

        return $this->success('Message sent successfully', $message);
    }
}
