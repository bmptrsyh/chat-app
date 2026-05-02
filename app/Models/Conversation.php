<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'name',
        'is_group',
        'created_by',
        'signature',
        'avatar',
        'avatar_updated_by'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_participants');
    }

    public function messages() {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
