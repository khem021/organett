@extends('layouts.app')

@section('title', 'Archived Farms')

@section('content')
<x-breadcrumbs :items="[['label' => 'All farms', 'url' => route('admin.farms.index')], ['label' => 'Archived farms']]" />

<div class="page-header">
    <div>
        <h2 class="page-title" style="margin-top:.25rem;">Archived Farms</h2>
        <p class="page-sub">Nothing here has been deleted. Restoring a farm brings it back with its previous status and all of its data.</p>
    </div>
</div>

@if(session('status'))
    <div style="margin-bottom:1.25rem;padding:.75rem 1rem;background:#14532d33;border:1px solid #16a34a55;border-radius:.5rem;font-size:.875rem;color:var(--green-light);">{{ session('status') }}</div>
@endif

<div class="card" style="overflow:hidden;">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
            <thead>
                <tr style="border-bottom:1px solid var(--card-border);">
                    <th style="padding:.75rem 1.5rem;text-align:left;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Farm</th>
                    <th style="padding:.75rem 1rem;text-align:center;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Users</th>
                    <th style="padding:.75rem 1rem;text-align:center;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Batches</th>
                    <th style="padding:.75rem 1rem;text-align:center;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Orders</th>
                    <th style="padding:.75rem 1rem;text-align:center;color:var(--text-muted);font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;">Archived</th>
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
                    <td style="padding:.875rem 1rem;text-align:center;color:var(--text-muted);">{{ $farm->users_count }}</td>
                    <td style="padding:.875rem 1rem;text-align:center;color:var(--text-muted);">{{ $farm->production_batches_count }}</td>
                    <td style="padding:.875rem 1rem;text-align:center;color:var(--text-muted);">{{ $farm->orders_count }}</td>
                    <td style="padding:.875rem 1rem;text-align:center;color:var(--text-muted);font-size:.8rem;">{{ $farm->deleted_at->format('M d, Y') }}</td>
                    <td style="padding:.875rem 1.5rem;text-align:right;">
                        <form method="POST" action="{{ route('admin.farms.restore', $farm->id) }}" style="display:inline;"
                              data-confirm="Restore &ldquo;{{ $farm->name }}&rdquo;? Its users will be able to sign in again."
                              data-confirm-title="Restore farm" data-confirm-button="Restore farm">
                            @csrf @method('PATCH')
                            <button type="submit" style="background:none;border:none;padding:0;font-size:.8rem;color:var(--green-light);font-weight:500;cursor:pointer;font-family:inherit;">Restore</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="padding:2rem;text-align:center;color:var(--text-muted);">No archived farms.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
