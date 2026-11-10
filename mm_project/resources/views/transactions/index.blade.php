@extends('layouts.membership')
@section('title', 'Transactions')
@section('content')
<header class="page-heading"><div><h1>Transactions</h1><p class="muted">Applications, renewals, and their payment progress.</p></div><a class="button" href="{{ route('transactions.create') }}">New Transaction</a></header>
@include('transactions.errors')
<form class="ledger-filters" method="GET" action="{{ route('transactions.index') }}">
    <div class="field"><label for="search">Search transactions</label><input id="search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="Reference, member name or number"></div>
    <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
    <button class="button" type="submit">Search</button><a class="button secondary-button" href="{{ route('transactions.index') }}">Reset</a>
</form>
@include('transactions.list')
@include('transactions.pagination', ['records' => $transactions])
@endsection
