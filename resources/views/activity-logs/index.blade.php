@extends('layouts.app')
@section('title','Activity Log')
@section('page-title','Activity Log')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h2>Activity Log</h2>
        <p>Audit trail of all user actions across the system</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('activity-logs.index') }}">
<div class="filter-bar">
    <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Search description…">
    <select name="module" class="filter-select" onchange="this.form.submit()">
        <option value="">All Modules</option>
        @foreach($modules as $mod)
            <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ $mod }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn-secondary">Filter</button>
    @if(request()->hasAny(['search','module','user_id']))
        <a href="{{ route('activity-logs.index') }}" class="btn-secondary">Clear</a>
    @endif
</div>
</form>

{{-- Active filter pills --}}
@if(request()->hasAny(['search','module']))
<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;flex-wrap:wrap;">
    <span style="font-size:.75rem;color:var(--text-dim);">Filtered by:</span>
    @if(request('search'))<span class="filter-pill">Search: "{{ request('search') }}" <a href="{{ route('activity-logs.index', array_diff_key(request()->query(), ['search'=>''])) }}">×</a></span>@endif
    @if(request('module'))<span class="filter-pill">{{ request('module') }} <a href="{{ route('activity-logs.index', array_diff_key(request()->query(), ['module'=>''])) }}">×</a></span>@endif
</div>
@endif

<div class="card">
    @if($logs->isEmpty())
        <div class="empty-state-full">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <p>No activity logs found.</p>
        </div>
    @else
    <table>
        <thead>
            <tr>
                <th style="width:140px;">Time</th>
                <th style="width:130px;">User</th>
                <th style="width:100px;">Module</th>
                <th style="width:80px;">Action</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            @php
                $actionMap = [
                    'create'   => ['badge-green',  'Created'],
                    'update'   => ['badge-blue',   'Updated'],
                    'delete'   => ['badge-red',    'Deleted'],
                    'adjust'   => ['badge-yellow', 'Adjusted'],
                    'cancel'   => ['badge-yellow', 'Cancelled'],
                    'delivery' => ['badge-blue',   'Delivery'],
                    'login'    => ['badge-gray',   'Login'],
                    'logout'   => ['badge-gray',   'Logout'],
                ];
                [$badgeClass, $label] = $actionMap[strtolower($log->action)] ?? ['badge-gray', ucfirst($log->action)];
            @endphp
            <tr>
                <td style="font-size:.75rem;color:var(--text-muted);white-space:nowrap;">
                    {{ $log->created_at->format('M d, Y') }}<br>
                    <span style="color:var(--text-dim);">{{ $log->created_at->format('h:i A') }}</span>
                </td>
                <td style="font-size:.8125rem;">
                    <div style="font-weight:600;color:var(--text);">{{ $log->user?->full_name ?? '—' }}</div>
                    <div style="font-size:.7rem;color:var(--text-dim);">{{ $log->user?->role ?? '' }}</div>
                </td>
                <td style="font-size:.75rem;color:var(--text-muted);">{{ $log->module }}</td>
                <td><span class="badge {{ $badgeClass }}"><span class="badge-dot"></span>{{ $label }}</span></td>
                <td class="cell-wrap" style="font-size:.8125rem;color:var(--text-muted);">{{ $log->description }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:1rem;">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
