@extends('layouts.auth')
@section('title', 'Set New Password')

@section('brand')
<div class="brand-name">ORGANETT</div>
<div class="brand-sub">Farm Management System</div>
@endsection

@section('extra-style')
.brand { margin-bottom: 1.5rem; }
.card { box-shadow: 0 25px 50px -12px #00000060, 0 0 80px #16a34a0d; }
.card-title { margin-bottom: 1.5rem; }
label { margin-bottom: .5rem; }
.btn-primary:hover { box-shadow: 0 4px 15px var(--green-glow); }
@endsection

@section('content')
<div class="card-title">Set new password</div>
<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="field">
        <label for="email">Email address</label>
        <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
            <input id="email" type="email" name="email" value="{{ old('email', request('email')) }}"
                   required autofocus autocomplete="username"
                   placeholder="you@example.com"
                   class="{{ $errors->has('email') ? 'is-error' : '' }}">
        </div>
        @error('email')
            <p class="error-msg">{{ $message }}</p>
        @enderror
    </div>
    <div class="field">
        <label for="password">New password</label>
        <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input id="password" type="password" name="password"
                   required autocomplete="new-password"
                   placeholder="Min. 8 characters"
                   class="{{ $errors->has('password') ? 'is-error' : '' }}">
        </div>
        @error('password')
            <p class="error-msg">{{ $message }}</p>
        @enderror
        <p class="hint">At least 8 characters</p>
    </div>
    <div class="field">
        <label for="password_confirmation">Confirm new password</label>
        <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   required autocomplete="new-password"
                   placeholder="Repeat password">
        </div>
    </div>
    <button type="submit" class="btn-primary">Reset Password</button>
</form>
@endsection

@section('after-card')
<a href="{{ route('login') }}" class="back-link">← Back to login</a>
@endsection
