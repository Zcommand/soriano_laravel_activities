<?php

namespace Database\Factories;

use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'membership_transaction_id' => MembershipTransaction::factory(),
            'recorded_by' => User::factory()->administrator(), 'submission_key' => (string) Str::uuid(),
            'amount' => '200.00', 'method' => 'Cash', 'paid_on' => today(),
        ];
    }
}
