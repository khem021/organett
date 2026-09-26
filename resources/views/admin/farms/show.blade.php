@extends('layouts.app')

@section('title', $farm->name . ' — Farm Detail')

@section('content')
<div class="page-header">
    <div>
        <a href="{{ route('admin.farms.index') }}" style="font-size:.8rem;color:var(--text-muted);text-decoration:none;">&larr; All Farms</a>
        <h1 class="page-title" style="margin-top:.25rem;">{{ $farm->name }}</h1>
        <p class="page-sub">{{ $farm->slug }} &bull; Registered {{ $farm->created_at->format('M d, Y') }}</p>
    </div>
    <div style="display:flex;gap:.75rem;align-items:center;">
        <a href="{{ route('admin.farms.features', $farm) }}" class="btn-outline" style="font-size:.8rem;">Manage Features</a>
        <form method="POST" action="{{ route('admin.farms.status', $farm) }}">
            @csrf @method('PATCH')
            @if($farm->status === 'active')
                <input type="hidden" name="status" value="inactive">
                <button type="submit" style="padding:.45rem .875rem;background:#7f1d1d33;color:var(--danger);border:1px solid #f8717144;border-radius:.375rem;font-size:.8rem;cursor:pointer;">Deactivate</button>
            @else
                <input type="hidden" name="status" value="active">
                <button type="submit" style="padding:.45rem .875rem;background:#14532d33;color:var(--green-light);border:1px solid #16a34a55;border-radius:.375rem;font-size:.8rem;cursor:pointer;">Activate</button>
            @endif
        </form>
    </div>
</div>

@if(session('status'))
    <div style="margin-bottom:1.25rem;padding:.75rem 1rem;background:#14532d33;border:1px solid #16a34a55;border-radius:.5rem;font-size:.875rem;color:var(--green-light);">{{ session('status') }}</div>
@endif

{{-- Stats row --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-label">Users</div>
        <div class="stat-value">{{ $farm->users_count }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Batches</div>
        <div class="stat-value">{{ $farm->production_batches_count }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Customers</div>
        <div class="stat-value">{{ $farm->customers_count }}</div>
    </div>
</div>

{{-- Users list --}}
<div class="card" style="overflow:hidden;">
    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--card-border);">
        <h2 style="font-size:1rem;font-weight:600;">Farm Users</h2>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
            <thead>
                <tr style="border-bottom:1px solid var(--card-border);">
                    <th style="padding:.75rem 1.5rem;text-align:left;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Name</th>
                    <th style="padding:.75rem 1rem;text-align:left;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Email</th>
                    <th style="padding:.75rem 1rem;text-align:center;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Role</th>
                    <th style="padding:.75rem 1rem;text-align:center;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($farm->users as $user)
                <tr style="border-bottom:1px solid var(--card-border);">
                    <td style="padding:.875rem 1.5rem;font-weight:500;">{{ $user->full_name }}</td>
                    <td style="padding:.875rem 1rem;color:var(--text-muted);">{{ $user->email }}</td>
                    <td style="padding:.875rem 1rem;text-align:center;">
                        <span style="font-size:.75rem;text-transform:capitalize;color:{{ $user->role === 'farm_admin' ? 'var(--green-light)' : 'var(--text-muted)' }}">{{ str_replace('_', ' ', $user->role) }}</span>
                    </td>
                    <td style="padding:.875rem 1rem;text-align:center;">
                        @if($user->status === 'active')
                            <span style="font-size:.7rem;padding:.2rem .5rem;background:#14532d44;color:var(--green-light);border-radius:99px;">Active</span>
                        @else
                            <span style="font-size:.7rem;padding:.2rem .5rem;background:#7f1d1d33;color:var(--danger);border-radius:99px;">Inactive</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="padding:2rem;text-align:center;color:var(--text-muted);">No users in this farm.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
