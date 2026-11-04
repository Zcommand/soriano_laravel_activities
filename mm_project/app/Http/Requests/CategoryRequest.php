<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        $record = $this->route('category');

        return [
            'description' => ['nullable', 'string', 'max:2000'],
            'name' => ['required', 'string', 'max:100', Rule::unique('membership_categories', 'name')->ignore($record), Rule::when($record?->name === 'Corporate', Rule::in(['Corporate']))],
        ];
    }
}
