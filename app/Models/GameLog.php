<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class GameLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'game_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'ticket_type',
        'ticket_name',
        'amount',
        'balance_before',
        'balance_after',
        'result',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
