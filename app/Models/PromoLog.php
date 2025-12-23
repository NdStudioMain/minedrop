<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property numeric $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoLog whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoLog whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoLog whereUserId($value)
 * @mixin \Eloquent
 */
class PromoLog extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'amount',
    ];

    protected $table = 'promoLog';

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Получить пользователя, который активировал промокод
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
