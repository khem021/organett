@extends('layouts.app')
@section('title','Settings')
@section('page-title','System Settings')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h2>System Settings</h2>
        <p>Farm information, export configuration, and access restrictions</p>
    </div>
</div>

<form method="POST" action="{{ route('settings.update') }}">
@csrf

<div class="grid-2 gap-top" style="align-items:start;">

    {{-- Left column --}}
    <div style="display:flex;flex-direction:column;gap:1rem;">

        {{-- Farm Information --}}
        <div class="card">
            <div class="card-title">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Farm Information
            </div>
            <div class="form-group">
                <label class="form-label">Farm Name</label>
                <input type="text" name="farm_name" class="form-input" value="{{ $settings->get('farm_name','') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="farm_address" class="form-input" value="{{ $settings->get('farm_address','') }}" placeholder="City, Province, Philippines">
            </div>
            <div class="form-group">
                <label class="form-label">Contact Number</label>
                <input type="text" name="farm_contact" class="form-input" value="{{ $settings->get('farm_contact','') }}" placeholder="+63 9xx xxx xxxx">
            </div>
        </div>

        {{-- Export Settings --}}
        <div class="card">
            <div class="card-title">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export Settings
            </div>
            <p style="font-size:.8125rem;color:var(--text-muted);margin-bottom:1.25rem;">
                Choose which income report formats staff and admin can download from the Reports page. At least one format must be enabled.
            </p>

            @php
            $periods = [
                'daily'   => ['Daily',   'Last 30 days, one row per day'],
                'weekly'  => ['Weekly',  'Last 12 weeks, one row per week'],
                'monthly' => ['Monthly', 'Last 12 months, one row per month'],
                'yearly'  => ['Yearly',  'All-time, one row per year'],
            ];
            @endphp

            <div style="display:flex;flex-direction:column;gap:.625rem;">
                @foreach($periods as $key => [$label, $desc])
                <label style="display:flex;align-items:center;gap:.875rem;padding:.75rem 1rem;background:#0a1a0e;border:1px solid {{ in_array($key,$exportPeriods) ? 'var(--green-accent)' : 'var(--card-border)' }};border-radius:.5rem;cursor:pointer;transition:border-color .15s;" id="label-{{ $key }}">
                    <input
                        type="checkbox"
                        name="export_periods[]"
                        value="{{ $key }}"
                        {{ in_array($key, $exportPeriods) ? 'checked' : '' }}
                        onchange="updateLabel('{{ $key }}', this.checked)"
                        style="width:1rem;height:1rem;accent-color:var(--green-accent);flex-shrink:0;cursor:pointer;">
                    <div>
                        <div style="font-size:.875rem;font-weight:600;color:var(--text);">{{ $label }}</div>
                        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.125rem;">{{ $desc }}</div>
                    </div>
                    <span style="margin-left:auto;font-size:.6875rem;font-weight:600;padding:.15rem .5rem;border-radius:999px;background:{{ in_array($key,$exportPeriods) ? 'var(--green-mid)' : '#1a3322' }};color:{{ in_array($key,$exportPeriods) ? 'var(--green-light)' : 'var(--text-dim)' }};" id="badge-{{ $key }}">
                        {{ in_array($key,$exportPeriods) ? 'Enabled' : 'Disabled' }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>

    </div>

    {{-- Right column --}}
    <div style="display:flex;flex-direction:column;gap:1rem;">

        {{-- Access Restrictions --}}
        <div class="card">
            <div class="card-title">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Access Restrictions
            </div>
            <table>
                <thead>
                    <tr><th>Feature</th><th style="text-align:center;">Admin</th><th style="text-align:center;">Staff</th></tr>
                </thead>
                <tbody>
                    @php
                    $matrix = [
                        ['Dashboard',       true, true],
                        ['Batches',         true, true],
                        ['Harvest Logs',    true, true],
                        ['Inventory',       true, true],
                        ['Orders',          true, true],
                        ['Reports',         true, true],
                        ['Export Reports',  true, true],
                        ['Settings',        true, false],
                        ['User Management', true, false],
                    ];
                    @endphp
                    @foreach($matrix as [$feature, $adminOk, $staffOk])
                    <tr>
                        <td class="text-main">{{ $feature }}</td>
                        <td style="text-align:center;">
                            <span style="color:var(--green-light);font-size:1rem;">✓</span>
                        </td>
                        <td style="text-align:center;">
                            @if($staffOk)
                                <span style="color:var(--green-light);font-size:1rem;">✓</span>
                            @else
                                <span style="color:var(--danger);font-size:.875rem;">✗</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:1rem;padding:.75rem;background:#0a1a0e;border-radius:.5rem;font-size:.75rem;color:var(--text-muted);border:1px solid var(--card-border);">
                <strong style="color:var(--text);">Note:</strong> Staff can only download export formats that are enabled above. Admin can always export all formats regardless of this setting.
            </div>
        </div>

        {{-- Save --}}
        <div style="display:flex;justify-content:flex-end;">
            @if($errors->any())
                @foreach($errors->all() as $e)
                    <div class="flash flash-error" style="margin-right:auto;">{{ $e }}</div>
                @endforeach
            @endif
            <button type="submit" class="btn-primary" style="padding:.6rem 1.75rem;font-size:.9375rem;">Save All Settings</button>
        </div>

    </div>
</div>

</form>

<script>
function updateLabel(key, checked) {
    const label = document.getElementById('label-' + key);
    const badge = document.getElementById('badge-' + key);
    if (checked) {
        label.style.borderColor = 'var(--green-accent)';
        badge.style.background  = 'var(--green-mid)';
        badge.style.color       = 'var(--green-light)';
        badge.textContent       = 'Enabled';
    } else {
        label.style.borderColor = 'var(--card-border)';
        badge.style.background  = '#1a3322';
        badge.style.color       = 'var(--text-dim)';
        badge.textContent       = 'Disabled';
    }
}
</script>

@endsection
