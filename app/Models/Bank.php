<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $fillable = ['name', 'currency', 'default_balance', 'totalBets', 'totalWins', 'rtp', 'houseEdge', 'maxPayoutPercent', 'is_default'];

    protected $casts = [
        'default_balance' => 'decimal:2',
        'totalBets' => 'decimal:2',
        'totalWins' => 'decimal:2',
        'rtp' => 'decimal:2',
        'houseEdge' => 'decimal:2',
        'maxPayoutPercent' => 'decimal:2',
    ];

    public function games()
    {
        return $this->hasMany(Games::class);
    }
}
