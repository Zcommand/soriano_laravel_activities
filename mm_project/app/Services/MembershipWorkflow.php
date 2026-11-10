<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipWorkflow
{
    public function create(array $data, User $staff): MembershipTransaction
    {
        return DB::transaction(function () use ($data, $staff): MembershipTransaction {
            $member = Member::lockForUpdate()->findOrFail($data['member_id']);
            $existing = MembershipTransaction::where('submission_key', $data['submission_key'])->first();
            if ($existing) {
                if ($existing->member_id !== $member->id || $existing->type !== $data['type'] || $existing->membership_level_id !== (int) $data['membership_level_id']) {
                    throw ValidationException::withMessages(['submission_key' => 'This form was already submitted. Start a new transaction.']);
                }

                return $existing;
            }
            if (! $member->is_active) {
                throw ValidationException::withMessages(['member_id' => 'Reactivate this member before creating a transaction.']);
            }
            if ($member->transactions()->whereIn('status', ['Pending', 'Paid'])->exists()) {
                throw ValidationException::withMessages(['member_id' => 'This member already has an open transaction. Complete or cancel it first.']);
            }
            $hasMembership = $member->transactions()->whereIn('status', ['Approved', 'Expired'])->exists();
            if (($data['type'] === 'Renewal') !== $hasMembership) {
                throw ValidationException::withMessages(['type' => $hasMembership ? 'This member already has an approved membership. Choose Renewal.' : 'Create and approve an Application before a Renewal.']);
            }
            $level = MembershipLevel::lockForUpdate()->findOrFail($data['membership_level_id']);
            $member->load('category');

            return MembershipTransaction::create([
                'submission_key' => $data['submission_key'], 'member_id' => $member->id,
                'membership_level_id' => $level->id, 'membership_category_id' => $member->membership_category_id,
                'created_by' => $staff->id, 'type' => $data['type'],
                'status' => MembershipTransaction::minorUnits($level->fee) === 0 ? 'Paid' : 'Pending',
                'member_name' => $member->first_name.' '.$member->last_name,
                'membership_number' => $member->membership_number,
                'level_name' => $level->name, 'category_name' => $member->category->name,
                'fee' => $level->fee, 'validity_days' => $level->validity_days,
                'benefits' => $level->benefits, 'notes' => $data['notes'] ?? null,
            ]);
        }, 3);
    }

    public function pay(MembershipTransaction $transaction, array $data, User $staff): Payment
    {
        return DB::transaction(function () use ($transaction, $data, $staff): Payment {
            $member = Member::lockForUpdate()->findOrFail($transaction->member_id);
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($transaction->id);
            $existing = Payment::where('submission_key', $data['submission_key'])->first();
            if ($existing) {
                if ($existing->membership_transaction_id !== $transaction->id
                    || MembershipTransaction::minorUnits($existing->amount) !== MembershipTransaction::minorUnits((string) $data['amount'])
                    || $existing->method !== $data['method'] || $existing->paid_on->toDateString() !== $data['paid_on']
                    || $existing->external_reference !== ($data['external_reference'] ?? null)) {
                    throw ValidationException::withMessages(['submission_key' => 'This payment form was already submitted. Open a new payment form.']);
                }

                return $existing;
            }
            if (! $member->is_active || $transaction->status !== 'Pending') {
                throw ValidationException::withMessages(['amount' => 'Payments require an active member and a Pending transaction.']);
            }
            $transaction->load('payments');
            if (MembershipTransaction::minorUnits((string) $data['amount']) > $transaction->balanceMinorUnits()) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the remaining balance.']);
            }
            $payment = $transaction->payments()->create([...$data, 'recorded_by' => $staff->id]);
            $transaction->load('payments');
            if ($transaction->balanceMinorUnits() === 0) {
                $transaction->update(['status' => 'Paid']);
            }

            return $payment;
        }, 3);
    }

    public function voidPayment(Payment $payment, string $reason, User $staff): void
    {
        DB::transaction(function () use ($payment, $reason, $staff): void {
            $original = $payment->transaction;
            Member::lockForUpdate()->findOrFail($original->member_id);
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($original->id);
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->voided_at) {
                throw ValidationException::withMessages(['void_reason' => 'This payment is already voided. Its original audit record is preserved.']);
            }
            if (! in_array($transaction->status, ['Pending', 'Paid'], true)) {
                throw ValidationException::withMessages(['void_reason' => 'Only payments on Pending or Paid transactions can be voided. Approved memberships require a separate membership reversal.']);
            }
            $payment->forceFill(['voided_at' => now(), 'voided_by' => $staff->id, 'void_reason' => $reason])->save();
            $transaction->load('payments');
            $transaction->update(['status' => $transaction->balanceMinorUnits() === 0 ? 'Paid' : 'Pending']);
        }, 3);
    }

    public function approve(MembershipTransaction $transaction, User $staff): void
    {
        DB::transaction(function () use ($transaction, $staff): void {
            $member = Member::lockForUpdate()->findOrFail($transaction->member_id);
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($transaction->id);
            $transaction->load(['payments', 'level']);
            if (! $member->is_active || $transaction->status !== 'Paid' || $transaction->balanceMinorUnits() !== 0) {
                throw ValidationException::withMessages(['approval' => 'Only fully paid transactions for active members can be approved.']);
            }
            $days = $transaction->validity_days ?? $transaction->level->validity_days;
            if (! $days) {
                throw ValidationException::withMessages(['approval' => 'Configure this level’s validity in days on the Levels page before approval.']);
            }
            $start = now()->toImmutable();
            if ($transaction->type === 'Renewal') {
                $previous = $member->transactions()->whereIn('status', ['Approved', 'Expired'])->orderByDesc('expires_at')->first();
                if (! $previous) {
                    throw ValidationException::withMessages(['approval' => 'A renewal requires a previously approved membership.']);
                }
                if ($previous->expires_at->greaterThan($start)) {
                    $start = $previous->expires_at;
                }
            }
            $transaction->update([
                'previous_membership_level_id' => $member->membership_level_id,
                'status' => 'Approved', 'approved_by' => $staff->id, 'approved_at' => now(),
                'validity_days' => $days, 'starts_at' => $start, 'expires_at' => $start->addDays($days),
            ]);
            $member->update(['membership_level_id' => $transaction->membership_level_id]);
        }, 3);
    }

    public function reverseApproval(MembershipTransaction $transaction, string $reason, User $staff): void
    {
        DB::transaction(function () use ($transaction, $reason, $staff): void {
            $member = Member::lockForUpdate()->findOrFail($transaction->member_id);
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($transaction->id);
            if (! in_array($transaction->status, ['Approved', 'Expired'], true)) {
                throw ValidationException::withMessages(['reversal_reason' => 'Only Approved or Expired memberships can have their approval reversed.']);
            }
            $otherTransactions = $member->transactions()->where('id', '!=', $transaction->id);
            if ((clone $otherTransactions)->whereIn('status', ['Pending', 'Paid'])->exists()) {
                throw ValidationException::withMessages(['reversal_reason' => 'Resolve the other open transaction first: void its payments and cancel it, or complete its correction.']);
            }
            if ((clone $otherTransactions)->whereIn('status', ['Approved', 'Expired'])
                ->where(function (Builder $query) use ($transaction): void {
                    $query->where('expires_at', '>', $transaction->expires_at)
                        ->orWhere(function (Builder $query) use ($transaction): void {
                            $query->where('expires_at', $transaction->expires_at)->where('id', '>', $transaction->id);
                        });
                })->exists()) {
                throw ValidationException::withMessages(['reversal_reason' => 'Reverse the later membership or renewal first. This approval supports its validity dates.']);
            }
            $transaction->approvalReversals()->create([
                'reversed_by' => $staff->id, 'reason' => $reason,
                'previous_status' => $transaction->displayStatus(),
                'approved_by' => $transaction->approved_by, 'approved_at' => $transaction->approved_at,
                'starts_at' => $transaction->starts_at, 'expires_at' => $transaction->expires_at,
            ]);
            $transaction->load('payments');
            $transaction->update([
                'status' => $transaction->balanceMinorUnits() === 0 ? 'Paid' : 'Pending',
                'approved_by' => null, 'approved_at' => null, 'starts_at' => null, 'expires_at' => null,
            ]);
            $previous = $member->transactions()->whereIn('status', ['Approved', 'Expired'])->orderByDesc('expires_at')->orderByDesc('id')->first();
            $member->update(['membership_level_id' => $previous?->membership_level_id ?? $transaction->previous_membership_level_id ?? $transaction->membership_level_id]);
        }, 3);
    }

    public function cancel(MembershipTransaction $transaction, string $reason): void
    {
        DB::transaction(function () use ($transaction, $reason): void {
            Member::lockForUpdate()->findOrFail($transaction->member_id);
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($transaction->id);
            if ($transaction->status !== 'Pending' || $transaction->payments()->whereNull('voided_at')->exists()) {
                throw ValidationException::withMessages(['cancellation_reason' => 'Only unpaid Pending transactions can be cancelled. Payment records are preserved.']);
            }
            $transaction->update(['status' => 'Cancelled', 'cancellation_reason' => $reason]);
        }, 3);
    }
}
