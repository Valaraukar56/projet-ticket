<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'name',
        'price',
        'loss_percentage',
        'potential_gain',
        'user_id',
        'status',
        'result',
        'scratched_percentage',
    ];

    protected $casts = [
        'price' => 'integer',
        'loss_percentage' => 'integer',
        'potential_gain' => 'integer',
        'scratched_percentage' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRevealed(): bool
    {
        return $this->scratched_percentage >= 75;
    }

    public function calculateResult(): string
    {
        $random = rand(1, 100);
        return $random <= $this->loss_percentage ? 'lose' : 'win';
    }
}
