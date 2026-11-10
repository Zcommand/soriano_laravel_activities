<?php

use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('shows the current membership without mistaking a future renewal for the current level', function () {
    $this->freezeSecond();
    $this->actingAs(User::factory()->administrator()->create());
    $member = Member::factory()->create();
    MembershipTransaction::factory()->create(['member_id' => $member->id, 'status' => 'Approved', 'level_name' => 'Current Silver', 'starts_at' => now()->subDays(20), 'expires_at' => now()->addDays(345)]);
    MembershipTransaction::factory()->create(['member_id' => $member->id, 'status' => 'Approved', 'type' => 'Renewal', 'level_name' => 'Future Gold', 'starts_at' => now()->addDays(345), 'expires_at' => now()->addDays(710)]);
    expect($member->fresh()->currentMembership->level_name)->toBe('Current Silver');
    $this->get(route('transactions.create', ['member_id' => $member->id]))->assertOk()->assertSee('Current Silver')->assertSee('Valid until')->assertDontSee('Future Gold');
    $this->travel(346)->days();
    expect($member->fresh()->currentMembership->level_name)->toBe('Future Gold');
});

it('shows unapproved assigned levels and expired memberships accurately', function () {
    $this->freezeSecond();
    $this->actingAs(User::factory()->administrator()->create());
    $member = Member::factory()->create();
    $this->get(route('transactions.create', ['member_id' => $member->id]))->assertOk()->assertSee('Assigned level')->assertSee('Not yet approved')->assertSee($member->level->name);
    MembershipTransaction::factory()->create(['member_id' => $member->id, 'status' => 'Approved', 'starts_at' => now()->subDays(400), 'expires_at' => now()->subDay()]);
    $this->get(route('transactions.create', ['member_id' => $member->id]))->assertOk()->assertSee('Expired')->assertSee('Membership level');
});

function transactionPayload(Member $member, array $overrides = []): array
{
    return array_replace(['member_id' => $member->id, 'membership_level_id' => $member->membership_level_id, 'type' => 'Application', 'submission_key' => (string) Str::uuid()], $overrides);
}

function paymentPayload(string $amount = '200.00', array $overrides = []): array
{
    return array_replace(['amount' => $amount, 'method' => 'Cash', 'paid_on' => today()->toDateString(), 'submission_key' => (string) Str::uuid()], $overrides);
}

it('protects the complete transaction workspace', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route($route))->assertForbidden();
})->with(['dashboard', 'transactions.index', 'transactions.create', 'payments.index']);

it('rejects writes by regular users', function () {
    $transaction = MembershipTransaction::factory()->create();
    $this->actingAs(User::factory()->create());
    $this->post(route('transactions.store'), [])->assertForbidden();
    $this->post(route('transactions.approve', $transaction))->assertForbidden();
    $this->post(route('transactions.cancel', $transaction), ['cancellation_reason' => 'No longer needed'])->assertForbidden();
    $this->post(route('payments.store', $transaction), paymentPayload())->assertForbidden();
    $this->assertDatabaseCount('payments', 0);
});

it('captures membership details and ignores client fee and status overrides', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $level = MembershipLevel::factory()->create(['name' => 'Executive', 'fee' => '600.50', 'validity_days' => 365, 'benefits' => 'Member assistance']);
    $member = Member::factory()->create(['membership_level_id' => $level->id]);
    $data = transactionPayload($member, ['fee' => '0.01', 'status' => 'Approved']);
    $this->post(route('transactions.store'), $data)->assertSessionHasNoErrors();
    $transaction = MembershipTransaction::firstOrFail();
    expect($transaction->fee)->toBe('600.50')->and($transaction->status)->toBe('Pending')->and($transaction->reference)->toStartWith('TXN-');
    $level->update(['name' => 'Renamed', 'fee' => '900.00', 'benefits' => 'New benefit', 'validity_days' => 30]);
    expect($transaction->fresh()->level_name)->toBe('Executive')->and($transaction->fresh()->fee)->toBe('600.50')->and($transaction->fresh()->validity_days)->toBe(365);
    $this->get(route('transactions.show', $transaction))->assertOk()->assertSee('Member assistance');
    $this->post(route('transactions.store'), $data)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('membership_transactions', 1);
});

