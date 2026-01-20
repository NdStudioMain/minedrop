<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCryptoPayInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'in:TON,USDT,BTC,ETH,LTC,BNB,TRX,USDC'],
            'amount_rub' => ['required', 'numeric', 'min:100', 'max:1000000'],
            'crypto_amount' => ['required', 'numeric', 'min:0.00000001'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency.required' => 'Выберите валюту',
            'currency.in' => 'Недопустимая валюта',
            'amount_rub.required' => 'Укажите сумму пополнения',
            'amount_rub.numeric' => 'Сумма должна быть числом',
            'amount_rub.min' => 'Минимальная сумма пополнения 100 RUB',
            'amount_rub.max' => 'Максимальная сумма пополнения 1 000 000 RUB',
            'crypto_amount.required' => 'Укажите сумму в криптовалюте',
            'crypto_amount.numeric' => 'Сумма должна быть числом',
            'crypto_amount.min' => 'Сумма слишком мала',
        ];
    }
}
