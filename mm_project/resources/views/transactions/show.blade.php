@extends('layouts.membership')
@section('title', $transaction->reference)
@section('content')
<a class="back-link" href="{{ route('transactions.index') }}">Back to transactions</a>
<header class="page-heading"><div><h1>{{ $transaction->reference }}</h1><p class="muted">{{ $transaction->type }} · {{ $transaction->member_name }}</p></div><span class="status transaction-status status-{{ strtolower($transaction->displayStatus()) }}">{{ $transaction->displayStatus() }}</span></header>
@include('transactions.errors')
<div class="form-actions print-hidden"><a class="button secondary-button" href="{{ route('members.show', $transaction->member) }}">Member Profile</a><a class="button secondary-button" href="{{ route('members.history', $transaction->member) }}">Member History</a><button class="button secondary-button" type="button" data-print>Print Record</button></div>
<section class="workflow-section">
<h2>Transaction details</h2>
<dl class="profile-grid">
    <div><dt>Membership number</dt><dd>{{ $transaction->membership_number }}</dd></div>
    <div><dt>Category at application</dt><dd>{{ $transaction->category_name }}</dd></div>
    <div><dt>Level at application</dt><dd>{{ $transaction->level_name }}</dd></div>
    <div><dt>Fee</dt><dd>₱{{ number_format($transaction->fee, 2) }}</dd></div>
    <div><dt>Recorded payments</dt><dd>₱{{ number_format($transaction->paidMinorUnits() / 100, 2) }}</dd></div>
    <div><dt>Balance</dt><dd>₱{{ number_format($transaction->balanceMinorUnits() / 100, 2) }}</dd></div>
    <div><dt>Validity</dt><dd>{{ $transaction->validity_days ? $transaction->validity_days.' days' : 'Configure before approval' }}</dd></div>
    <div><dt>Starts</dt><dd>{{ $transaction->starts_at?->format('M j, Y H:i') ?? 'Awaiting approval' }}</dd></div>
    <div><dt>Expires</dt><dd>{{ $transaction->expires_at?->format('M j, Y H:i') ?? 'Awaiting approval' }}</dd></div>
    <div><dt>Created by</dt><dd>{{ $transaction->creator->name }}</dd></div>
    <div><dt>Approved by</dt><dd>{{ $transaction->approver?->name ?? 'Not approved' }}</dd></div>
    <div><dt>Approved on</dt><dd>{{ $transaction->approved_at?->format('M j, Y H:i') ?? 'Not approved' }}</dd></div>
</dl>
@if($transaction->benefits)<h3>Benefits at application</h3><p class="settings-benefits">{{ $transaction->benefits }}</p>@endif
@if($transaction->notes)<h3>Notes</h3><p class="settings-benefits">{{ $transaction->notes }}</p>@endif
@if($transaction->cancellation_reason)<p class="error">Cancellation reason: {{ $transaction->cancellation_reason }}</p>@endif
</section>
@if(!$transaction->member->is_active)<p class="notice">This member is inactive. Reactivate their account before recording payment or approving membership.</p>@endif
<div class="workflow-actions print-hidden">
@if($transaction->status === 'Pending' && $transaction->member->is_active)
    <a class="button" href="{{ route('payments.create', $transaction) }}">Record Payment</a>
@endif
@if($transaction->status === 'Paid' && $transaction->member->is_active)
    <form method="POST" action="{{ route('transactions.approve', $transaction) }}">@csrf<button class="button" type="submit">Approve Membership</button></form>
@endif
@if($transaction->status === 'Pending' && $transaction->payments->isEmpty())
    <details class="workflow-cancel"><summary>Cancel transaction</summary><form method="POST" action="{{ route('transactions.cancel', $transaction) }}" class="settings-form">@csrf<label for="cancellation_reason">Reason (required)</label><textarea id="cancellation_reason" name="cancellation_reason" required maxlength="1000">{{ old('cancellation_reason') }}</textarea><button class="button danger-button" type="submit">Confirm Cancellation</button></form></details>
@endif
</div>
<section class="workflow-section"><h2>Payment history</h2>
<ul class="ledger-list">@forelse($transaction->payments as $payment)<li class="payment-row"><div><a href="{{ route('payments.show', $payment) }}">{{ $payment->reference }}</a><p class="muted">{{ $payment->paid_on->format('M j, Y') }} · {{ $payment->method }}</p></div><strong>₱{{ number_format($payment->amount, 2) }}</strong><a class="button secondary-button print-hidden" href="{{ route('payments.show', $payment) }}">Receipt</a></li>@empty<li class="empty-message">No payments recorded. @if((float) $transaction->fee === 0.0)This level has no fee.@endif</li>@endforelse</ul>
</section>
@endsection
