<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-members') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        $record = $this->route('level');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('membership_levels', 'name')->ignore($record)],
            'fee' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'validity_days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'benefits' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
