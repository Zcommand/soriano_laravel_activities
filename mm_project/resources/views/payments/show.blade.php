@extends('layouts.membership')
@section('title', $payment->reference)
@section('content')
<a class="back-link print-hidden" href="{{ route('transactions.show', $payment->transaction) }}">Back to transaction</a>
@include('transactions.errors')
<article class="receipt">
<header class="page-heading"><div><h1>Payment Receipt</h1><p class="muted">{{ $payment->reference }}</p></div><button class="button print-hidden" type="button" data-print>Print Receipt</button></header>
<p class="settings-note">Membership Management · Academic simulation · No gateway charge</p>
<dl class="profile-grid">
    <div><dt>Member</dt><dd>{{ $payment->transaction->member_name }}</dd></div>
    <div><dt>Membership number</dt><dd>{{ $payment->transaction->membership_number }}</dd></div>
    <div><dt>Transaction</dt><dd>{{ $payment->transaction->reference }}</dd></div>
    <div><dt>Type / level</dt><dd>{{ $payment->transaction->type }} / {{ $payment->transaction->level_name }}</dd></div>
    <div><dt>Amount received</dt><dd class="fee-value">₱{{ number_format($payment->amount, 2) }}</dd></div>
    <div><dt>Method</dt><dd>{{ $payment->method }}</dd></div>
    <div><dt>Payment date</dt><dd>{{ $payment->paid_on->format('M j, Y') }}</dd></div>
    <div><dt>Recorded by</dt><dd>{{ $payment->recorder->name }}</dd></div>
    <div><dt>External reference</dt><dd>{{ $payment->external_reference ?: 'Not applicable' }}</dd></div>
</dl>
@if($payment->notes)<p class="settings-benefits">{{ $payment->notes }}</p>@endif
<p class="settings-note">This receipt acknowledges this payment only. Membership approval is recorded separately on the transaction.</p>
</article>
@endsection
