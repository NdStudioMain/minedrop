<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Games extends Model
{
    protected $fillable = ['id_game', 'description', 'image', 'url_slug', 'bank_id'];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
