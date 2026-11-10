@extends('layouts.membership')
@section('title', 'Member Transaction History')
@section('content')
<a class="back-link" href="{{ route('members.show', $member) }}">Back to member profile</a>
<header class="page-heading"><div><h1>Transaction History</h1><p class="muted">{{ $member->first_name }} {{ $member->last_name }} · {{ $member->membership_number }}</p></div>@if($member->is_active)<a class="button" href="{{ route('transactions.create', ['member_id' => $member->id]) }}">New Transaction</a>@endif</header>
@include('transactions.list')
@include('transactions.pagination', ['records' => $transactions])
@endsection
