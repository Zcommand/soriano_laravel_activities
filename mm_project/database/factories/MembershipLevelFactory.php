<?php

namespace Database\Factories;

use App\Models\MembershipLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MembershipLevel> */
class MembershipLevelFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->word().'-'.fake()->unique()->numberBetween(100, 999999)];
    }
}
