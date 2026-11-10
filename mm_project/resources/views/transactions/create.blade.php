@extends('layouts.membership')
@section('title', 'New Transaction')
@section('content')
<a class="back-link" href="{{ route('transactions.index') }}">Back to transactions</a>
<header class="page-heading"><div><h1>New Transaction</h1><p class="muted">Create an application or renew an approved membership.</p></div></header>
@include('transactions.errors')
@if($members->isEmpty() || $levels->isEmpty())
<div class="empty-message">Register an active member and configure a membership level before creating a transaction.</div>
@else
<form class="member-form settings-form" action="{{ route('transactions.store') }}" method="POST">
    @csrf
    <input type="hidden" name="submission_key" value="{{ old('submission_key', (string) Str::uuid()) }}">
    <div class="field"><label for="member_id">Member (required)</label><select id="member_id" name="member_id" required><option value="">Select member</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected((string) old('member_id', $selectedMember?->id) === (string) $member->id)>{{ $member->first_name }} {{ $member->last_name }} · {{ $member->membership_number }}</option>@endforeach</select></div>
    <section class="membership-context" data-membership-summary aria-live="polite" aria-atomic="true" aria-label="Selected member membership">
        @php($contextMember = $members->firstWhere('id', (int) old('member_id', $selectedMember?->id)))
        @if($contextMember)
            @include('transactions.member-summary')
        @else
            <p>Select a member to see their current membership.</p>
        @endif
    </section>
    <div class="form-grid">
        <div class="field"><label for="type">Transaction type (required)</label><select id="type" name="type" required>@foreach(['Application', 'Renewal'] as $type)<option @selected(old('type', $selectedType) === $type)>{{ $type }}</option>@endforeach</select></div>
        <div class="field"><label for="membership_level_id">Level and fee (required)</label><select id="membership_level_id" name="membership_level_id" required><option value="">Select level</option>@foreach($levels as $level)<option value="{{ $level->id }}" @selected((string) old('membership_level_id', $selectedMember?->membership_level_id) === (string) $level->id)>{{ $level->name }} · ₱{{ number_format($level->fee, 2) }} · {{ $level->validity_days ? $level->validity_days.' days' : 'Validity not configured' }}</option>@endforeach</select></div>
    </div>
    <div class="field"><label for="notes">Notes (optional)</label><textarea id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea></div>
    <p class="settings-note">Application is for a first membership. Renewal requires a previously approved membership. The selected fee and level details are saved with this transaction.</p>
    <div class="form-actions"><button class="button" type="submit">Create Transaction</button><a class="button secondary-button" href="{{ route('transactions.index') }}">Cancel</a></div>
</form>
@foreach($members as $contextMember)
    <template id="membership-context-{{ $contextMember->id }}">@include('transactions.member-summary')</template>
@endforeach
@endif
@endsection
@push('scripts')
    <script src="{{ asset('js/transaction-form.js') }}" defer></script>
@endpush
