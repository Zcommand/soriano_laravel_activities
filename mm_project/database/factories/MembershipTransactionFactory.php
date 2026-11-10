<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<MembershipTransaction> */
class MembershipTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_key' => (string) Str::uuid(),
            'member_id' => Member::factory(), 'membership_level_id' => MembershipLevel::factory(),
            'membership_category_id' => MembershipCategory::factory(),
            'created_by' => User::factory()->administrator(), 'type' => 'Application', 'status' => 'Pending',
            'member_name' => fake()->name(), 'membership_number' => 'MEM-TEST-'.fake()->unique()->numerify('######'),
            'level_name' => 'Basic', 'category_name' => 'Regular', 'fee' => '200.00', 'validity_days' => 365,
        ];
    }
}
