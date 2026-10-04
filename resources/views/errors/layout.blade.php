<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>@yield('code') — @yield('title') · Organett</title>
    <style>
        /* Standalone on purpose: an error page must not depend on the database, the session or the asset build. */
        :root {
            --bg: #070f09; --card: #0d1a10; --border: #1a3324; --text: #d1fae5; --muted: #8fb9a0;
            --accent: #f87171; --accent-bg: #7f1d1d22; --accent-border: #7f1d1d66;
            --btn-from: #14532d; --btn-to: #15803d; --btn-text: #ffffff; --focus: #86efac;
        }
        @media (prefers-color-scheme: light) {
            :root {
                --bg: #f3faf5; --card: #ffffff; --border: #cfe6d7; --text: #0f2a1a; --muted: #4b6b58;
                --accent: #b91c1c; --accent-bg: #fee2e222; --accent-border: #fecaca;
                --btn-from: #166534; --btn-to: #15803d; --btn-text: #ffffff; --focus: #166534;
            }
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--bg); color: var(--text);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem;
        }
        main { text-align: center; max-width: 26rem; width: 100%; }
        .icon {
            width: 4rem; height: 4rem; margin: 0 auto 1.5rem; border-radius: 1rem;
            display: flex; align-items: center; justify-content: center;
            background: var(--accent-bg); border: 1px solid var(--accent-border); color: var(--accent);
        }
        .code { font-size: 3.5rem; font-weight: 700; letter-spacing: -.04em; line-height: 1; color: var(--accent); margin-bottom: .5rem; }
        h1 { font-size: 1.25rem; font-weight: 700; margin-bottom: .5rem; }
        p { font-size: .9375rem; color: var(--muted); line-height: 1.6; margin-bottom: 1.75rem; }
        .actions { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: .5rem; padding: .625rem 1.25rem; border-radius: .5rem;
            font-size: .875rem; font-weight: 600; text-decoration: none; border: 1px solid transparent;
        }
        .btn-primary { background: linear-gradient(135deg, var(--btn-from), var(--btn-to)); color: var(--btn-text); }
        .btn-secondary { background: transparent; color: var(--text); border-color: var(--border); }
        .btn:hover { opacity: .9; }
        .btn:focus-visible { outline: 3px solid var(--focus); outline-offset: 2px; }
    </style>
</head>
<body>
    <main>
        <div class="icon" aria-hidden="true">
            @yield('icon')
        </div>
        <div class="code" aria-hidden="true">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <a class="btn btn-primary" href="{{ url('/dashboard') }}">&larr; Back to dashboard</a>
            @endif
        </div>
    </main>
</body>
</html>
