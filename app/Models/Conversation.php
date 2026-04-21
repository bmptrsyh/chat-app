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
    ];

    public function user()
    {
        return $this->belongsToMany(User::class, 'conversation_participants');
    }

    public function messages() {
        return $this->hasMany(Message::class);
    }
}
