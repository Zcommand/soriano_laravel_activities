<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-members') ?? false;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'membership_level_id' => ['required', 'integer', 'exists:membership_levels,id'],
            'type' => ['required', Rule::in(['Application', 'Renewal'])],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'submission_key' => ['required', 'uuid'],
        ];
    }
}
