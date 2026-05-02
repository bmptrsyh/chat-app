<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request)
    {
        $authId = $request->user()->id;
        $otherUser = $this->users->firstWhere('id', '!=', $authId);
        $isGroup = (bool) $this->is_group;

        return [
            'id' => $this->id,
            'name' => $isGroup ? $this->name : ($otherUser?->name ?? 'Deleted User'),
            'participant_ids' => $this->users->pluck('id'),
            'is_group' => $isGroup,
            'avatar' => $isGroup ? $this->avatar : ($otherUser?->avatar ?? null),
            'avatar_updated_by' => $this->avatar_updated_by,
            'updated_at' => $this->updated_at,
            'signature' => $this->signature,
            'auth_user' => [
                'id' => $authId,
                'name' => $request->user()->name,
                'avatar' => $request->user()->avatar,
            ],

            $this->mergeWhen(! $isGroup, [
                'other_user' => [
                    'id' => $otherUser?->id,
                    'name' => $otherUser?->name,
                    'avatar' => $otherUser?->avatar,
                    'is_online' => $otherUser?->is_online,
                    'last_seen' => $otherUser?->last_seen,
                ],
            ]),

            $this->mergeWhen($isGroup, [
                'participants' => $this->users->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                ]),
            ]),

            'last_message' => $this->when(
                $this->lastMessage,
                fn () => [
                    'message' => $this->lastMessage->type === 'text' ? $this->lastMessage->message : 'Shared a file',
                    'type' => $this->lastMessage->type,
                    'created_at' => $this->lastMessage->created_at,
                ]
            ),
        ];
    }
}
