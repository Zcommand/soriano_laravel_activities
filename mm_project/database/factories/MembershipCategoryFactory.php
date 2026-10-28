<?php

namespace Database\Factories;

use App\Models\MembershipCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MembershipCategory> */
class MembershipCategoryFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->word().'-'.fake()->unique()->numberBetween(100, 999999)];
    }
}
