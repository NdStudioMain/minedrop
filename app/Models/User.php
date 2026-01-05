<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property int|null $tg_id
 * @property string|null $name
 * @property string|null $username
 * @property string|null $avatar
 * @property string|null $ref_code
 * @property string|null $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $referrer_id
 * @property numeric $balance
 * @property numeric $ref_balance
 * @property int|null $bonus_time
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payments> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PromoLog> $promoLogs
 * @property-read int|null $promo_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $referrals
 * @property-read int|null $referrals_count
 * @property-read User|null $referrer
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBonusTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRefBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRefCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereReferrerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'tg_id',
        'name',
        'username',
        'avatar',
        'ref_code',
        'email',
        'email_verified_at',
        'password',
        'referrer_id',
        'balance',
        'ref_balance',
        'bank_id',
        'bonus_time',
    ];

    protected $table = 'users';


    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'balance' => 'decimal:2',
        'ref_balance' => 'decimal:2',
        'bonus_time' => 'integer',
    ];

    /**
     * Получить реферера пользователя
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }
    /**
     * Получить всех рефералов пользователя
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    /**
     * Получить все платежи пользователя
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payments::class);
    }

    /**
     * Получить все активированные промокоды пользователя
     */
    public function promoLogs(): HasMany
    {
        return $this->hasMany(PromoLog::class);
    }

    /**
     * Accessor для bonusTime (camelCase -> snake_case)
     */
    public function getBonusTimeAttribute()
    {
        return $this->attributes['bonus_time'] ?? null;
    }

    /**
     * Mutator для bonusTime (camelCase -> snake_case)
     */
    public function setBonusTimeAttribute($value)
    {
        $this->attributes['bonus_time'] = $value;
    }
}
