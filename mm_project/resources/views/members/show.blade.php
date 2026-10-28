@extends('layouts.membership')
@section('title', 'Member Profile')
@section('content')
    <a class="back-link" href="{{ route('members.index') }}">Back to members</a>
    <header class="page-heading">
        <div class="profile-heading">
            <span class="member-initial profile-initial" aria-hidden="true">{{ mb_substr($member->first_name, 0, 1) }}{{ mb_substr($member->last_name, 0, 1) }}</span>
            <div>
            <h1>{{ $member->first_name }} {{ $member->last_name }}</h1>
            <p class="muted member-number">{{ $member->membership_number }}</p>
            </div>
        </div>
        <a class="button" href="{{ route('members.edit', $member) }}">Edit Member</a>
    </header>
    <section class="profile" aria-labelledby="profile-heading">
        <h2 id="profile-heading">Member Details</h2>
        <dl class="profile-grid">
            <div><dt>Email address</dt><dd>{{ $member->email }}</dd></div>
            <div><dt>Phone</dt><dd>{{ $member->phone ?: 'Not provided' }}</dd></div>
            <div class="full-width"><dt>Address</dt><dd class="address">{{ $member->address ?: 'Not provided' }}</dd></div>
            <div><dt>Category</dt><dd>{{ $member->category->name }}</dd></div>
            <div><dt>Level</dt><dd><span class="level-badge level-{{ match ($member->level->name) { 'Silver' => 'silver', 'Gold' => 'gold', 'Platinum' => 'platinum', default => 'basic' } }}">{{ $member->level->name }}</span></dd></div>
            <div><dt>Membership fee <span class="muted">(current rate)</span></dt><dd class="fee-value">&#8369;{{ number_format($member->level->fee, 2) }}</dd></div>
            <div><dt>Account status</dt><dd><span class="status {{ $member->is_active ? 'active' : 'inactive' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></dd></div>
            <div><dt>Registered</dt><dd>{{ $member->created_at->format('M j, Y') }}</dd></div>
        </dl>
    </section>
    <section class="account-actions" aria-label="Membership status">
        @if($member->is_active)
            <details>
                <summary>Deactivate member</summary>
                <p>Deactivate <strong>{{ $member->first_name }} {{ $member->last_name }}</strong> ({{ $member->membership_number }})? Their profile and membership number will be preserved.</p>
                <form method="POST" action="{{ route('members.destroy', $member) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="button danger-button">Confirm Deactivation</button>
                </form>
            </details>
        @else
            <form method="POST" action="{{ route('members.activate', $member) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="button secondary-button">Reactivate Member</button>
            </form>
        @endif
    </section>
@endsection
