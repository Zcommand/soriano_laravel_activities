@extends('layouts.membership')
@section('title', ($record->exists ? 'Edit ' : 'Add ').$label)
@section('content')
<a class="back-link" href="{{ route($kind.'.index') }}">Back to {{ $kind }}</a>
<header class="page-heading"><h1>{{ $record->exists ? 'Edit' : 'Add' }} {{ $label }}</h1></header>
@if($errors->any())
<div class="error-summary" role="alert"><strong>Please check the highlighted fields.</strong><ul>@foreach($errors->messages() as $field => $messages)<li><a href="#{{ $field }}">{{ $messages[0] }}</a></li>@endforeach</ul></div>
@endif
<form class="member-form settings-form" action="{{ $record->exists ? route($kind.'.update', $record) : route($kind.'.store') }}" method="POST">
    @csrf
    @if($record->exists) @method('PUT') @endif
    <div class="field">
        <label for="name">Name <span class="required-mark" aria-hidden="true">*</span></label>
        <input id="name" name="name" value="{{ old('name', $record->name) }}" maxlength="100" required
            @readonly($kind === 'categories' && $record->name === 'Corporate')
            @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        @if($kind === 'categories' && $record->name === 'Corporate')<p class="muted">The Corporate name is retained because it enables the company-name field.</p>@endif
        @error('name')<p class="error" id="name-error">{{ $message }}</p>@enderror
    </div>
    @if($kind === 'levels')
    <div class="form-grid">
        <div class="field">
            <label for="fee">Fee (PHP) <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="fee" name="fee" type="number" min="0" max="999999.99" step="0.01" value="{{ old('fee', $record->fee) }}" required @error('fee') aria-invalid="true" aria-describedby="fee-error" @enderror>
            @error('fee')<p class="error" id="fee-error">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="validity_days">Validity in days (optional)</label>
            <input id="validity_days" name="validity_days" type="number" min="1" max="36500" step="1" value="{{ old('validity_days', $record->validity_days) }}" @error('validity_days') aria-invalid="true" aria-describedby="validity_days-error" @enderror>
            @error('validity_days')<p class="error" id="validity_days-error">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="field">
        <label for="benefits">Benefits (optional)</label>
        <textarea id="benefits" name="benefits" rows="5" maxlength="5000" @error('benefits') aria-invalid="true" aria-describedby="benefits-error" @enderror>{{ old('benefits', $record->benefits) }}</textarea>
        @error('benefits')<p class="error" id="benefits-error">{{ $message }}</p>@enderror
    </div>
    <p class="settings-note">Leaving validity blank means it is not configured. Updating a level changes its details for all assigned members.</p>
    @endif
    <div class="form-actions"><button class="button" type="submit">{{ $record->exists ? 'Save Changes' : 'Add '.$label }}</button><a class="button secondary-button" href="{{ route($kind.'.index') }}">Cancel</a></div>
</form>
@endsection
