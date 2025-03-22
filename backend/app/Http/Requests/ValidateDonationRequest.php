<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateDonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow all requests temporarily
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|uuid|exists:users,id',
            'cause_id' => 'required|uuid|exists:causes,id',
            'amount' => 'required|numeric|min:0.01',
            'is_anonymous' => 'sometimes|boolean',
            'total_amount' => 'sometimes|numeric|min:0.01',
            'cover_fees' => 'sometimes|boolean',
            'currency_code' => 'sometimes|string|size:3',
            'gift_message' => 'sometimes|string|max:500',
            'is_gift' => 'sometimes|boolean',
            'payment_method_id' => 'sometimes|string',
            'payment_status' => 'sometimes|string',
            'processing_fee' => 'sometimes|numeric|min:0',
            'recipient_email' => 'sometimes|email|max:255',
            'recipient_name' => 'sometimes|string|max:255',
            'payment_id' => 'sometimes|string',
        ];
    }
}
