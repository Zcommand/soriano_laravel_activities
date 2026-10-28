@extends('layouts.membership')
@section('title', $member->exists ? 'Edit Member' : 'Add Member')
@section('content')
    <a class="back-link" href="{{ $member->exists ? route('members.show', $member) : route('members.index') }}">Back to {{ $member->exists ? 'profile' : 'members' }}</a>
    <header class="page-heading">
        <div><h1>{{ $member->exists ? 'Edit Member' : 'Add Member' }}</h1>
        @if($member->exists)<p class="muted member-number">{{ $member->membership_number }}</p>@endif</div>
    </header>

    @if($errors->any())
        <div class="error-summary" role="alert">
            <strong>Member details could not be saved.</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="member-editor">
    <form class="member-form" action="{{ $member->exists ? route('members.update', $member) : route('members.store') }}" method="POST">
        @csrf
        @if($member->exists) @method('PUT') @endif

        <fieldset>
            <legend>Member Details</legend>
            <div class="form-grid">
                @foreach([
                    ['first_name', 'First name', 'text', 100, 'given-name', true],
                    ['last_name', 'Last name', 'text', 100, 'family-name', true],
                    ['email', 'Email address', 'email', 255, 'email', true],
                    ['phone', 'Phone (optional)', 'tel', 30, 'tel', false],
                ] as [$name, $label, $type, $max, $autocomplete, $required])
                    <div class="field">
                        <label for="{{ $name }}">{{ $label }}</label>
                        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" maxlength="{{ $max }}" autocomplete="{{ $autocomplete }}"
                               value="{{ old($name, $member->{$name}) }}" @required($required)
                               @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
                        @error($name) <p class="error" id="{{ $name }}-error">{{ $message }}</p> @enderror
                    </div>
                @endforeach
                <div class="field full-width">
                    <label for="address">Address (optional)</label>
                    <textarea id="address" name="address" rows="3" maxlength="1000" autocomplete="street-address"
                              @error('address') aria-invalid="true" aria-describedby="address-error" @enderror>{{ old('address', $member->address) }}</textarea>
                    @error('address') <p class="error" id="address-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>
        <fieldset>
            <legend>Membership</legend>
            <div class="form-grid">
                <div class="field">
                    <label for="membership_category_id">Category</label>
                    <select id="membership_category_id" name="membership_category_id" required
                            @error('membership_category_id') aria-invalid="true" aria-describedby="category-error" @enderror>
                        <option value="">Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('membership_category_id', $member->membership_category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('membership_category_id') <p class="error" id="category-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="membership_level_id">Level and fee (PHP)</label>
                    <select id="membership_level_id" name="membership_level_id" required
                            @error('membership_level_id') aria-invalid="true" aria-describedby="level-error" @enderror>
                        @foreach($levels as $level)
                            <option value="{{ $level->id }}" @selected((string) old('membership_level_id', $member->membership_level_id ?? $levels->firstWhere('name', 'Basic')?->id) === (string) $level->id)>{{ $level->name }} - &#8369;{{ number_format($level->fee, 2) }}</option>
                        @endforeach
                    </select>
                    @error('membership_level_id') <p class="error" id="level-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>
        <div class="form-actions">
            <button class="button" type="submit">{{ $member->exists ? 'Save Changes' : 'Register Member' }}</button>
            <a class="button secondary-button" href="{{ $member->exists ? route('members.show', $member) : route('members.index') }}">Cancel</a>
        </div>
    </form>
    <aside class="level-reference" aria-labelledby="level-reference-heading">
        <h2 id="level-reference-heading">Membership Levels</h2>
        <ul class="level-reference-list">
            @foreach($levels as $level)
                @php
                    $selected = (string) old('membership_level_id', $member->membership_level_id ?? $levels->firstWhere('name', 'Basic')?->id) === (string) $level->id;
                    $tone = match ($level->name) { 'Silver' => 'silver', 'Gold' => 'gold', 'Platinum' => 'platinum', default => 'basic' };
                @endphp
                <li data-reference-level="{{ $level->id }}" class="{{ $selected ? 'is-selected' : '' }}">
                    <div><span class="level-badge level-{{ $tone }}">{{ $level->name }}</span><span class="selected-label" @if(!$selected) hidden @endif>Selected</span></div>
                    <span class="level-price">&#8369;{{ number_format($level->fee, 2) }}</span>
                </li>
            @endforeach
        </ul>
    </aside>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/member-form.js') }}" defer></script>
@endpush
