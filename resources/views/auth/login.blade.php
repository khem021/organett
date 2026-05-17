<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Log in — {{ config('app.name', 'Organett') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <!-- Styles / Scripts -->
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
                background-image:
                    radial-gradient(ellipse 80% 60% at 50% -10%, #14532d55 0%, transparent 70%),
                    radial-gradient(ellipse 40% 30% at 80% 90%, #16a34a1a 0%, transparent 60%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                -webkit-font-smoothing: antialiased;
                padding: 1.5rem;
            }

            .wrapper {
                width: 100%;
                max-width: 420px;
            }

            /* Brand */
            .brand {
                text-align: center;
                margin-bottom: 1.1rem;
            }
            .brand a {
                text-decoration: none;
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }
            .brand-logo {
                width: 190px;
                height: 190px;
                object-fit: contain;
                filter: drop-shadow(0 0 18px #16a34a55);
            }
            .brand-sub {
                margin-top: 0.375rem;
                font-size: 0.875rem;
                color: var(--text-muted);
            }

            /* Card */
            .card {
                background: var(--card-bg);
                border: 1px solid var(--card-border);
                border-radius: 1rem;
                padding: 2rem;
                box-shadow:
                    0 0 0 1px #16a34a1a,
                    0 25px 50px -12px #00000060,
                    0 0 80px #16a34a0d;
            }

            /* Divider label */
            .section-label {
                font-size: 0.7rem;
                font-weight: 600;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: var(--text-muted);
                margin-bottom: 1.25rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            .section-label::before,
            .section-label::after {
                content: '';
                flex: 1;
                height: 1px;
                background: var(--card-border);
            }

            /* Form fields */
            .field { margin-bottom: 1.125rem; }

            .field-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 0.5rem;
            }

            label {
                display: block;
                font-size: 0.8125rem;
                font-weight: 500;
                color: #a3c9b0;
            }

            .forgot {
                font-size: 0.75rem;
                color: var(--text-muted);
                text-decoration: none;
                transition: color .15s;
            }
            .forgot:hover { color: var(--green-light); }

            .input-wrap {
                position: relative;
            }
            .input-icon {
                position: absolute;
                left: 0.875rem;
                top: 50%;
                transform: translateY(-50%);
                color: var(--text-muted);
                pointer-events: none;
                width: 1rem;
                height: 1rem;
            }

            input[type="email"],
            input[type="password"] {
                width: 100%;
                padding: 0.625rem 0.875rem 0.625rem 2.5rem;
                font-size: 0.875rem;
                font-family: inherit;
                color: #d1fae5;
                background: var(--input-bg);
                border: 1px solid var(--input-border);
                border-radius: 0.5rem;
                outline: none;
                transition: border-color .15s, box-shadow .15s;
            }
            input[type="email"]::placeholder,
            input[type="password"]::placeholder {
                color: #2d5a3d;
            }
            input[type="email"]:focus,
            input[type="password"]:focus {
                border-color: var(--green-accent);
                box-shadow: 0 0 0 3px var(--green-glow);
            }
            input.is-error {
                border-color: #dc2626;
                box-shadow: 0 0 0 3px #dc262622;
            }

            .error-msg {
                margin-top: 0.375rem;
                font-size: 0.75rem;
                color: #f87171;
            }

            /* Remember me */
            .remember {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 1.5rem;
                margin-top: 0.25rem;
            }
            .remember input[type="checkbox"] {
                width: 1rem;
                height: 1rem;
                accent-color: var(--green-accent);
                border-radius: 0.25rem;
                cursor: pointer;
            }
            .remember label {
                font-size: 0.8125rem;
                color: var(--text-muted);
                cursor: pointer;
                font-weight: 400;
            }

            /* Submit button */
            .btn-primary {
                width: 100%;
                padding: 0.7rem 1rem;
                font-size: 0.9375rem;
                font-weight: 600;
                font-family: inherit;
                color: #fff;
                background: linear-gradient(135deg, var(--green-mid) 0%, var(--green-accent) 100%);
                border: none;
                border-radius: 0.5rem;
                cursor: pointer;
                transition: opacity .15s, box-shadow .15s, transform .1s;
                box-shadow: 0 4px 15px var(--green-glow);
                letter-spacing: 0.01em;
            }
            .btn-primary:hover {
                opacity: 0.9;
                box-shadow: 0 6px 25px #16a34a44;
            }
            .btn-primary:active {
                transform: scale(0.98);
                opacity: 0.85;
            }

            /* Status / flash */
            .flash {
                margin-bottom: 1.25rem;
                padding: 0.75rem 1rem;
                background: #14532d33;
                border: 1px solid #16a34a55;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                color: var(--green-light);
            }

            /* Footer register link */
            .register-hint {
                margin-top: 1.5rem;
                text-align: center;
                font-size: 0.8125rem;
                color: var(--text-muted);
            }
            .register-hint a {
                color: var(--green-light);
                text-decoration: none;
                font-weight: 600;
                transition: opacity .15s;
            }
            .register-hint a:hover { opacity: 0.8; }
        </style>
    </head>
    <body>

        <div class="wrapper">

            <!-- Brand -->
            <div class="brand">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('logo-mushroom.png') }}" alt="Organett Logo" class="brand-logo">
                </a>
            </div>

            <!-- Card -->
            <div class="card">

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
            </div>

            @if (Route::has('register'))
                <p class="register-hint">
                    Don't have an account? <a href="{{ route('register') }}">Create one</a>
                </p>
            @endif

            @if(app()->environment('local'))
                <div style="margin-top:1.25rem;background:var(--card-bg);border:1px dashed var(--card-border);border-radius:.625rem;padding:.875rem 1rem;font-size:.75rem;color:var(--text-muted);">
                    <div style="display:flex;align-items:center;gap:.375rem;margin-bottom:.5rem;font-weight:600;color:#a3c9b0;letter-spacing:.04em;text-transform:uppercase;font-size:.625rem;">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Demo credentials
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:.5rem;">
                        <span>Admin</span><code style="color:var(--green-light);font-family:ui-monospace,Menlo,monospace;">admin@organett.local / admin123</code>
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:.5rem;margin-top:.25rem;">
                        <span>Staff</span><code style="color:var(--green-light);font-family:ui-monospace,Menlo,monospace;">staff@organett.local / staff123</code>
                    </div>
                </div>
            @endif

        </div>

        <script>
            // Auto-fill demo credentials on click
            document.querySelectorAll('code').forEach(c => {
                c.style.cursor = 'pointer';
                c.title = 'Click to fill';
                c.addEventListener('click', () => {
                    const [email, pwd] = c.textContent.split(' / ');
                    document.getElementById('email').value = email.trim();
                    document.getElementById('password').value = pwd.trim();
                });
            });
        </script>

    </body>
</html>
