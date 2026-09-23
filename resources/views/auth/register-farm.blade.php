@extends('layouts.auth')
@section('title', 'Register Your Farm')
@section('wrapper-width', '460px')

@section('extra-style')
body {
    background-image:
        radial-gradient(ellipse 80% 60% at 50% -10%, #14532d55 0%, transparent 70%),
        radial-gradient(ellipse 40% 30% at 80% 90%, #16a34a1a 0%, transparent 60%);
}
.brand-logo { width: 120px; height: 120px; }
label { margin-bottom: .4rem; }
.btn-primary { margin-top: .5rem; }
@endsection

@section('content')
<div class="section-label">register your farm</div>

<form method="POST" action="{{ route('farm.register') }}">
    @csrf

    <div class="field-group-label">Farm Details</div>

    <div class="field">
        <label for="farm_name">Farm Name</label>
        <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <input id="farm_name" type="text" name="farm_name" value="{{ old('farm_name') }}" required autofocus placeholder="e.g. Green Hills Mushroom Farm" class="{{ $errors->has('farm_name') ? 'is-error' : '' }}">
        </div>
        @error('farm_name')<p class="error-msg">{{ $message }}</p>@enderror
    </div>

    <div class="field-group-label">Admin Account</div>

    <div class="field">
        <label for="full_name">Full Name</label>
        <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            <input id="full_name" type="text" name="full_name" value="{{ old('full_name') }}" required placeholder="Your full name" class="{{ $errors->has('full_name') ? 'is-error' : '' }}">
        </div>
        @error('full_name')<p class="error-msg">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="email">Email Address</label>
        <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="you@example.com" class="{{ $errors->has('email') ? 'is-error' : '' }}">
        </div>
        @error('email')<p class="error-msg">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Min. 8 characters" class="{{ $errors->has('password') ? 'is-error' : '' }}">
        </div>
        @error('password')<p class="error-msg">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="password_confirmation">Confirm Password</label>
        <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat your password">
        </div>
    </div>

    <button type="submit" class="btn-primary">Create Farm &amp; Account</button>
</form>
@endsection

@section('after-card')
<p class="login-hint">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
@endsection
