@extends('layouts.membership')
@section('title', 'Payments')
@section('content')
<header class="page-heading"><div><h1>Payments</h1><p class="muted">Recorded payments and printable simulation receipts.</p></div><a class="button" href="{{ route('transactions.index') }}">Find Transaction</a></header>
<p class="settings-note">Open a Pending transaction to record payment. Payment records are preserved for history.</p>
<ul class="ledger-list">@forelse($payments as $payment)<li class="payment-row"><div><a class="ledger-reference" href="{{ route('payments.show', $payment) }}">{{ $payment->reference }}</a><p>{{ $payment->transaction->member_name }}</p><p class="muted">{{ $payment->transaction->reference }} · {{ $payment->paid_on->format('M j, Y') }} · {{ $payment->method }}</p></div><div>@if($payment->voided_at)<span class="status status-cancelled">Voided</span>@endif<strong>₱{{ number_format($payment->amount, 2) }}</strong></div><a class="button secondary-button" href="{{ route('payments.show', $payment) }}">Receipt</a></li>@empty<li class="empty-message">No payments recorded yet.</li>@endforelse</ul>
@include('transactions.pagination', ['records' => $payments])
@endsection
