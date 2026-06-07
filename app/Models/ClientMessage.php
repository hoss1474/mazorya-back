<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientMessage extends Model
{
    protected $table = 'client_messages';

    protected $fillable = [
        'client_id',
        'sender',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
