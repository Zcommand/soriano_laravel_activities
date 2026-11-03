<?php

namespace App\Http\Requests;

use App\Models\Member;
use App\Models\MembershipCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-members') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }

        if (! $this->isCorporateCategory()) {
            $this->merge(['company_name' => null]);
        } elseif (is_string($this->input('company_name'))) {
            $this->merge(['company_name' => trim($this->input('company_name'))]);
        }
    }

    public function rules(): array
    {
        $member = $this->route('member');
        $uniqueEmail = Rule::unique('members', 'email');

        if ($member instanceof Member) {
            $uniqueEmail->ignore($member);
        }

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', $uniqueEmail],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'company_name' => [Rule::requiredIf($this->isCorporateCategory()), 'nullable', 'string', 'max:255'],
            'membership_category_id' => ['required', 'integer', 'exists:membership_categories,id'],
            'membership_level_id' => ['required', 'integer', 'exists:membership_levels,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'membership_category_id' => 'category',
            'membership_level_id' => 'level',
        ];
    }

    private function isCorporateCategory(): bool
    {
        $categoryId = filter_var($this->input('membership_category_id'), FILTER_VALIDATE_INT);

        return $categoryId !== false && MembershipCategory::whereKey($categoryId)->where('name', 'Corporate')->exists();
    }
}
