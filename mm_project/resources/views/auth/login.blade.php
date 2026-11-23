@extends('layouts.membership')
@section('title', 'Staff Login')
@section('page-class', 'login-page')
@section('content')
    <div class="login-theme">@include('partials.theme-toggle')</div>
    <div role="img" aria-label="Membership Management">
        <img class="login-logo logo-light" src="{{ asset('images/brand/logo-horizontal-light.png') }}" alt="" width="300" height="100">
        <img class="login-logo logo-dark" src="{{ asset('images/brand/logo-horizontal-light.png') }}" alt="" width="300" height="100">
    </div>
    <h1>Staff Login</h1>
    <form action="{{ route('login.store') }}" method="POST" class="login-form">
        @csrf
        <div class="field">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email') <p class="error" id="email-error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password') <p class="error" id="password-error">{{ $message }}</p> @enderror
        </div>
        <button class="button" type="submit">Sign in</button>
    </form>
    @if(config('app.demo_login_hint'))
        <details class="demo-login-hint">
            <summary>Professor / demo login</summary>
            <div class="demo-login-content">
                <p>Use this shared administrator account to check the project.</p>
                <dl>
                    <div><dt>Email</dt><dd><code>testing@gmail.com</code></dd></div>
                    <div><dt>Password</dt><dd><code>testinglngto</code></dd></div>
                </dl>
                <p class="muted">Public academic demo. Use fictional data only.</p>
            </div>
        </details>
    @endif
@endsection
