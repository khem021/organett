@extends('layouts.auth')
@section('title', 'Log in')

@section('extra-style')
body {
    background-image:
        radial-gradient(ellipse 80% 60% at 50% -10%, #14532d55 0%, transparent 70%),
        radial-gradient(ellipse 40% 30% at 80% 90%, #16a34a1a 0%, transparent 60%);
}
@endsection

@section('content')
@if (session('status'))
    <div class="flash">{{ session('status') }}</div>
@endif

<div class="section-label">credentials</div>

<form method="POST" action="{{ route('login') }}">
    @csrf

    <!-- Email -->
    <div class="field">
        <label for="email">Email address</label>
        <div class="input-wrap" style="margin-top:.5rem">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="you@example.com"
                class="{{ $errors->has('email') ? 'is-error' : '' }}"
            >
        </div>
        @error('email')
            <p class="error-msg">{{ $message }}</p>
        @enderror
    </div>

    <!-- Password -->
    <div class="field">
        <div class="field-header">
            <label for="password">Password</label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot">Forgot password?</a>
            @endif
        </div>
        <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="{{ $errors->has('password') ? 'is-error' : '' }}"
            >
        </div>
        @error('password')
            <p class="error-msg">{{ $message }}</p>
        @enderror
    </div>

    <!-- Remember me -->
    <div class="remember">
        <input id="remember_me" type="checkbox" name="remember">
        <label for="remember_me">Remember me for 30 days</label>
    </div>

    <!-- Submit -->
    <button type="submit" class="btn-primary">
        Sign in to Organett
    </button>
</form>
@endsection

@section('after-card')
<p class="register-hint">
    New mushroom farm? <a href="{{ route('farm.register') }}">Register your farm &rarr;</a>
</p>
@endsection
