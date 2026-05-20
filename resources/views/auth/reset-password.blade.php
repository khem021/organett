<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password — {{ config('app.name', 'Organett') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root {
            --green-dark:   #0d2818;
            --green-mid:    #14532d;
            --green-accent: #16a34a;
            --green-light:  #4ade80;
            --green-glow:   #16a34a33;
            --card-bg:      #0f1f14;
            --card-border:  #1e3a27;
            --input-bg:     #0a1a0e;
            --input-border: #1e3a27;
            --text-muted:   #6b9a7d;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--green-dark);
            background-image: radial-gradient(ellipse 80% 60% at 50% -10%, #14532d55 0%, transparent 70%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            padding: 1.5rem;
        }
        .wrapper { width: 100%; max-width: 420px; }
        .brand { text-align: center; margin-bottom: 1.5rem; }
        .brand-name { font-size: 1.5rem; font-weight: 700; color: var(--green-light); letter-spacing: .08em; }
        .brand-sub { font-size: .875rem; color: var(--text-muted); margin-top: .25rem; }
        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px #00000060, 0 0 80px #16a34a0d;
        }
        .card-title { font-size: 1.125rem; font-weight: 700; color: #d1fae5; margin-bottom: 1.5rem; }
        .field { margin-bottom: 1.125rem; }
        label { display: block; font-size: .8125rem; font-weight: 500; color: #a3c9b0; margin-bottom: .5rem; }
        .input-wrap { position: relative; }
        .input-icon { position: absolute; left: .875rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; width: 1rem; height: 1rem; }
        input[type="email"], input[type="password"] {
            width: 100%; padding: .625rem .875rem .625rem 2.5rem;
            font-size: .875rem; font-family: inherit; color: #d1fae5;
            background: var(--input-bg); border: 1px solid var(--input-border);
            border-radius: .5rem; outline: none; transition: border-color .15s, box-shadow .15s;
        }
        input[type="email"]:focus, input[type="password"]:focus { border-color: var(--green-accent); box-shadow: 0 0 0 3px var(--green-glow); }
        input.is-error { border-color: #dc2626; box-shadow: 0 0 0 3px #dc262622; }
        .error-msg { margin-top: .375rem; font-size: .75rem; color: #f87171; }
        .hint { font-size: .75rem; color: var(--text-muted); margin-top: .375rem; }
        .btn-primary {
            width: 100%; padding: .7rem 1rem; font-size: .9375rem; font-weight: 600;
            font-family: inherit; color: #fff;
            background: linear-gradient(135deg, var(--green-mid) 0%, var(--green-accent) 100%);
            border: none; border-radius: .5rem; cursor: pointer;
            transition: opacity .15s, box-shadow .15s;
            box-shadow: 0 4px 15px var(--green-glow);
        }
        .btn-primary:hover { opacity: .9; }
        .back-link { display: block; text-align: center; margin-top: 1.25rem; font-size: .8125rem; color: var(--text-muted); text-decoration: none; transition: color .15s; }
        .back-link:hover { color: var(--green-light); }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="brand">
            <div class="brand-name">ORGANETT</div>
            <div class="brand-sub">Farm Management System</div>
        </div>
        <div class="card">
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
        </div>
        <a href="{{ route('login') }}" class="back-link">← Back to login</a>
    </div>
</body>
</html>
