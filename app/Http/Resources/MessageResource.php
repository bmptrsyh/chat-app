<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'conversation_id' => (int) $this->conversation_id,
            'message' => $this->message,
            'file' => $this->file ? asset('storage/' . $this->file) : null,
            'type' => $this->type,
            'is_edited' => (bool) $this->is_edited,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'avatar' => $this->sender->avatar ? asset('storage/' . $this->sender->avatar) : null,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
