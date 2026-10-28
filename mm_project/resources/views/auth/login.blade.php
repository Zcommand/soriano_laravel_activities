@extends('layouts.membership')
@section('title', 'Staff Login')
@section('page-class', 'login-page')
@section('content')
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
@endsection
