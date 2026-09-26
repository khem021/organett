<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.theme-toggle')
    <title>@yield('title') — {{ config('app.name', 'Organett') }}</title>
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
            --text:         #d1fae5;
            --label-color:  #a3c9b0;
            --danger:       #f87171;
            --danger-glow:  #f8717122;
        }
        :root[data-theme="light"] {
            --green-dark:   #f3f4f6; /* light-mode page background, name kept to avoid touching body's var() usage */
            --green-mid:    #14532d;
            --green-accent: #16a34a;
            --green-light:  #15803d;
            --green-glow:   #16a34a22;
            --card-bg:      #ffffff;
            --card-border:  #e5e7eb;
            --input-bg:     #f9fafb;
            --input-border: #e5e7eb;
            --text-muted:   #6b7280;
            --text:         #111827;
            --label-color:  #374151;
            --danger:       #dc2626;
            --danger-glow:  #dc262622;
        }
        :root[data-theme="light"] body { background-image: none; }
        @media (prefers-color-scheme: light) {
            :root:not([data-theme="dark"]) {
                --green-dark:   #f3f4f6;
                --green-mid:    #14532d;
                --green-accent: #16a34a;
                --green-light:  #15803d;
                --green-glow:   #16a34a22;
                --card-bg:      #ffffff;
                --card-border:  #e5e7eb;
                --input-bg:     #f9fafb;
                --input-border: #e5e7eb;
                --text-muted:   #6b7280;
                --text:         #111827;
                --label-color:  #374151;
                --danger:       #dc2626;
                --danger-glow:  #dc262622;
            }
            :root:not([data-theme="dark"]) body { background-image: none; }
        }
        .icon-sun { display: none; }
        .icon-moon { display: inline; }
        :root[data-theme="light"] .icon-sun { display: inline; }
        :root[data-theme="light"] .icon-moon { display: none; }
        @media (prefers-color-scheme: light) {
            :root:not([data-theme="dark"]) .icon-sun { display: inline; }
            :root:not([data-theme="dark"]) .icon-moon { display: none; }
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--green-dark);
            background-image: radial-gradient(ellipse 80% 60% at 50% -10%, #14532d55 0%, transparent 70%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            padding: 1.5rem;
        }
        .wrapper { width: 100%; max-width: @yield('wrapper-width', '420px'); }

        /* Brand */
        .brand { text-align: center; margin-bottom: 1.1rem; }
        .brand a { text-decoration: none; display: inline-flex; flex-direction: column; align-items: center; gap: 0.5rem; }
        .brand-logo { width: 190px; height: 190px; object-fit: contain; filter: drop-shadow(0 0 18px #16a34a55); }
        .brand-name { font-size: 1.5rem; font-weight: 700; color: var(--green-light); letter-spacing: .08em; }
        .brand-sub { font-size: .875rem; color: var(--text-muted); margin-top: .25rem; }

        /* Card */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 0 0 1px #16a34a1a, 0 25px 50px -12px #00000060, 0 0 80px #16a34a0d;
        }

        /* Section divider label (login / register-farm) */
        .section-label {
            font-size: .7rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase;
            color: var(--text-muted); margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: .75rem;
        }
        .section-label::before, .section-label::after { content: ''; flex: 1; height: 1px; background: var(--card-border); }

        /* Card heading text (forgot / reset) */
        .card-title { font-size: 1.125rem; font-weight: 700; color: var(--text); }
        .card-desc { font-size: .875rem; color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5; }
        .hint { font-size: .75rem; color: var(--text-muted); margin-top: .375rem; }

        /* Fields */
        .field { margin-bottom: 1.125rem; }
        .field-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; }
        label { display: block; font-size: 0.8125rem; font-weight: 500; color: var(--label-color); }
        .forgot { font-size: 0.75rem; color: var(--text-muted); text-decoration: none; transition: color .15s; }
        .forgot:hover { color: var(--green-light); }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); pointer-events: none; width: 1rem; height: 1rem;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 0.625rem 0.875rem 0.625rem 2.5rem;
            font-size: 0.875rem;
            font-family: inherit;
            color: var(--text);
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 0.5rem;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        input[type="text"]::placeholder, input[type="email"]::placeholder, input[type="password"]::placeholder { color: #2d5a3d; }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus {
            border-color: var(--green-accent);
            box-shadow: 0 0 0 3px var(--green-glow);
        }
        input.is-error { border-color: var(--danger); box-shadow: 0 0 0 3px var(--danger-glow); }
        .error-msg { margin-top: 0.375rem; font-size: 0.75rem; color: var(--danger); }

        /* Remember me (login) */
        .remember { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; margin-top: 0.25rem; }
        .remember input[type="checkbox"] { width: 1rem; height: 1rem; accent-color: var(--green-accent); border-radius: 0.25rem; cursor: pointer; }
        .remember label { font-size: 0.8125rem; color: var(--text-muted); cursor: pointer; font-weight: 400; }

        /* Field group heading (register-farm) */
        .field-group-label {
            font-size: .65rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
            color: var(--text-muted); margin-bottom: .75rem; margin-top: 1.5rem;
            padding-bottom: .4rem; border-bottom: 1px solid var(--card-border);
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
        .btn-primary:hover { opacity: 0.9; box-shadow: 0 6px 25px #16a34a44; }
        .btn-primary:active { transform: scale(0.98); opacity: 0.85; }

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

        /* Footer links */
        .register-hint, .login-hint { margin-top: 1.5rem; text-align: center; font-size: 0.8125rem; color: var(--text-muted); }
        .register-hint a, .login-hint a { color: var(--green-light); text-decoration: none; font-weight: 600; transition: opacity .15s; }
        .register-hint a:hover, .login-hint a:hover { opacity: 0.8; }
        .back-link { display: block; text-align: center; margin-top: 1.25rem; font-size: .8125rem; color: var(--text-muted); text-decoration: none; transition: color .15s; }
        .back-link:hover { color: var(--green-light); }

        @yield('extra-style')
    </style>
</head>
<body>
    <button onclick="toggleTheme()" title="Toggle light / dark mode" aria-label="Toggle color theme"
            style="position:fixed;top:1rem;right:1rem;background:none;border:1px solid var(--card-border);border-radius:.375rem;color:var(--text-muted);cursor:pointer;padding:.375rem .5rem;display:inline-flex;align-items:center;justify-content:center;font-family:inherit;transition:border-color .15s,color .15s;z-index:10;"
            onmouseover="this.style.borderColor='var(--green-accent)';this.style.color='var(--green-light)'"
            onmouseout="this.style.borderColor='var(--card-border)';this.style.color='var(--text-muted)'">
        <svg class="icon-sun" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
            <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
        </svg>
        <svg class="icon-moon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>
    </button>
    <div class="wrapper">
        <div class="brand">
            @hasSection('brand')
                @yield('brand')
            @else
                <a href="{{ url('/') }}">
                    <img src="{{ asset('logo-mushroom.png') }}" alt="Organett Logo" class="brand-logo">
                </a>
            @endif
        </div>
        <div class="card">
            @yield('content')
        </div>
        @yield('after-card')
    </div>
</body>
</html>
