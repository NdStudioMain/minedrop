<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $fillable = ['name', 'currency', 'default_balance', 'totalBets', 'totalWins', 'rtp', 'houseEdge', 'maxPayoutPercent'];

    public function games()
    {
        return $this->hasMany(Games::class);
    }
}
