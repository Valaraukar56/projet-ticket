<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ScratchLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'scratch_logs';

    protected $fillable = [
        'user_id',
        'ticket_id',
        'ticket_name',
        'ticket_price',
        'loss_percentage',
        'potential_gain',
        'result',
        'amount_won',
        'scratched_at',
    ];

    protected $casts = [
        'scratched_at' => 'datetime',
        'user_id' => 'integer',
        'ticket_id' => 'integer',
        'ticket_price' => 'integer',
        'loss_percentage' => 'integer',
        'potential_gain' => 'integer',
        'amount_won' => 'integer',
    ];
}
