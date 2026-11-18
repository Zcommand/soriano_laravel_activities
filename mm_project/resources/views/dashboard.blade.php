@extends('layouts.membership')
@section('title', 'Membership desk')
@section('content')
<header class="page-heading desk-heading">
    <div><p class="desk-eyebrow">Staff workspace</p><h1>Membership desk</h1><p class="muted">Members, applications, and the next step.</p></div>
    <a class="button" href="{{ route('transactions.create') }}">New Transaction</a>
</header>
<div class="desk-board">
<section class="desk-register" aria-labelledby="register-heading">
<header class="desk-section-heading"><h2 id="register-heading">Membership register</h2><span class="muted">All accounts</span></header>
<div class="dashboard-summary" aria-label="Membership overview">
    <a href="{{ route('members.index') }}"><span>Total members</span><strong>{{ $summary['members'] }}</strong></a>
    <a href="{{ route('members.index', ['status' => 'active']) }}"><span>Active accounts</span><strong>{{ $summary['active'] }}</strong></a>
    <a href="{{ route('transactions.index', ['status' => 'Pending']) }}"><span>Pending transactions</span><strong>{{ $summary['pending'] }}</strong></a>
    <a href="{{ route('payments.index') }}"><span>Recorded collections</span><strong>₱{{ number_format($summary['collected'], 2) }}</strong></a>
</div>
</section>
<section class="desk-attention" aria-labelledby="attention-heading">
    <header class="desk-section-heading"><div><h2 id="attention-heading">Needs attention</h2><p class="muted">{{ $summary['paid'] }} awaiting approval · {{ $summary['pending'] }} awaiting payment</p></div><a class="back-link" href="{{ route('transactions.index') }}">Open transactions</a></header>
    <ul class="desk-queue">
        @forelse($attention as $item)
            <li><div><a class="ledger-reference" href="{{ route('transactions.show', $item) }}">{{ $item->member_name }}</a><p class="muted">{{ $item->reference }} · {{ $item->level_name }}</p></div><span class="status transaction-status status-{{ strtolower($item->status) }}">{{ $item->status }}</span><a class="button secondary-button" href="{{ route('transactions.show', $item) }}">{{ $item->status === 'Paid' ? 'Review approval' : 'Review payment' }}</a></li>
        @empty
            <li class="desk-clear"><strong>Nothing waiting right now.</strong><p class="muted">Pending applications and paid transactions appear here when they need a next step.</p></li>
        @endforelse
    </ul>
</section>
</div>
<div class="dashboard-lower">
<section class="workflow-section"><header class="desk-section-heading"><h2>Recent transactions</h2><a class="back-link" href="{{ route('transactions.index') }}">View all transactions</a></header>@include('transactions.list')</section>
@include('partials.company-location')
</div>
<p class="desk-footnote">Collections represent simulated payment records; no online payments are processed.</p>
@endsection

@push('scripts')
    @include('partials.location-scripts')
@endpush
