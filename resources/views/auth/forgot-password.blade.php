@extends('layouts.auth')
@section('title', 'Reset Password')

@section('brand')
<div class="brand-name">ORGANETT</div>
<div class="brand-sub">Farm Management System</div>
@endsection

@section('extra-style')
.brand { margin-bottom: 1.5rem; }
.card { box-shadow: 0 25px 50px -12px #00000060, 0 0 80px #16a34a0d; }
.card-title { margin-bottom: .5rem; }
label { margin-bottom: .5rem; }
.field { margin-bottom: 1.25rem; }
@endsection

@section('content')
@if (session('status'))
    <div class="flash">{{ session('status') }}</div>
@endif
<div class="card-title">Forgot your password?</div>
<div class="card-desc">Enter your email address and we'll send you a link to reset your password.</div>
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="field">
        <label for="email">Email address</label>
        <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username"
                   placeholder="you@example.com"
                   class="{{ $errors->has('email') ? 'is-error' : '' }}">
        </div>
        @error('email')
            <p class="error-msg">{{ $message }}</p>
        @enderror
    </div>
    <button type="submit" class="btn-primary">Send Reset Link</button>
</form>
@endsection

@section('after-card')
<a href="{{ route('login') }}" class="back-link">← Back to login</a>
@endsection
