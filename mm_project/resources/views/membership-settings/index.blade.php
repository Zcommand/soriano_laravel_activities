@extends('layouts.membership')
@section('title', ucfirst($kind))
@section('content')
<header class="page-heading">
    <div><h1>{{ ucfirst($kind) }}</h1><p class="muted">{{ $kind === 'levels' ? 'Set membership fees, validity, and benefits.' : 'Organize the categories available during registration.' }}</p></div>
    <a class="button" href="{{ route($kind.'.create') }}">Add {{ $label }}</a>
</header>
@if($errors->any())
    <div class="error-summary" role="alert">@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>
@endif
<p class="settings-note">Changes appear in member forms. Records assigned to members are protected from deletion.</p>
<div class="settings-list">
@forelse($records as $record)
    <article class="settings-record">
        <div class="settings-record-heading">
            <h2>@if($kind === 'levels')<span class="level-badge level-{{ match ($record->name) { 'Silver' => 'silver', 'Gold' => 'gold', 'Platinum' => 'platinum', default => 'basic' } }}">{{ $record->name }}</span>@else{{ $record->name }}@endif</h2>
            <span class="muted">{{ $record->members_count }} {{ Str::plural('member', $record->members_count) }}</span>
        </div>
        @if($kind === 'levels')
            <dl class="settings-facts">
                <div><dt>Fee</dt><dd class="settings-fee">₱{{ number_format($record->fee, 2) }}</dd></div>
                <div><dt>Validity</dt><dd>{{ $record->validity_days ? $record->validity_days.' days' : 'Not configured' }}</dd></div>
            </dl>
            <p class="settings-benefits">{{ $record->benefits ?: 'Benefits have not been configured yet.' }}</p>
        @endif
        <div class="settings-actions">
            <a class="button secondary-button" href="{{ route($kind.'.edit', $record) }}" aria-label="Edit {{ $record->name }}">Edit</a>
            @if($record->members_count)
                <span class="muted">In use · deletion protected</span>
            @else
                <details class="settings-delete">
                    <summary>Delete</summary>
                    <form action="{{ route($kind.'.destroy', $record) }}" method="POST">
                        @csrf @method('DELETE')
                        <p>Delete {{ $record->name }} permanently? This cannot be undone.</p>
                        <button class="button danger-button" type="submit">Confirm deletion</button>
                    </form>
                </details>
            @endif
        </div>
    </article>
@empty
    <div class="empty-message"><h2>No {{ $kind }} yet</h2><p>Add your first {{ strtolower($label) }} to make it available during registration.</p></div>
@endforelse
</div>
@if($records->hasPages())
<nav class="settings-pagination" aria-label="{{ ucfirst($kind) }} pagination">
    @if($records->previousPageUrl())<a class="button secondary-button" href="{{ $records->previousPageUrl() }}">Previous</a>@endif
    <span>Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
    @if($records->nextPageUrl())<a class="button secondary-button" href="{{ $records->nextPageUrl() }}">Next</a>@endif
</nav>
@endif
@endsection
