<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property numeric $amount
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payments query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payments whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payments whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payments whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payments whereUserId($value)
 * @mixin \Eloquent
 */
class Payments extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'status',
    ];

    protected $table = 'payments';

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * Получить пользователя, которому принадлежит платеж
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
