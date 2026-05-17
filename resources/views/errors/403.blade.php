<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Access Denied · Organett</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background: #070f09;
            color: #d1fae5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .wrap {
            text-align: center;
            padding: 2rem;
            max-width: 420px;
        }
        .icon {
            width: 4rem; height: 4rem;
            background: #7f1d1d22;
            border: 1px solid #7f1d1d55;
            border-radius: 1rem;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .code {
            font-size: 4rem;
            font-weight: 700;
            color: #f87171;
            letter-spacing: -.04em;
            line-height: 1;
            margin-bottom: .5rem;
        }
        h1 {
            font-size: 1.125rem;
            font-weight: 700;
            color: #d1fae5;
            margin-bottom: .5rem;
        }
        p {
            font-size: .875rem;
            color: #5a8a6a;
            line-height: 1.6;
            margin-bottom: 1.75rem;
        }
        a {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .5rem 1.25rem;
            background: linear-gradient(135deg, #14532d, #16a34a);
            color: #fff;
            border-radius: .5rem;
            font-size: .875rem;
            font-weight: 600;
            text-decoration: none;
            transition: opacity .15s;
        }
        a:hover { opacity: .85; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div class="code">403</div>
        <h1>Access Restricted</h1>
        <p>This area is for administrators only. Your account does not have permission to view this page. Contact your farm administrator if you need access.</p>
        <a href="{{ url('/dashboard') }}">← Back to Dashboard</a>
    </div>
</body>
</html>
