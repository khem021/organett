<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'Organett') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:           #070f09;
            --sidebar-bg:   #0a1510;
            --sidebar-w:    240px;
            --card-bg:      #0d1f12;
            --card-border:  #1a3322;
            --green-mid:    #14532d;
            --green-accent: #16a34a;
            --green-light:  #4ade80;
            --green-glow:   #16a34a30;
            --text:         #d1fae5;
            --text-muted:   #5a8a6a;
            --text-dim:     #3a6a4a;
            --danger:       #f87171;
            --warning:      #fbbf24;
            --info:         #38bdf8;
        }

        html, body { height: 100%; }

        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--card-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            text-decoration: none;
        }
        .brand-icon {
            width: 2rem; height: 2rem;
            background: linear-gradient(135deg, var(--green-mid), var(--green-accent));
            border-radius: 0.5rem;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 12px var(--green-glow);
            flex-shrink: 0;
        }
        .brand-icon svg { color: #fff; }
        .brand-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--green-light);
            letter-spacing: 0.08em;
        }
        .brand-tag {
            font-size: 0.6rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 0.75rem 0.625rem;
            overflow-y: auto;
        }

        .nav-section {
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-dim);
            padding: 0.75rem 0.625rem 0.375rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
            transition: background .15s, color .15s;
            margin-bottom: 2px;
            position: relative;
        }
        .nav-item svg { flex-shrink: 0; opacity: 0.7; }
        .nav-item:hover {
            background: #16a34a14;
            color: var(--text);
        }
        .nav-item:hover svg { opacity: 1; }
        .nav-item.active {
            background: #16a34a1e;
            color: var(--green-light);
        }
        .nav-item.active svg { opacity: 1; color: var(--green-light); }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 20%; bottom: 20%;
            width: 3px;
            background: var(--green-accent);
            border-radius: 0 2px 2px 0;
        }

        .nav-badge {
            margin-left: auto;
            font-size: 0.6875rem;
            font-weight: 600;
            background: var(--green-mid);
            color: var(--green-light);
            padding: 0.1rem 0.4rem;
            border-radius: 999px;
        }
        .nav-badge.danger { background: #7f1d1d; color: var(--danger); }

        /* Sidebar footer */
        .sidebar-footer {
            padding: 0.875rem 0.625rem;
            border-top: 1px solid var(--card-border);
        }
        .user-row {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
        }
        .avatar {
            width: 2rem; height: 2rem;
            background: linear-gradient(135deg, var(--green-mid), var(--green-accent));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }
        .user-info { flex: 1; min-width: 0; }
        .user-name {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-role {
            font-size: 0.6875rem;
            color: var(--text-muted);
        }
        .logout-btn {
            background: none;
            border: none;
            color: var(--text-dim);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.375rem;
            display: flex;
            transition: color .15s;
        }
        .logout-btn:hover { color: var(--danger); }

        /* ── Main ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            height: 56px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            padding: 0 1.75rem;
            gap: 1rem;
            position: sticky;
            top: 0;
            background: var(--bg);
            z-index: 50;
        }
        .topbar-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
            flex: 1;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .topbar-date {
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        .status-dot {
            width: 8px; height: 8px;
            background: var(--green-accent);
            border-radius: 50%;
            box-shadow: 0 0 6px var(--green-accent);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .page-content {
            padding: 1.75rem;
            flex: 1;
        }

        /* ── Cards ── */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.75rem;
            padding: 1.25rem;
        }
        .card-title {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card-title svg { opacity: 0.7; }

        /* ── Grid helpers ── */
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .grid-main { display: grid; grid-template-columns: 1fr 320px; gap: 1rem; }

        .gap-top { margin-top: 1rem; }
        .gap-top-lg { margin-top: 1.5rem; }

        /* ── Stat card ── */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.75rem;
            padding: 1.125rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-muted);
        }
        .stat-icon {
            width: 2rem; height: 2rem;
            border-radius: 0.5rem;
            display: flex; align-items: center; justify-content: center;
        }
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.02em;
            line-height: 1;
        }
        .stat-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .stat-sub span { color: var(--green-light); font-weight: 600; }
        .stat-sub span.warn { color: var(--warning); }
        .stat-sub span.danger { color: var(--danger); }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; }
        thead th {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-dim);
            text-align: left;
            padding: 0 0.75rem 0.625rem;
            border-bottom: 1px solid var(--card-border);
        }
        tbody td {
            font-size: 0.8125rem;
            color: var(--text-muted);
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid #0f2018;
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #16a34a08; }
        td.text-main { color: var(--text); font-weight: 500; }

        /* ── Badges ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
        }
        .badge-green   { background: #14532d33; color: var(--green-light); }
        .badge-yellow  { background: #78350f33; color: var(--warning); }
        .badge-red     { background: #7f1d1d33; color: var(--danger); }
        .badge-blue    { background: #0c4a6e33; color: var(--info); }
        .badge-gray    { background: #1a3322; color: var(--text-muted); }
        .badge-dot {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: currentColor;
        }

        /* ── Alert list ── */
        .alert-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #0f2018;
        }
        .alert-item:last-child { border-bottom: none; }
        .alert-icon {
            width: 1.75rem; height: 1.75rem;
            border-radius: 0.375rem;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .alert-body { flex: 1; }
        .alert-title {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.125rem;
        }
        .alert-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .alert-time {
            font-size: 0.6875rem;
            color: var(--text-dim);
            white-space: nowrap;
            margin-top: 2px;
        }

        /* ── Progress bar ── */
        .progress-row { display: flex; flex-direction: column; gap: 0.375rem; margin-bottom: 0.75rem; }
        .progress-label { display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); }
        .progress-label span:last-child { color: var(--text); font-weight: 600; }
        .progress-track { height: 5px; background: var(--card-border); border-radius: 999px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--green-mid), var(--green-accent)); }

        /* ── Forms & Modals ── */
        dialog { background:var(--card-bg); border:1px solid var(--card-border); border-radius:.875rem; padding:1.75rem; color:var(--text); max-width:520px; width:100%; box-shadow:0 25px 50px -12px #00000080; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); margin:0; }
        dialog::backdrop { background:rgba(0,0,0,.75); backdrop-filter:blur(4px); }
        .modal-title { font-size:1rem; font-weight:700; margin-bottom:1.25rem; color:var(--text); display:flex; align-items:center; justify-content:space-between; }
        .modal-close { background:none; border:none; color:var(--text-muted); cursor:pointer; padding:.25rem; border-radius:.375rem; font-size:1.25rem; line-height:1; }
        .modal-close:hover { color:var(--text); }
        .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.875rem; }
        .form-group { display:flex; flex-direction:column; gap:.375rem; margin-bottom:.875rem; }
        .form-group:last-child { margin-bottom:0; }
        .form-label { font-size:.8125rem; font-weight:500; color:#a3c9b0; }
        .form-input,.form-select,.form-textarea { width:100%; padding:.5625rem .875rem; font-size:.875rem; font-family:inherit; color:#d1fae5; background:#0a1a0e; border:1px solid var(--card-border); border-radius:.5rem; outline:none; transition:border-color .15s; }
        .form-input:focus,.form-select:focus,.form-textarea:focus { border-color:var(--green-accent); }
        .form-select option { background:var(--card-bg); }
        .form-textarea { resize:vertical; min-height:70px; }
        .form-actions { display:flex; gap:.75rem; justify-content:flex-end; margin-top:1.25rem; padding-top:1.25rem; border-top:1px solid var(--card-border); }
        .btn-primary { padding:.5rem 1rem; font-size:.875rem; font-weight:600; font-family:inherit; color:#fff; background:linear-gradient(135deg,#14532d,#16a34a); border:none; border-radius:.5rem; cursor:pointer; transition:opacity .15s; }
        .btn-primary:hover { opacity:.88; }
        .btn-secondary { padding:.5rem 1rem; font-size:.875rem; font-weight:600; font-family:inherit; color:var(--text-muted); background:transparent; border:1px solid var(--card-border); border-radius:.5rem; cursor:pointer; transition:all .15s; }
        .btn-secondary:hover { border-color:var(--text-muted); color:var(--text); }
        .page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:1.5rem; }
        .page-header-left h2 { font-size:1.125rem; font-weight:700; color:var(--text); }
        .page-header-left p { font-size:.8125rem; color:var(--text-muted); margin-top:.25rem; }
        .page-header-right { display:flex; gap:.625rem; align-items:center; }
        .filter-bar { display:flex; gap:.75rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap; }
        .filter-input,.filter-select { padding:.4375rem .75rem; font-size:.8125rem; font-family:inherit; color:var(--text); background:var(--card-bg); border:1px solid var(--card-border); border-radius:.5rem; outline:none; transition:border-color .15s; }
        .filter-input { min-width:200px; }
        .filter-input::placeholder { color:var(--text-dim); }
        .filter-input:focus,.filter-select:focus { border-color:var(--green-accent); }
        .filter-select option { background:var(--card-bg); }
        .btn-sm { display:inline-flex; align-items:center; gap:.3rem; padding:.275rem .625rem; font-size:.7rem; font-weight:600; font-family:inherit; border-radius:.375rem; cursor:pointer; border:1px solid transparent; transition:all .15s; text-decoration:none; }
        .btn-sm-green { background:#14532d22; color:var(--green-light); border-color:#14532d55; }
        .btn-sm-green:hover { background:#14532d44; }
        .btn-sm-blue { background:#0c4a6e22; color:#38bdf8; border-color:#0c4a6e55; }
        .btn-sm-blue:hover { background:#0c4a6e44; }
        .btn-sm-red { background:#7f1d1d22; color:var(--danger); border-color:#7f1d1d55; }
        .btn-sm-red:hover { background:#7f1d1d44; }
        .btn-sm-yellow { background:#78350f22; color:var(--warning); border-color:#78350f55; }
        .btn-sm-yellow:hover { background:#78350f44; }
        .flash { padding:.75rem 1rem; border-radius:.5rem; font-size:.875rem; margin-bottom:1.25rem; }
        .flash-success { background:#14532d22; border:1px solid #16a34a44; color:var(--green-light); }
        .flash-error   { background:#7f1d1d22; border:1px solid #dc262644; color:var(--danger); }
        .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); font-size:.875rem; }

        /* ── Toast notifications ── */
        .toast-container {
            position: fixed;
            top: 1rem; right: 1rem;
            z-index: 1000;
            display: flex; flex-direction: column;
            gap: .5rem;
            pointer-events: none;
        }
        .toast {
            min-width: 280px; max-width: 380px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: .625rem;
            padding: .75rem 1rem;
            display: flex; align-items: flex-start; gap: .625rem;
            box-shadow: 0 10px 30px #00000080, 0 0 0 1px #16a34a14;
            pointer-events: auto;
            animation: toastIn .25s cubic-bezier(.2,.9,.3,1.2) both;
            position: relative; overflow: hidden;
        }
        .toast.exit { animation: toastOut .2s ease-in both; }
        .toast.success { border-left: 3px solid var(--green-accent); }
        .toast.error   { border-left: 3px solid var(--danger); }
        .toast-icon {
            width: 1.25rem; height: 1.25rem;
            flex-shrink: 0; margin-top: 1px;
        }
        .toast.success .toast-icon { color: var(--green-light); }
        .toast.error .toast-icon   { color: var(--danger); }
        .toast-body { flex: 1; font-size: .8125rem; color: var(--text); line-height: 1.4; }
        .toast-close {
            background: none; border: none; cursor: pointer;
            color: var(--text-dim); padding: 0; margin-left: .25rem;
            font-size: 1rem; line-height: 1;
            transition: color .15s;
        }
        .toast-close:hover { color: var(--text); }
        .toast-bar {
            position: absolute; bottom: 0; left: 0;
            height: 2px; background: var(--green-accent);
            animation: toastBar 4s linear forwards;
        }
        .toast.error .toast-bar { background: var(--danger); }
        @keyframes toastIn  { from { opacity: 0; transform: translateX(20px) scale(.95); } to { opacity: 1; transform: none; } }
        @keyframes toastOut { to   { opacity: 0; transform: translateX(20px) scale(.95); } }
        @keyframes toastBar { from { width: 100%; } to { width: 0; } }

        /* ── Mobile menu toggle ── */
        .mobile-toggle {
            display: none;
            background: none; border: 1px solid var(--card-border);
            border-radius: .375rem;
            color: var(--text);
            cursor: pointer;
            padding: .375rem .5rem;
            margin-right: .5rem;
            transition: border-color .15s;
        }
        .mobile-toggle:hover { border-color: var(--green-accent); }
        .sidebar-backdrop {
            display: none;
            position: fixed; inset: 0;
            background: #000a;
            backdrop-filter: blur(2px);
            z-index: 99;
        }
        .sidebar-backdrop.show { display: block; animation: fadeIn .2s ease both; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* ── Loading state on buttons ── */
        .btn-primary.is-loading, .btn-secondary.is-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        .btn-primary.is-loading::after,
        .btn-secondary.is-loading::after {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            width: 14px; height: 14px;
            margin: -7px 0 0 -7px;
            border: 2px solid #ffffff44;
            border-top-color: #fff;
            border-radius: 50%;
            animation: btnSpin .6s linear infinite;
        }
        .btn-secondary.is-loading::after { border-color: #ffffff22; border-top-color: var(--text); }
        @keyframes btnSpin { to { transform: rotate(360deg); } }

        /* ── Smooth nav hover transition ── */
        .nav-item { transition: background .18s ease, color .18s ease, padding-left .18s ease; }
        .nav-item:hover { padding-left: 1rem; }
        .nav-item.active:hover { padding-left: .75rem; }

        /* ── Card hover lift ── */
        .stat-card { transition: transform .2s ease, border-color .2s ease; }
        .stat-card:hover { transform: translateY(-2px); border-color: #1e4a30; }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
            .grid-main { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform .25s ease;
                box-shadow: 8px 0 24px #000a;
            }
            .sidebar.open { transform: none; }
            .main { margin-left: 0; }
            .mobile-toggle { display: inline-flex; align-items: center; }
            .topbar { padding: 0 1rem; }
            .topbar-date { display: none; }
            .page-content { padding: 1rem; }
            .grid-3 { grid-template-columns: 1fr; }
            .grid-2 { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; gap: .75rem; align-items: stretch; }
            .page-header-right { justify-content: flex-end; }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .filter-input { min-width: 0; width: 100%; }
            table { font-size: .75rem; }
            tbody td, thead th { padding: .5rem .375rem; }
            dialog { width: 95%; max-width: 95%; padding: 1.25rem; }
            .form-grid-2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .grid-4 { grid-template-columns: 1fr 1fr; gap: .625rem; }
            .stat-value { font-size: 1.375rem; }
            .stat-card { padding: .875rem 1rem; }
        }

        /* ── Keyboard shortcut hint (kbd) ── */
        .kbd-hint {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            font-size: .6875rem;
            font-weight: 500;
            color: var(--text-dim);
            padding: .125rem .375rem;
            background: #0a1510;
            border: 1px solid var(--card-border);
            border-radius: .25rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        /* ── Scroll-to-top floating button ── */
        .scroll-top {
            position: fixed;
            bottom: 1.25rem; right: 1.25rem;
            width: 2.5rem; height: 2.5rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 50%;
            display: none;
            align-items: center; justify-content: center;
            cursor: pointer;
            color: var(--green-light);
            box-shadow: 0 8px 24px #000a, 0 0 0 1px #16a34a22;
            transition: transform .15s, border-color .15s;
            z-index: 90;
        }
        .scroll-top:hover { transform: translateY(-2px); border-color: var(--green-accent); }
        .scroll-top.show { display: inline-flex; animation: fadeIn .2s both; }
        @media (max-width: 480px) { .scroll-top { bottom: .875rem; right: .875rem; } }

        /* ── Better focus states ── */
        button:focus-visible, a:focus-visible {
            outline: 2px solid var(--green-accent);
            outline-offset: 2px;
            border-radius: .375rem;
        }
        .nav-item:focus-visible { outline-offset: 0; }
        input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: none;
        }

        /* ── Table row click affordance ── */
        tbody tr { transition: background .12s ease; }
        tbody tr:hover td:first-child { color: var(--green-light); }

        /* ── Selection color ── */
        ::selection { background: var(--green-accent); color: #fff; }

        /* Show shortcut label on wider screens */
        @media (min-width: 900px) {
            .topbar-shortcut-label { display: inline !important; }
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Mobile sidebar backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <div class="brand-name">ORGANETT</div>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-section">Main</div>

            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('batches.index') }}" class="nav-item {{ request()->routeIs('batches*') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                    <polyline points="2 17 12 22 22 17"/>
                    <polyline points="2 12 12 17 22 12"/>
                </svg>
                Batches
            </a>

            <a href="{{ route('harvest.index') }}" class="nav-item {{ request()->routeIs('harvest*') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Harvest Logs
            </a>

            <div class="nav-section">Operations</div>

            <a href="{{ route('inventory.index') }}" class="nav-item {{ request()->routeIs('inventory*') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                </svg>
                Inventory
                @if(($navLowStock ?? 0) > 0)
                    <span class="nav-badge danger">{{ $navLowStock }}</span>
                @endif
            </a>

            <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers*') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Customers
            </a>

            <a href="{{ route('orders.index') }}" class="nav-item {{ request()->routeIs('orders*') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13"/>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                    <circle cx="5.5" cy="18.5" r="2.5"/>
                    <circle cx="18.5" cy="18.5" r="2.5"/>
                </svg>
                Orders
                @if(($navPendingOrders ?? 0) > 0)
                    <span class="nav-badge">{{ $navPendingOrders }}</span>
                @endif
            </a>

            <div class="nav-section">Analytics</div>

            <a href="{{ route('reports') }}" class="nav-item {{ request()->routeIs('reports*') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                Reports
            </a>

            @if(Auth::check() && Auth::user()->role === 'admin')
            <div class="nav-section" style="margin-top:.5rem;">Admin</div>

            <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users*') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                User Management
            </a>

            <a href="{{ route('settings') }}" class="nav-item {{ request()->routeIs('settings*') ? 'active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                Settings
            </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="user-row">
                @if(Auth::user()->profile_photo)
                    <img src="{{ Storage::url(Auth::user()->profile_photo) }}" alt="avatar"
                         style="width:2rem;height:2rem;border-radius:50%;object-fit:cover;flex-shrink:0;border:1px solid var(--card-border);">
                @else
                    <div class="avatar">{{ strtoupper(substr(Auth::user()->full_name ?? 'A', 0, 1)) }}</div>
                @endif
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->full_name ?? 'Admin' }}</div>
                    <div class="user-role">{{ Auth::user()->role === 'admin' ? 'Administrator' : 'Farm Staff' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn" title="Log out">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <div class="main">
        <header class="topbar">
            <button class="mobile-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
            <div class="topbar-right">
                <button onclick="document.getElementById('shortcutsDialog').showModal()"
                        title="Keyboard shortcuts"
                        style="background:none;border:1px solid var(--card-border);border-radius:.375rem;color:var(--text-muted);cursor:pointer;padding:.25rem .5rem;display:inline-flex;align-items:center;gap:.375rem;font-size:.6875rem;font-family:inherit;transition:border-color .15s,color .15s;"
                        onmouseover="this.style.borderColor='var(--green-accent)';this.style.color='var(--text)'"
                        onmouseout="this.style.borderColor='var(--card-border)';this.style.color='var(--text-muted)'">
                    <span class="kbd-hint" style="border:none;background:transparent;padding:0;color:inherit;">?</span>
                    <span style="display:none;" class="topbar-shortcut-label">Shortcuts</span>
                </button>
                <span class="topbar-date">{{ now()->format('D, M j Y') }}</span>
                <div style="display:flex;align-items:center;gap:.375rem;font-size:.75rem;color:var(--text-muted);">
                    <div class="status-dot"></div> Live
                </div>
            </div>
        </header>

        <div class="page-content">
            @yield('content')
        </div>
    </div>

    {{-- Toast notifications --}}
    <div class="toast-container" id="toastContainer">
        @if(session('success'))
            <div class="toast success" data-auto="4000">
                <svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <div class="toast-body">{{ session('success') }}</div>
                <button class="toast-close" onclick="dismissToast(this.parentElement)">×</button>
                <div class="toast-bar"></div>
            </div>
        @endif
        @if(session('error'))
            <div class="toast error" data-auto="5000">
                <svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <div class="toast-body">{{ session('error') }}</div>
                <button class="toast-close" onclick="dismissToast(this.parentElement)">×</button>
                <div class="toast-bar"></div>
            </div>
        @endif
        @if($errors->any() && !session('error'))
            <div class="toast error" data-auto="6000">
                <svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <div class="toast-body">
                    <strong style="display:block;margin-bottom:.125rem;">Please fix the following:</strong>
                    @foreach($errors->all() as $err)
                        <div style="font-size:.75rem;color:var(--text-muted);">• {{ $err }}</div>
                    @endforeach
                </div>
                <button class="toast-close" onclick="dismissToast(this.parentElement)">×</button>
                <div class="toast-bar"></div>
            </div>
        @endif
    </div>

    {{-- Scroll-to-top button --}}
    <button class="scroll-top" id="scrollTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Back to top">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"/>
        </svg>
    </button>

    <script>
        // ── Sidebar toggle (mobile) ────────────────────────────────────────
        function toggleSidebar() {
            const sb = document.getElementById('sidebar');
            const bd = document.getElementById('sidebarBackdrop');
            sb.classList.toggle('open');
            bd.classList.toggle('show');
        }
        // Close mobile sidebar when a nav link is tapped
        document.querySelectorAll('.sidebar .nav-item').forEach(el => {
            el.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('open');
                    document.getElementById('sidebarBackdrop').classList.remove('show');
                }
            });
        });

        // ── Toast auto-dismiss ──────────────────────────────────────────────
        function dismissToast(el) {
            el.classList.add('exit');
            el.addEventListener('animationend', () => el.remove(), { once: true });
        }
        document.querySelectorAll('.toast[data-auto]').forEach(t => {
            const ms = parseInt(t.dataset.auto, 10) || 4000;
            const bar = t.querySelector('.toast-bar');
            if (bar) bar.style.animationDuration = ms + 'ms';
            setTimeout(() => dismissToast(t), ms);
        });

        // ── Button loading state on form submit ────────────────────────────
        document.addEventListener('submit', e => {
            const form = e.target;
            // Skip if this is an unconfirmed delete/confirm form — loading goes on after confirm
            const isDelete = (form.querySelector('input[name="_method"]')?.value || '').toUpperCase() === 'DELETE';
            const needsConfirm = isDelete || form.dataset.confirm;
            if (needsConfirm && !form.dataset.confirmed) return;

            const btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.classList.add('is-loading');
                btn.disabled = true;
                setTimeout(() => {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                }, 8000);
            }
        });

        // ── ESC closes any open <dialog> ────────────────────────────────────
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('dialog[open]').forEach(d => d.close());
            }
        });

        // ── Click outside dialog to close ───────────────────────────────────
        document.querySelectorAll('dialog').forEach(d => {
            d.addEventListener('click', e => {
                const r = d.getBoundingClientRect();
                if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) d.close();
            });
        });

        // ── Confirm-before-delete for any form with [data-confirm] or DELETE method ─
        document.addEventListener('submit', e => {
            const form = e.target;
            const isDelete = (form.querySelector('input[name="_method"]')?.value || '').toUpperCase() === 'DELETE';
            const customMsg = form.dataset.confirm;
            if ((isDelete || customMsg) && !form.dataset.confirmed) {
                e.preventDefault();
                const msg = customMsg || 'Are you sure you want to delete this? This action cannot be undone.';
                showConfirm(msg).then(ok => {
                    if (ok) {
                        form.dataset.confirmed = '1';
                        form.requestSubmit ? form.requestSubmit() : form.submit();
                    }
                });
            }
        }, true);

        function showConfirm(message) {
            return new Promise(resolve => {
                const d = document.getElementById('confirmDialog');
                d.querySelector('.confirm-msg').textContent = message;
                const yes = d.querySelector('.confirm-yes');
                const no  = d.querySelector('.confirm-no');
                const cleanup = (val) => {
                    yes.removeEventListener('click', onYes);
                    no.removeEventListener('click', onNo);
                    d.close();
                    resolve(val);
                };
                const onYes = () => cleanup(true);
                const onNo  = () => cleanup(false);
                yes.addEventListener('click', onYes);
                no.addEventListener('click', onNo);
                d.showModal();
            });
        }

        // ── Scroll-to-top visibility ────────────────────────────────────────
        const scrollBtn = document.getElementById('scrollTopBtn');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 320) scrollBtn.classList.add('show');
            else scrollBtn.classList.remove('show');
        }, { passive: true });

        // ── Keyboard shortcuts (g+key for navigation) ───────────────────────
        const navMap = {
            'd': @json(route('dashboard')),
            'b': @json(route('batches.index')),
            'h': @json(route('harvest.index')),
            'i': @json(route('inventory.index')),
            'c': @json(route('customers.index')),
            'o': @json(route('orders.index')),
            'r': @json(route('reports')),
        };
        let pendingG = false, pendingTimer = null;
        document.addEventListener('keydown', e => {
            // Skip if typing in input/textarea/contenteditable
            const tag = (e.target.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
            if (e.ctrlKey || e.metaKey || e.altKey) return;

            if (e.key === 'g') {
                pendingG = true;
                clearTimeout(pendingTimer);
                pendingTimer = setTimeout(() => pendingG = false, 1200);
                return;
            }
            if (pendingG && navMap[e.key]) {
                e.preventDefault();
                window.location = navMap[e.key];
                pendingG = false;
            }
            // ? = show shortcuts
            if (e.key === '?') {
                e.preventDefault();
                document.getElementById('shortcutsDialog')?.showModal();
            }
        });
    </script>

    {{-- Confirm dialog (used for destructive actions) --}}
    <dialog id="confirmDialog">
        <div style="display:flex;align-items:flex-start;gap:.875rem;margin-bottom:1.25rem;">
            <div style="width:2.5rem;height:2.5rem;flex-shrink:0;background:#7f1d1d22;border:1px solid #7f1d1d55;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div>
                <div style="font-size:1rem;font-weight:700;color:var(--text);margin-bottom:.375rem;">Confirm action</div>
                <p class="confirm-msg" style="font-size:.875rem;color:var(--text-muted);line-height:1.5;"></p>
            </div>
        </div>
        <div class="form-actions" style="margin-top:0;padding-top:1rem;">
            <button type="button" class="btn-secondary confirm-no">Cancel</button>
            <button type="button" class="btn-primary confirm-yes" style="background:linear-gradient(135deg,#991b1b,#dc2626);">Yes, continue</button>
        </div>
    </dialog>

    {{-- Keyboard shortcuts dialog --}}
    <dialog id="shortcutsDialog">
        <div class="modal-title">
            Keyboard Shortcuts
            <button class="modal-close" onclick="this.closest('dialog').close()">×</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.625rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:#0a1510;border-radius:.5rem;font-size:.8125rem;color:var(--text-muted);">
                Dashboard <span><span class="kbd-hint">g</span> <span class="kbd-hint">d</span></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:#0a1510;border-radius:.5rem;font-size:.8125rem;color:var(--text-muted);">
                Batches <span><span class="kbd-hint">g</span> <span class="kbd-hint">b</span></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:#0a1510;border-radius:.5rem;font-size:.8125rem;color:var(--text-muted);">
                Harvest <span><span class="kbd-hint">g</span> <span class="kbd-hint">h</span></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:#0a1510;border-radius:.5rem;font-size:.8125rem;color:var(--text-muted);">
                Inventory <span><span class="kbd-hint">g</span> <span class="kbd-hint">i</span></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:#0a1510;border-radius:.5rem;font-size:.8125rem;color:var(--text-muted);">
                Customers <span><span class="kbd-hint">g</span> <span class="kbd-hint">c</span></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:#0a1510;border-radius:.5rem;font-size:.8125rem;color:var(--text-muted);">
                Orders <span><span class="kbd-hint">g</span> <span class="kbd-hint">o</span></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:#0a1510;border-radius:.5rem;font-size:.8125rem;color:var(--text-muted);">
                Reports <span><span class="kbd-hint">g</span> <span class="kbd-hint">r</span></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:#0a1510;border-radius:.5rem;font-size:.8125rem;color:var(--text-muted);">
                Close dialog <span><span class="kbd-hint">Esc</span></span>
            </div>
        </div>
        <p style="font-size:.6875rem;color:var(--text-dim);margin-top:1rem;text-align:center;">
            Press <span class="kbd-hint">?</span> any time to see this list
        </p>
    </dialog>

</body>
</html>
