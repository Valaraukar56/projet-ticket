<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CasinoData extends Model
{
    protected $table = 'casino_data';

    protected $fillable = [
        'user_id',
        'money',
        'max_money',
        'machines',
        'last_collected_at',
    ];

    protected $casts = [
        'money' => 'integer',
        'max_money' => 'integer',
        'machines' => 'array',
        'last_collected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