it('records partial payments and only permits fully paid approval', function () {
    $this->freezeSecond();
    $this->actingAs($staff = User::factory()->administrator()->create());
    $transaction = MembershipTransaction::factory()->create();
    $this->post(route('transactions.approve', $transaction))->assertSessionHasErrors('approval');
    $this->post(route('payments.store', $transaction), paymentPayload('75.25'))->assertSessionHasNoErrors();
    expect($transaction->fresh()->status)->toBe('Pending');
    $this->post(route('transactions.approve', $transaction))->assertSessionHasErrors('approval');
    $this->post(route('payments.store', $transaction), paymentPayload('124.75'))->assertSessionHasNoErrors();
    expect($transaction->fresh()->status)->toBe('Paid');
    $this->post(route('transactions.approve', $transaction))->assertSessionHasNoErrors();
    $transaction->refresh();
    expect($transaction->status)->toBe('Approved')->and($transaction->approved_by)->toBe($staff->id)
        ->and($transaction->expires_at->equalTo(now()->addDays(365)))->toBeTrue();
    $this->post(route('transactions.approve', $transaction))->assertSessionHasErrors('approval');
    expect($transaction->fresh()->expires_at->equalTo(now()->addDays(365)))->toBeTrue();
});

it('prevents overpayment and duplicate payment submissions', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $transaction = MembershipTransaction::factory()->create();
    $this->post(route('payments.store', $transaction), paymentPayload('200.01'))->assertSessionHasErrors('amount');
    $data = paymentPayload();
    $this->post(route('payments.store', $transaction), $data)->assertSessionHasNoErrors();
    $this->post(route('payments.store', $transaction), $data)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('payments', 1);
    $this->post(route('payments.store', $transaction), [...$data, 'amount' => '1.00'])->assertSessionHasErrors('submission_key');
    $this->post(route('payments.store', $transaction), paymentPayload('1.00'))->assertSessionHasErrors('amount');
    expect($transaction->fresh()->status)->toBe('Paid');
});

it('validates payment inputs', function (array $changes, string $field) {
    $this->actingAs(User::factory()->administrator()->create());
    $transaction = MembershipTransaction::factory()->create();
    $this->post(route('payments.store', $transaction), paymentPayload('200.00', $changes))->assertSessionHasErrors($field);
    $this->assertDatabaseCount('payments', 0);
})->with([
    'zero' => [['amount' => '0'], 'amount'],
    'negative' => [['amount' => '-1'], 'amount'],
    'precision' => [['amount' => '1.001'], 'amount'],
    'invalid method' => [['method' => 'Gateway'], 'method'],
    'future date' => [['paid_on' => '2099-01-01'], 'paid_on'],
    'missing transfer reference' => [['method' => 'Bank transfer'], 'external_reference'],
    'invalid submission' => [['submission_key' => 'bad'], 'submission_key'],
]);

it('rejects invalid transactions and duplicate open applications', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $member = Member::factory()->create();
    $this->post(route('transactions.store'), [])->assertSessionHasErrors(['member_id', 'membership_level_id', 'type', 'submission_key']);
    $this->post(route('transactions.store'), transactionPayload($member, ['type' => 'Renewal']))->assertSessionHasErrors('type');
    $this->post(route('transactions.store'), transactionPayload($member, ['type' => 'Invalid']))->assertSessionHasErrors('type');
    $member->level->update(['fee' => 200]);
    $this->post(route('transactions.store'), transactionPayload($member))->assertSessionHasNoErrors();
    $this->post(route('transactions.store'), transactionPayload($member))->assertSessionHasErrors('member_id');
    $this->assertDatabaseCount('membership_transactions', 1);
});

it('blocks inactive member operations', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $member = Member::factory()->inactive()->create();
    $this->post(route('transactions.store'), transactionPayload($member))->assertSessionHasErrors('member_id');
    $transaction = MembershipTransaction::factory()->create(['member_id' => $member->id]);
    $this->post(route('payments.store', $transaction), paymentPayload())->assertSessionHasErrors('amount');
    $transaction->update(['status' => 'Paid', 'fee' => 0]);
    $this->post(route('transactions.approve', $transaction))->assertSessionHasErrors('approval');
});

it('requires configured validity before approval and supports free levels', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $level = MembershipLevel::factory()->create(['fee' => 0, 'validity_days' => null]);
    $member = Member::factory()->create(['membership_level_id' => $level->id]);
    $this->post(route('transactions.store'), transactionPayload($member))->assertSessionHasNoErrors();
    $transaction = MembershipTransaction::firstOrFail();
    expect($transaction->status)->toBe('Paid');
    $this->post(route('transactions.approve', $transaction))->assertSessionHasErrors('approval');
    $level->update(['validity_days' => 365]);
    $this->post(route('transactions.approve', $transaction))->assertSessionHasNoErrors();
    expect($transaction->fresh()->validity_days)->toBe(365);
});

