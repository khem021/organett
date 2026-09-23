@extends('layouts.app')

@section('title', 'Master Dashboard — All Farms')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Master Dashboard</h1>
        <p class="page-sub">Manage all registered farms on the Organett platform.</p>
    </div>
</div>

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-label">Total Farms</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Farms</div>
        <div class="stat-value" style="color:var(--green-light);">{{ $stats['active'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Farm Users</div>
        <div class="stat-value">{{ $stats['users'] }}</div>
    </div>
</div>

@if(session('status'))
    <div class="alert-success" style="margin-bottom:1.25rem;padding:.75rem 1rem;background:#14532d33;border:1px solid #16a34a55;border-radius:.5rem;font-size:.875rem;color:var(--green-light);">
        {{ session('status') }}
    </div>
@endif

{{-- Farms Table --}}
<div class="card" style="overflow:hidden;">
    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--card-border);display:flex;align-items:center;justify-content:space-between;">
        <h2 style="font-size:1rem;font-weight:600;">Registered Farms</h2>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
            <thead>
                <tr style="border-bottom:1px solid var(--card-border);">
                    <th style="padding:.75rem 1.5rem;text-align:left;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Farm</th>
                    <th style="padding:.75rem 1rem;text-align:center;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Status</th>
                    <th style="padding:.75rem 1rem;text-align:center;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Users</th>
                    <th style="padding:.75rem 1rem;text-align:center;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Batches</th>
                    <th style="padding:.75rem 1rem;text-align:center;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Orders</th>
                    <th style="padding:.75rem 1rem;text-align:center;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Registered</th>
                    <th style="padding:.75rem 1.5rem;text-align:right;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($farms as $farm)
                <tr style="border-bottom:1px solid var(--card-border);">
                    <td style="padding:.875rem 1.5rem;">
                        <div style="font-weight:600;color:var(--text);">{{ $farm->name }}</div>
                        <div style="font-size:.75rem;color:var(--text-muted);">{{ $farm->slug }}</div>
                    </td>
                    <td style="padding:.875rem 1rem;text-align:center;">
                        @if($farm->status === 'active')
                            <span style="display:inline-block;padding:.2rem .6rem;background:#14532d44;color:var(--green-light);border-radius:99px;font-size:.7rem;font-weight:600;text-transform:uppercase;">Active</span>
                        @elseif($farm->status === 'inactive')
                            <span style="display:inline-block;padding:.2rem .6rem;background:#7f1d1d33;color:#f87171;border-radius:99px;font-size:.7rem;font-weight:600;text-transform:uppercase;">Inactive</span>
                        @else
                            <span style="display:inline-block;padding:.2rem .6rem;background:#78350f33;color:#fbbf24;border-radius:99px;font-size:.7rem;font-weight:600;text-transform:uppercase;">Pending</span>
                        @endif
                    </td>
                    <td style="padding:.875rem 1rem;text-align:center;color:var(--text-muted);">{{ $farm->users_count }}</td>
                    <td style="padding:.875rem 1rem;text-align:center;color:var(--text-muted);">{{ $farm->production_batches_count }}</td>
                    <td style="padding:.875rem 1rem;text-align:center;color:var(--text-muted);">{{ $farm->orders_count }}</td>
                    <td style="padding:.875rem 1rem;text-align:center;color:var(--text-muted);font-size:.8rem;">{{ $farm->created_at->format('M d, Y') }}</td>
                    <td style="padding:.875rem 1.5rem;text-align:right;">
                        <a href="{{ route('admin.farms.show', $farm) }}" style="font-size:.8rem;color:var(--green-light);text-decoration:none;font-weight:500;margin-right:.75rem;">Manage</a>
                        <a href="{{ route('admin.farms.features', $farm) }}" style="font-size:.8rem;color:var(--text-muted);text-decoration:none;font-weight:500;">Features</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding:2rem;text-align:center;color:var(--text-muted);">No farms registered yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
