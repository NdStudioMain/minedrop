<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'payment_system_id', 'amount', 'status', 'payment_url', 'payment_id', 'payment_data'];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => 'string',
        'payment_data' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentSystem()
    {
        return $this->belongsTo(PaymentSystem::class);
    }
}
