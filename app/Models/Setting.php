<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Получить значение настройки по ключу
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = "setting.{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Установить значение настройки
     */
    public static function setValue(string $key, mixed $value, ?string $type = null): void
    {
        $setting = static::firstOrNew(['key' => $key]);

        if ($type) {
            $setting->type = $type;
        }

        $setting->value = is_array($value) ? json_encode($value) : (string) $value;
        $setting->save();

        Cache::forget("setting.{$key}");
    }

    /**
     * Приведение типа значения
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Получить курс Stars к RUB
     */
    public static function getStarsRate(): float
    {
        return (float) static::getValue('stars_rate', 2.5);
    }
}