it('extends an early renewal from the previous expiry and preserves its fee', function () {
    $this->freezeSecond();
    $this->actingAs(User::factory()->administrator()->create());
    $level = MembershipLevel::factory()->create(['fee' => 200, 'validity_days' => 365]);
    $member = Member::factory()->create(['membership_level_id' => $level->id]);
    $previous = MembershipTransaction::factory()->create(['member_id' => $member->id, 'status' => 'Approved', 'starts_at' => now()->subDays(300), 'expires_at' => now()->addDays(65), 'approved_at' => now()->subDays(300)]);
    $this->post(route('transactions.store'), transactionPayload($member))->assertSessionHasErrors('type');
    $this->post(route('transactions.store'), transactionPayload($member, ['type' => 'Renewal']))->assertSessionHasNoErrors();
    $renewal = MembershipTransaction::latest('id')->firstOrFail();
    $this->post(route('payments.store', $renewal), paymentPayload())->assertSessionHasNoErrors();
    $this->post(route('transactions.approve', $renewal))->assertSessionHasNoErrors();
    expect($renewal->fresh()->starts_at->equalTo($previous->expires_at))->toBeTrue();
    expect($renewal->fresh()->expires_at->equalTo($previous->expires_at->addDays(365)))->toBeTrue();
});

it('expires due memberships and starts late renewal from approval time', function () {
    $this->freezeSecond();
    $this->actingAs(User::factory()->administrator()->create());
    $member = Member::factory()->create();
    $member->level->update(['fee' => 0, 'validity_days' => 365]);
    $previous = MembershipTransaction::factory()->create(['member_id' => $member->id, 'status' => 'Approved', 'expires_at' => now()->subDay()]);
    $this->get(route('transactions.index', ['status' => 'Expired']))->assertOk()->assertSee($previous->reference);
    expect($previous->fresh()->status)->toBe('Expired');
    $this->post(route('transactions.store'), transactionPayload($member, ['type' => 'Renewal']))->assertSessionHasNoErrors();
    $renewal = MembershipTransaction::latest('id')->firstOrFail();
    $this->post(route('transactions.approve', $renewal))->assertSessionHasNoErrors();
    expect($renewal->fresh()->starts_at->equalTo(now()))->toBeTrue();
});

it('cancels only unpaid pending transactions and retains their history', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $transaction = MembershipTransaction::factory()->create();
    $this->post(route('transactions.cancel', $transaction), [])->assertSessionHasErrors('cancellation_reason');
    $this->post(route('transactions.cancel', $transaction), ['cancellation_reason' => 'Duplicate application'])->assertSessionHasNoErrors();
    expect($transaction->fresh()->status)->toBe('Cancelled');
    $this->post(route('payments.store', $transaction), paymentPayload())->assertSessionHasErrors('amount');
    $partial = MembershipTransaction::factory()->create();
    $this->post(route('payments.store', $partial), paymentPayload('1.00'))->assertSessionHasNoErrors();
    $this->post(route('transactions.cancel', $partial), ['cancellation_reason' => 'Changed mind'])->assertSessionHasErrors('cancellation_reason');
    $this->assertDatabaseCount('payments', 1);
});

it('shows correct dashboard totals member history and printable receipts', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $transaction = MembershipTransaction::factory()->create(['notes' => '<script>bad()</script>']);
    $this->post(route('payments.store', $transaction), paymentPayload('55.50'))->assertSessionHasNoErrors();
    $payment = Payment::firstOrFail();
    $this->get(route('dashboard'))->assertOk()->assertSee('₱55.50')->assertViewHas('summary', fn (array $summary): bool => $summary['pending'] === 1);
    $this->get(route('payments.index'))->assertOk()->assertSee($payment->reference);
    $this->get(route('payments.show', $payment))->assertOk()->assertSee('Print Receipt')->assertSee($transaction->reference)->assertSee('₱55.50');
    $this->get(route('members.history', $transaction->member))->assertOk()->assertSee($transaction->reference);
    $this->get(route('transactions.show', $transaction))->assertOk()->assertSee('&lt;script&gt;bad()&lt;/script&gt;', false)->assertDontSee('<script>bad()</script>', false);
    $this->get(route('transactions.create'))->assertOk();
    $this->get(route('payments.create', $transaction))->assertOk();
    $this->get(route('transactions.index', ['search' => 'NoSuchTransaction']))->assertOk()->assertDontSee($transaction->reference);
    $this->get(route('transactions.index', ['status' => 'bad']))->assertSessionHasErrors('status');
});

it('protects historic levels and categories even after member reassignment', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $transaction = MembershipTransaction::factory()->create();
    $this->delete(route('levels.destroy', $transaction->level))->assertSessionHasErrors('delete');
    $this->delete(route('categories.destroy', $transaction->category))->assertSessionHasErrors('delete');
    $this->assertModelExists($transaction->level);
    $this->assertModelExists($transaction->category);
});

it('keeps transaction references unique and exposes the expiry command', function () {
    $first = MembershipTransaction::factory()->create();
    $second = MembershipTransaction::factory()->create();
    expect($first->reference)->not->toBe($second->reference);
    $this->artisan('memberships:expire')->assertSuccessful();
});
