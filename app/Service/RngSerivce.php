<?php

namespace App\Service;

class RngSerivce
{
    /**
     * Генерация float от 0 до 1
     */
    public function randomFloat(): float
    {
        return mt_rand() / mt_getrandmax();
    }

    /**
     * Генерация float в диапазоне
     */
    public function randomFloatBetween(float $min, float $max): float
    {
        return $min + $this->randomFloat() * ($max - $min);
    }

    /**
     * Генерация multiplier (например для crash / x2 / mines)
     * Генерирует множители с шагом 0.1 (один знак после запятой)
     */
    public function generateMultiplier(
        float $min = 1.0,
        float $max = 100.0,
        float $bias = 1.0
    ): float {
        /**
         * bias > 1  → чаще маленькие множители
         * bias = 1  → равномерно
         * bias < 1  → чаще большие множители
         */
        $r = pow($this->randomFloat(), $bias);

        $value = $min + $r * ($max - $min);

        // Округляем до одного знака после запятой (шаг 0.1)
        return round($value, 1);
    }
}
