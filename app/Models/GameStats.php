<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class GameStats extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'game_stats';

    protected $fillable = [
        'date',
        'total_tickets_sold',
        'total_revenue',
        'total_payouts',
        'profit',
        'winners_count',
        'losers_count',
    ];

    protected $casts = [
        'date' => 'date',
        'total_tickets_sold' => 'integer',
        'total_revenue' => 'integer',
        'total_payouts' => 'integer',
        'profit' => 'integer',
        'winners_count' => 'integer',
        'losers_count' => 'integer',
    ];
}
