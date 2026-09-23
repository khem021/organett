@extends('layouts.app')

@section('title', $farm->name . ' — Feature Flags')

@section('content')
<div class="page-header">
    <div>
        <a href="{{ route('admin.farms.show', $farm) }}" style="font-size:.8rem;color:var(--text-muted);text-decoration:none;">&larr; {{ $farm->name }}</a>
        <h1 class="page-title" style="margin-top:.25rem;">Feature Flags</h1>
        <p class="page-sub">Enable or disable modules for this farm.</p>
    </div>
</div>

@if(session('status'))
    <div style="margin-bottom:1.25rem;padding:.75rem 1rem;background:#14532d33;border:1px solid #16a34a55;border-radius:.5rem;font-size:.875rem;color:var(--green-light);">{{ session('status') }}</div>
@endif

<div class="card" style="max-width:560px;padding:1.75rem;">
    <form method="POST" action="{{ route('admin.farms.features.update', $farm) }}">
        @csrf @method('PATCH')

        @php
        $labels = [
            'reports'       => ['Reports & Export', 'Access to the reports page and CSV export.'],
            'activity_logs' => ['Activity Logs', 'View audit trail of all user actions.'],
            'export'        => ['Data Export', 'Export reports to CSV.'],
        ];
        @endphp

        @foreach($features as $key => $enabled)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.875rem 0;border-bottom:1px solid var(--card-border);">
            <div>
                <div style="font-size:.9rem;font-weight:500;color:var(--text);">{{ $labels[$key][0] ?? $key }}</div>
                <div style="font-size:.775rem;color:var(--text-muted);margin-top:.2rem;">{{ $labels[$key][1] ?? '' }}</div>
            </div>
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.8rem;color:var(--text-muted);">
                <input type="checkbox" name="{{ $key }}" value="1" {{ $enabled ? 'checked' : '' }}
                    style="width:1rem;height:1rem;accent-color:var(--green-accent);cursor:pointer;">
                {{ $enabled ? 'Enabled' : 'Disabled' }}
            </label>
        </div>
        @endforeach

        <div style="margin-top:1.5rem;">
            <button type="submit" style="padding:.625rem 1.25rem;background:linear-gradient(135deg,var(--green-mid),var(--green-accent));color:#fff;border:none;border-radius:.5rem;font-size:.875rem;font-weight:600;cursor:pointer;">Save Changes</button>
        </div>
    </form>
</div>
@endsection
