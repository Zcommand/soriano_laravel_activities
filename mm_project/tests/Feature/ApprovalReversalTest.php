<?php

use App\Models\Member;
use App\Models\MembershipApprovalReversal;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reverses approval preserves its audit and allows payment voiding', function () {
    $this->freezeSecond();
    $staff = User::factory()->administrator()->create();
    $oldLevel = MembershipLevel::factory()->create();
    $member = Member::factory()->create(['membership_level_id' => $oldLevel->id]);
    $transaction = MembershipTransaction::factory()->create(['member_id' => $member->id, 'status' => 'Paid']);
    $payment = Payment::factory()->create(['membership_transaction_id' => $transaction->id]);
    $this->actingAs($staff)->post(route('transactions.approve', $transaction))->assertSessionHasNoErrors();
    $approved = $transaction->fresh();
    $this->post(route('transactions.reverse-approval', $transaction), ['reversal_reason' => 'Approval entered by mistake'])->assertRedirect(route('transactions.show', $transaction));
    $transaction->refresh();
    expect($transaction->status)->toBe('Paid')->and($transaction->starts_at)->toBeNull()->and($transaction->expires_at)->toBeNull()
        ->and($transaction->approved_at)->toBeNull()->and($member->fresh()->currentMembership)->toBeNull()
        ->and($member->fresh()->membership_level_id)->toBe($oldLevel->id);
    $audit = MembershipApprovalReversal::sole();
    expect($audit->reversed_by)->toBe($staff->id)->and($audit->approved_by)->toBe($staff->id)
        ->and($audit->approved_at->equalTo($approved->approved_at))->toBeTrue()
        ->and($audit->expires_at->equalTo($approved->expires_at))->toBeTrue();
    $this->get(route('transactions.show', $transaction))->assertOk()->assertSee('Approval reversal history')->assertSee('Approval entered by mistake');
    $this->post(route('payments.void', $payment), ['void_reason' => 'Mistaken payment record'])->assertSessionHasNoErrors();
    expect($payment->fresh()->voided_at)->not->toBeNull()->and($transaction->fresh()->status)->toBe('Pending');
});

it('restores a prior membership when reversing a future renewal', function () {
    $staff = User::factory()->administrator()->create();
    $member = Member::factory()->create();
    $prior = MembershipTransaction::factory()->create(['member_id' => $member->id, 'status' => 'Approved', 'starts_at' => now()->subDays(10), 'expires_at' => now()->addDays(355)]);
    $renewal = MembershipTransaction::factory()->create(['member_id' => $member->id, 'type' => 'Renewal', 'status' => 'Approved', 'starts_at' => $prior->expires_at, 'expires_at' => $prior->expires_at->addDays(365)]);
    Payment::factory()->create(['membership_transaction_id' => $renewal->id]);
    $this->actingAs($staff)->post(route('transactions.reverse-approval', $renewal), ['reversal_reason' => 'Wrong renewal selected'])->assertSessionHasNoErrors();
    expect($member->fresh()->currentMembership->id)->toBe($prior->id)->and($member->fresh()->membership_level_id)->toBe($prior->membership_level_id);
});

it('blocks reversing an approval supporting a later renewal', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $member = Member::factory()->create();
    $prior = MembershipTransaction::factory()->create(['member_id' => $member->id, 'status' => 'Approved', 'expires_at' => now()->addYear()]);
    MembershipTransaction::factory()->create(['member_id' => $member->id, 'type' => 'Renewal', 'status' => 'Approved', 'expires_at' => now()->addYears(2)]);
    $this->post(route('transactions.reverse-approval', $prior), ['reversal_reason' => 'Mistaken membership approval'])->assertSessionHasErrors('reversal_reason');
    expect($prior->fresh()->status)->toBe('Approved');
    $this->assertDatabaseCount('membership_approval_reversals', 0);
});

it('blocks reversal while another transaction is open', function (string $status) {
    $this->actingAs(User::factory()->administrator()->create());
    $member = Member::factory()->create();
    $approved = MembershipTransaction::factory()->create(['member_id' => $member->id, 'status' => 'Approved', 'expires_at' => now()->addYear()]);
    MembershipTransaction::factory()->create(['member_id' => $member->id, 'type' => 'Renewal', 'status' => $status]);
    $this->post(route('transactions.reverse-approval', $approved), ['reversal_reason' => 'Mistaken membership approval'])->assertSessionHasErrors('reversal_reason');
    expect($approved->fresh()->status)->toBe('Approved');
})->with(['Pending', 'Paid']);

it('preserves multiple reversal events across reapproval', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $transaction = MembershipTransaction::factory()->create(['status' => 'Paid']);
    Payment::factory()->create(['membership_transaction_id' => $transaction->id]);
    foreach (['First approval correction', 'Second approval correction'] as $reason) {
        $this->post(route('transactions.approve', $transaction))->assertSessionHasNoErrors();
        $this->post(route('transactions.reverse-approval', $transaction), ['reversal_reason' => $reason])->assertSessionHasNoErrors();
    }
    expect($transaction->approvalReversals()->count())->toBe(2);
    $this->post(route('transactions.reverse-approval', $transaction), ['reversal_reason' => 'Duplicate request'])->assertSessionHasErrors('reversal_reason');
    expect($transaction->approvalReversals()->count())->toBe(2);
});

it('allows expired approval reversal without changing account activation', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $member = Member::factory()->create(['is_active' => false]);
    $transaction = MembershipTransaction::factory()->create(['member_id' => $member->id, 'status' => 'Expired', 'starts_at' => now()->subYears(2), 'expires_at' => now()->subYear()]);
    Payment::factory()->create(['membership_transaction_id' => $transaction->id]);
    $this->post(route('transactions.reverse-approval', $transaction), ['reversal_reason' => 'Historical record correction'])->assertSessionHasNoErrors();
    expect($member->fresh()->is_active)->toBeFalse()->and($transaction->fresh()->status)->toBe('Paid');
});

it('rejects unauthorized reversals and missing reasons', function () {
    $transaction = MembershipTransaction::factory()->create(['status' => 'Approved']);
    $this->post(route('transactions.reverse-approval', $transaction))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->post(route('transactions.reverse-approval', $transaction), ['reversal_reason' => 'Mistaken approval'])->assertForbidden();
    $this->actingAs(User::factory()->administrator()->create())->post(route('transactions.reverse-approval', $transaction), ['reversal_reason' => '   '])->assertSessionHasErrors('reversal_reason');
    $this->assertDatabaseCount('membership_approval_reversals', 0);
});
