@extends('layouts.membership')
@section('title', 'Members')
@section('content')
    <header class="page-heading">
        <div class="heading-title">
            <h1>Members</h1>
            <span class="count">{{ $members->total() }} {{ request()->anyFilled(['search', 'category', 'level', 'status']) ? 'results' : ($members->total() === 1 ? 'member' : 'members') }}</span>
        </div>
        <a class="button" href="{{ route('members.create') }}">Add Member</a>
    </header>

    <section class="member-overview" aria-labelledby="overview-heading">
        <div class="overview-heading">
            <h2 id="overview-heading">Member overview</h2>
            <span class="muted">All records</span>
        </div>
        <dl class="member-summary">
            <div class="summary-total"><dt>Total members</dt><dd>{{ number_format($summary['total']) }}</dd></div>
            <div class="summary-active"><dt>Active accounts</dt><dd>{{ number_format($summary['active']) }}</dd></div>
            <div class="summary-inactive"><dt>Inactive accounts</dt><dd>{{ number_format($summary['inactive']) }}</dd></div>
        </dl>
        <details class="level-breakdown" open>
        <summary>By membership level</summary>
        <dl class="level-summary" aria-label="Members by level">
            @foreach($levels as $level)
                <div>
                    <dt><span class="level-badge level-{{ match ($level->name) { 'Silver' => 'silver', 'Gold' => 'gold', 'Platinum' => 'platinum', default => 'basic' } }}">{{ $level->name }}</span></dt>
                    <dd>{{ number_format($level->members_count) }} <span class="muted">{{ $level->members_count === 1 ? 'member' : 'members' }}</span></dd>
                </div>
            @endforeach
        </dl>
        </details>
    </section>

    <div class="records-heading">
        <h2>Member directory</h2>
        <span class="muted">{{ number_format($members->total()) }} {{ $members->total() === 1 ? 'result' : 'results' }}</span>
    </div>
    <form class="filters" action="{{ route('members.index') }}" method="GET">
        <div class="field search-field">
            <label for="search">Search members</label>
            <input id="search" name="search" type="search" value="{{ request('search') }}" maxlength="100" placeholder="Name, company, email or ID">
        </div>
        <div class="field">
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="level">Level</label>
            <select id="level" name="level">
                <option value="">All levels</option>
                @foreach($levels as $level)
                    <option value="{{ $level->id }}" @selected((string) request('level') === (string) $level->id)>{{ $level->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field status-field">
            <label for="status">Account status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="filter-actions">
            <button class="button" type="submit">Search</button>
            <a class="reset-link" href="{{ route('members.index') }}">Reset</a>
        </div>
    </form>

    @if($errors->any())
        <ul class="error-summary" role="alert">
            @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
    @endif

    <div class="table-scroll {{ $members->isEmpty() ? 'is-empty' : '' }}" role="region" aria-label="Member records" tabindex="0">
        <table class="member-table" role="table">
            <caption class="sr-only">Member records</caption>
            <colgroup>
                <col class="column-member"><col class="column-number"><col class="column-category">
                <col class="column-level"><col class="column-status"><col class="column-actions">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">Member</th>
                    <th scope="col">Membership number</th>
                    <th scope="col">Category</th>
                    <th scope="col">Level</th>
                    <th scope="col">Account status</th>
                    <th scope="col" class="actions-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr role="row">
                        <td class="identity-cell" role="cell"><div class="member-identity"><span class="member-initial" aria-hidden="true">{{ mb_substr($member->first_name, 0, 1) }}{{ mb_substr($member->last_name, 0, 1) }}</span><div><a class="member-name" href="{{ route('members.show', $member) }}">{{ $member->first_name }} {{ $member->last_name }}</a><span class="secondary">{{ $member->email }}</span></div></div></td>
                        <td class="member-number" data-label="Membership number" role="cell">{{ $member->membership_number }}</td>
                        <td data-label="Category" role="cell">{{ $member->category->name }}@if($member->category->name === 'Corporate' && $member->company_name)<span class="secondary">{{ $member->company_name }}</span>@endif</td>
                        <td data-label="Level" role="cell"><span class="level-badge level-{{ match ($member->level->name) { 'Silver' => 'silver', 'Gold' => 'gold', 'Platinum' => 'platinum', default => 'basic' } }}">{{ $member->level->name }}</span></td>
                        <td data-label="Account status" role="cell"><span class="status {{ $member->is_active ? 'active' : 'inactive' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="actions-cell" role="cell"><div class="row-actions"><a href="{{ route('members.show', $member) }}" aria-label="View {{ $member->first_name }} {{ $member->last_name }}">View</a><a href="{{ route('members.edit', $member) }}" aria-label="Edit {{ $member->first_name }} {{ $member->last_name }}">Edit</a></div></td>
                    </tr>
                @empty
                    <tr class="empty-row"><td colspan="6" class="empty">
                        <strong>No members found.</strong>
                        @if(request()->anyFilled(['search', 'category', 'level', 'status']))
                            <a class="button secondary-button" href="{{ route('members.index') }}">Clear filters</a>
                        @else
                            <a class="button secondary-button" href="{{ route('members.create') }}">Register your first member</a>
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($members->total() > 0)
    <nav class="pagination" aria-label="Member pagination">
        <span class="muted">{{ $members->firstItem() ?? 0 }}-{{ $members->lastItem() ?? 0 }} of {{ $members->total() }}</span>
        @if($members->hasPages())
        <div class="row-actions">
            @if($members->onFirstPage())
                <span class="muted" aria-disabled="true">Previous</span>
            @else
                <a href="{{ $members->previousPageUrl() }}" rel="prev">Previous</a>
            @endif
            <span>Page {{ $members->currentPage() }} of {{ $members->lastPage() }}</span>
            @if($members->hasMorePages())
                <a href="{{ $members->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="muted" aria-disabled="true">Next</span>
            @endif
        </div>
        @endif
    </nav>
    @endif
@endsection
