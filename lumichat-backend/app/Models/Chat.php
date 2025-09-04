<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chat_session_id',
        'sender',
        'message',
        'sent_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
