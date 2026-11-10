@extends('layouts.membership')
@section('title', 'Record Payment')
@section('content')
<a class="back-link" href="{{ route('transactions.show', $transaction) }}">Back to transaction</a>
<header class="page-heading"><div><h1>Record Payment</h1><p class="muted">{{ $transaction->reference }} · {{ $transaction->member_name }}</p></div></header>
@include('transactions.errors')
<p class="notice">Simulation only. Recording a payment does not charge a card, bank account, or e-wallet.</p>
<form class="member-form settings-form" action="{{ route('payments.store', $transaction) }}" method="POST">
    @csrf
    <input type="hidden" name="submission_key" value="{{ old('submission_key', (string) Str::uuid()) }}">
    <p><strong>Remaining balance: ₱{{ number_format($transaction->balanceMinorUnits() / 100, 2) }}</strong></p>
    <div class="form-grid">
        <div class="field"><label for="amount">Amount (PHP, required)</label><input id="amount" name="amount" type="number" min="0.01" max="{{ number_format($transaction->balanceMinorUnits() / 100, 2, '.', '') }}" step="0.01" required value="{{ old('amount', number_format($transaction->balanceMinorUnits() / 100, 2, '.', '')) }}"></div>
        <div class="field"><label for="paid_on">Payment date (required)</label><input id="paid_on" name="paid_on" type="date" max="{{ today()->toDateString() }}" required value="{{ old('paid_on', today()->toDateString()) }}"></div>
    </div>
    <div class="field"><label for="method">Payment method (required)</label><select id="method" name="method" required>@foreach($methods as $method)<option @selected(old('method') === $method)>{{ $method }}</option>@endforeach</select></div>
    <div class="field"><label for="external_reference">Bank / e-wallet reference (required for non-cash)</label><input id="external_reference" name="external_reference" maxlength="100" value="{{ old('external_reference') }}"></div>
    <div class="field"><label for="notes">Notes (optional)</label><textarea id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea></div>
    <div class="form-actions"><button class="button" type="submit">Record Payment</button><a class="button secondary-button" href="{{ route('transactions.show', $transaction) }}">Cancel</a></div>
</form>
@endsection
