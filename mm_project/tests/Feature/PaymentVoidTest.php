<?php

use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('preserves a voided receipt and reopens the balance without counting it as collections', function () {
    $staff = User::factory()->administrator()->create();
    $transaction = MembershipTransaction::factory()->create(['fee' => '200.00', 'status' => 'Paid']);
    $payment = Payment::factory()->create(['membership_transaction_id' => $transaction->id, 'amount' => '200.00']);
    $this->actingAs($staff)->post(route('payments.void', $payment), ['void_reason' => 'Payment entered twice'])->assertRedirect(route('payments.show', $payment));
    $payment->refresh();
    expect($payment->voided_by)->toBe($staff->id)->and($payment->voided_at)->not->toBeNull()
        ->and($payment->void_reason)->toBe('Payment entered twice')->and($payment->amount)->toBe('200.00');
    expect($transaction->fresh()->status)->toBe('Pending')->and($transaction->fresh()->balanceMinorUnits())->toBe(20000);
    $this->assertDatabaseCount('payments', 1);
    $this->get(route('payments.show', $payment))->assertOk()->assertSee('VOIDED')->assertSee('Payment entered twice')->assertSee($staff->name);
    $this->get(route('dashboard'))->assertViewHas('summary', fn (array $summary): bool => (float) $summary['collected'] === 0.0);
    $this->post(route('transactions.approve', $transaction))->assertSessionHasErrors('approval');
});

it('keeps other valid partial payments and permits a replacement payment', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $transaction = MembershipTransaction::factory()->create(['fee' => '200.00', 'status' => 'Paid']);
    Payment::factory()->create(['membership_transaction_id' => $transaction->id, 'amount' => '125.00']);
    $payment = Payment::factory()->create(['membership_transaction_id' => $transaction->id, 'amount' => '75.00']);
    $this->post(route('payments.void', $payment), ['void_reason' => 'Incorrect cash entry'])->assertSessionHasNoErrors();
    expect($transaction->fresh()->paidMinorUnits())->toBe(12500)->and($transaction->fresh()->balanceMinorUnits())->toBe(7500);
    $this->post(route('payments.store', $transaction), ['submission_key' => (string) Str::uuid(), 'amount' => '75.00', 'method' => 'Cash', 'paid_on' => today()->toDateString()])->assertSessionHasNoErrors();
    expect($transaction->fresh()->status)->toBe('Paid')->and($transaction->fresh()->balanceMinorUnits())->toBe(0);
});

it('rejects duplicate voids without overwriting the original audit', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $payment = Payment::factory()->create();
    $this->post(route('payments.void', $payment), ['void_reason' => 'Original correction'])->assertSessionHasNoErrors();
    $this->post(route('payments.void', $payment), ['void_reason' => 'Different correction'])->assertSessionHasErrors('void_reason');
    expect($payment->fresh()->void_reason)->toBe('Original correction');
});

it('protects finalized transactions from payment voiding', function (string $status) {
    $this->actingAs(User::factory()->administrator()->create());
    $transaction = MembershipTransaction::factory()->create(['status' => $status]);
    $payment = Payment::factory()->create(['membership_transaction_id' => $transaction->id]);
    $this->post(route('payments.void', $payment), ['void_reason' => 'Incorrect cash entry'])->assertSessionHasErrors('void_reason');
    expect($payment->fresh()->voided_at)->toBeNull()->and($transaction->fresh()->status)->toBe($status);
})->with(['Approved', 'Expired', 'Cancelled']);

it('requires a meaningful reason', function (mixed $reason) {
    $this->actingAs(User::factory()->administrator()->create());
    $payment = Payment::factory()->create();
    $this->post(route('payments.void', $payment), ['void_reason' => $reason])->assertSessionHasErrors('void_reason');
    expect($payment->fresh()->voided_at)->toBeNull();
})->with([null, '    ', 'oops', str_repeat('a', 1001)]);

it('protects voiding from guests and non administrators', function () {
    $payment = Payment::factory()->create();
    $this->post(route('payments.void', $payment), ['void_reason' => 'Incorrect cash entry'])->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->post(route('payments.void', $payment), ['void_reason' => 'Incorrect cash entry'])->assertForbidden();
    expect($payment->fresh()->voided_at)->toBeNull();
});

it('allows cancellation after all payments were voided while retaining receipts', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $transaction = MembershipTransaction::factory()->create(['status' => 'Paid']);
    $payment = Payment::factory()->create(['membership_transaction_id' => $transaction->id]);
    $this->post(route('payments.void', $payment), ['void_reason' => 'Incorrect cash entry'])->assertSessionHasNoErrors();
    $this->post(route('transactions.cancel', $transaction), ['cancellation_reason' => 'Application withdrawn'])->assertSessionHasNoErrors();
    expect($transaction->fresh()->status)->toBe('Cancelled');
    $this->get(route('payments.show', $payment))->assertOk()->assertSee('VOIDED');
});
