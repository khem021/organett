@extends('layouts.app')
@section('title','Platform Audit')
@section('page-title','Platform Audit')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h2>Platform Audit</h2>
        <p>Every recorded action across all farms, plus platform-level changes</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.audit') }}">
<div class="filter-bar">
    <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Search description…">

    <select name="farm" class="filter-select">
        <option value="">All Farms</option>
        <option value="platform" @selected(request('farm') === 'platform')>Platform (no farm)</option>
        @foreach($farms as $f)
            <option value="{{ $f->id }}" @selected(request('farm') == $f->id)>{{ $f->name }}@if($f->trashed()) (archived)@endif</option>
        @endforeach
    </select>

    <select name="module" class="filter-select">
        <option value="">All Modules</option>
        @foreach($modules as $mod)
            <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ $mod }}</option>
        @endforeach
    </select>

    <select name="action" class="filter-select">
        <option value="">All Actions</option>
        @foreach($actions as $act)
            <option value="{{ $act }}" @selected(request('action') === $act)>{{ $act }}</option>
        @endforeach
    </select>

    <select name="user_id" class="filter-select">
        <option value="">Anyone</option>
        @foreach($actors as $actor)
            <option value="{{ $actor->id }}" @selected(request('user_id') == $actor->id)>{{ $actor->full_name }}</option>
        @endforeach
    </select>

    <input type="date" name="from" value="{{ request('from') }}" class="filter-input" title="From date">
    <input type="date" name="to" value="{{ request('to') }}" class="filter-input" title="To date">

    <button type="submit" class="btn-secondary">Filter</button>
    @if(request()->hasAny(['search','farm','module','action','user_id','from','to']))
        <a href="{{ route('admin.audit') }}" class="btn-secondary">Clear</a>
    @endif
</div>
</form>

<div class="card">
    @if($logs->isEmpty())
        <div class="empty-state-full">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <p>No audit entries match these filters.</p>
        </div>
    @else
    <table>
        <thead>
            <tr>
                <th style="width:120px;">Time</th>
                <th style="width:140px;">Farm</th>
                <th style="width:130px;">User</th>
                <th style="width:100px;">Module</th>
                <th style="width:110px;">Action</th>
                <th>Description</th>
                <th style="width:110px;">IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            @php
                $actionMap = [
                    'create'            => ['badge-green',  'Created'],
                    'update'            => ['badge-blue',   'Updated'],
                    'delete'            => ['badge-red',    'Deleted'],
                    'adjust'            => ['badge-yellow', 'Adjusted'],
                    'cancel'            => ['badge-yellow', 'Cancelled'],
                    'delivery'          => ['badge-blue',   'Delivery'],
                    'login'             => ['badge-gray',   'Login'],
                    'logout'            => ['badge-gray',   'Logout'],
                    'enable'            => ['badge-green',  'Enabled'],
                    'disable'           => ['badge-red',    'Disabled'],
                    'approve'           => ['badge-green',  'Approved'],
                    'reject'            => ['badge-red',    'Rejected'],
                    'suspend'           => ['badge-red',    'Suspended'],
                    'archive'           => ['badge-red',    'Archived'],
                    'restore'           => ['badge-green',  'Restored'],
                    'password_reset'    => ['badge-yellow', 'Pwd Reset'],
                    'impersonate_start' => ['badge-yellow', 'View As'],
                    'impersonate_end'   => ['badge-gray',   'View End'],
                ];
                [$badgeClass, $label] = $actionMap[strtolower($log->action)] ?? ['badge-gray', ucfirst($log->action)];
            @endphp
            <tr>
                <td style="font-size:.75rem;color:var(--text-muted);white-space:nowrap;">
                    {{ $log->created_at->format('M d, Y') }}<br>
                    <span style="color:var(--text-dim);">{{ $log->created_at->format('h:i A') }}</span>
                </td>
                <td style="font-size:.8125rem;">
                    @if($log->farm)
                        <span style="color:var(--text);">{{ $log->farm->name }}</span>
                        @if($log->farm->trashed())
                            <div style="font-size:.68rem;color:var(--text-dim);">archived</div>
                        @endif
                    @else
                        <span class="badge badge-blue"><span class="badge-dot"></span>Platform</span>
                    @endif
                </td>
                <td style="font-size:.8125rem;">
                    <div style="font-weight:600;color:var(--text);">{{ $log->user?->full_name ?? '—' }}</div>
                    <div style="font-size:.7rem;color:var(--text-dim);">{{ $log->user?->role ?? '' }}</div>
                </td>
                <td style="font-size:.75rem;color:var(--text-muted);">{{ $log->module }}</td>
                <td><span class="badge {{ $badgeClass }}"><span class="badge-dot"></span>{{ $label }}</span></td>
                <td class="cell-wrap" style="font-size:.8125rem;color:var(--text-muted);">{{ $log->description }}</td>
                <td style="font-size:.7rem;color:var(--text-dim);white-space:nowrap;">{{ $log->ip_address ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:1rem;">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
