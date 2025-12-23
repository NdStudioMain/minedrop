<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property numeric $amount
 * @property int $activate
 * @property int $activate_limit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promocodes newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promocodes newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promocodes query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promocodes whereActivate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promocodes whereActivateLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promocodes whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promocodes whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promocodes whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promocodes whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promocodes whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Promocodes extends Model
{
    protected $fillable = [
        'name',
        'amount',
        'activate',
        'activate_limit',
    ];

    protected $table = 'promocodes';

    protected $casts = [
        'amount' => 'decimal:2',
        'activate' => 'integer',
        'activate_limit' => 'integer',
    ];
}
