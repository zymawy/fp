<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Anyone can make a payment (no auth required)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Donation details
            'user_id' => 'sometimes|string|exists:users,id',
            'cause_id' => 'required|string|exists:causes,id',
            'amount' => 'numeric|min:1',
            'invoiceValue' => 'numeric|min:1',
            'total_amount' => 'sometimes|numeric|min:1',
            'is_anonymous' => 'sometimes|boolean',
            'cover_fees' => 'sometimes|boolean',
            'currency_code' => 'sometimes|string|size:3',
            'gift_message' => 'sometimes|nullable|string|max:500',
            'is_gift' => 'sometimes|boolean',
            'recipient_email' => 'sometimes|nullable|email|max:255',
            'recipient_name' => 'sometimes|nullable|string|max:255',

            // Payment details
            'payment_method' => 'sometimes|string|in:card,paypal,bank_transfer',
            'payment_provider' => 'sometimes|string|in:stripe,paypal,manual',
            'payment_method_id' => 'sometimes|string',
            'payment_data' => 'sometimes|array',
            'payment_data.card_number' => 'sometimes|string',
            'payment_data.card_expiry' => 'sometimes|string',
            'payment_data.card_cvc' => 'sometimes|string',
            'payment_data.billing_address' => 'sometimes|array',
            'payment_data.token' => 'sometimes|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cause_id' => 'cause',
            'user_id' => 'user',
            'payment_method_id' => 'payment method',
            'payment_data.card_number' => 'card number',
            'payment_data.card_expiry' => 'card expiry date',
            'payment_data.card_cvc' => 'security code',
        ];
    }
}
