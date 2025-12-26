<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinesGame extends Model
{
    protected $fillable = [
        'user_id',
        'bet',
        'mine_count',
        'mines',
        'revealed',
        'step',
        'status',
    ];

    protected $casts = [
        'bet' => 'decimal:2',
        'mines' => 'array',
        'revealed' => 'array',
        'step' => 'integer',
        'mine_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
