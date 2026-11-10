<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-members') ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'external_reference' => ['nullable', 'string', 'max:100', 'required_unless:method,Cash'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'submission_key' => ['required', 'uuid'],
        ];
    }
}
