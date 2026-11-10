@extends('layouts.membership')
@section('title', 'Dashboard')
@section('content')
<header class="page-heading"><div><h1>Dashboard</h1><p class="muted">Membership and transaction overview.</p></div><a class="button" href="{{ route('transactions.create') }}">New Transaction</a></header>
<div class="dashboard-summary">
    <a href="{{ route('members.index') }}"><span>Total members</span><strong>{{ $summary['members'] }}</strong></a>
    <a href="{{ route('members.index', ['status' => 'active']) }}"><span>Active accounts</span><strong>{{ $summary['active'] }}</strong></a>
    <a href="{{ route('transactions.index', ['status' => 'Pending']) }}"><span>Pending transactions</span><strong>{{ $summary['pending'] }}</strong></a>
    <a href="{{ route('payments.index') }}"><span>Recorded collections</span><strong>₱{{ number_format($summary['collected'], 2) }}</strong></a>
</div>
<p class="settings-note">{{ $summary['paid'] }} fully paid {{ Str::plural('transaction', $summary['paid']) }} awaiting approval. Collections are simulated payment records.</p>
<section class="workflow-section"><header class="page-heading"><h2>Recent transactions</h2><a class="back-link" href="{{ route('transactions.index') }}">View all transactions</a></header>@include('transactions.list')</section>
@endsection
