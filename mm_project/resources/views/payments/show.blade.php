@extends('layouts.membership')
@section('title', $payment->reference)
@section('content')
<a class="back-link print-hidden" href="{{ route('transactions.show', $payment->transaction) }}">Back to transaction</a>
@include('transactions.errors')
<article class="receipt">
<header class="page-heading"><div><h1>Payment Receipt</h1><p class="muted">{{ $payment->reference }}</p></div><button class="button print-hidden" type="button" data-print>Print Receipt</button></header>
<p class="settings-note">Membership Management · Academic simulation · No gateway charge</p>
@if($payment->voided_at)
<section class="void-audit" aria-labelledby="void-heading">
    <h2 id="void-heading">VOIDED — not a valid payment receipt</h2>
    <p>{{ $payment->void_reason }}</p>
    <p>Voided by {{ $payment->voider?->name ?? 'Staff' }} on {{ $payment->voided_at->format('M j, Y g:i A') }}. Excluded from the paid balance and collections.</p>
</section>
@endif
<dl class="profile-grid">
    <div><dt>Member</dt><dd>{{ $payment->transaction->member_name }}</dd></div>
    <div><dt>Membership number</dt><dd>{{ $payment->transaction->membership_number }}</dd></div>
    <div><dt>Transaction</dt><dd>{{ $payment->transaction->reference }}</dd></div>
    <div><dt>Type / level</dt><dd>{{ $payment->transaction->type }} / {{ $payment->transaction->level_name }}</dd></div>
    <div><dt>{{ $payment->voided_at ? 'Original amount (voided)' : 'Amount received' }}</dt><dd class="fee-value">₱{{ number_format($payment->amount, 2) }}</dd></div>
    <div><dt>Method</dt><dd>{{ $payment->method }}</dd></div>
    <div><dt>Payment date</dt><dd>{{ $payment->paid_on->format('M j, Y') }}</dd></div>
    <div><dt>Recorded by</dt><dd>{{ $payment->recorder->name }}</dd></div>
    <div><dt>External reference</dt><dd>{{ $payment->external_reference ?: 'Not applicable' }}</dd></div>
</dl>
@if($payment->notes)<p class="settings-benefits">{{ $payment->notes }}</p>@endif
<p class="settings-note">This receipt acknowledges this payment only. Membership approval is recorded separately on the transaction.</p>
</article>
@if(! $payment->voided_at)
<section class="account-actions print-hidden" aria-label="Payment correction">
    @if(in_array($payment->transaction->status, ['Pending', 'Paid'], true))
    <details @if($errors->has('void_reason')) open @endif>
        <summary>Void payment</summary>
        <p>This preserves the receipt and reopens the unpaid balance. It cannot be undone and does not issue a refund.</p>
        <form method="POST" action="{{ route('payments.void', $payment) }}">
            @csrf
            <div class="field"><label for="void_reason">Reason for voiding (required)</label><textarea id="void_reason" name="void_reason" required minlength="5" maxlength="1000">{{ old('void_reason') }}</textarea></div>
            <button class="button danger-button" type="submit">Confirm Void Payment</button>
        </form>
    </details>
    @else
    <p class="muted">This payment is protected because its transaction is {{ strtolower($payment->transaction->status) }}. @if(in_array($payment->transaction->status, ['Approved', 'Expired'], true))
        <a href="{{ route('transactions.show', $payment->transaction) }}#reverse-approval">Reverse the membership approval first</a>, then return here to void this payment.
        @endif</p>
    @endif
</section>
@endif
@endsection
