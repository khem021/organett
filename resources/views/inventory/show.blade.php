@extends('layouts.app')
@section('title', $inventory->item_name)
@section('page-title', 'Inventory')

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:.5rem;font-size:.8125rem;color:var(--text-muted);margin-bottom:1.25rem;">
    <a href="{{ route('inventory.index') }}" style="color:var(--text-muted);text-decoration:none;">Inventory</a>
    <span style="color:var(--text-dim);">/</span>
    <span style="color:var(--text);">{{ $inventory->item_name }}</span>
</div>

{{-- Header --}}
<div class="page-header">
    <div class="page-header-left">
        <h2>{{ $inventory->item_name }}</h2>
        <p>{{ $inventory->category }} · {{ $inventory->unit }} · {{ $inventory->location ?? 'No location set' }}</p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('inventory.index') }}" class="btn-secondary">← Back</a>
    </div>
</div>

{{-- Stat cards --}}
<div class="grid-3" style="margin-bottom:1rem;">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Current Stock</span>
            <div class="stat-icon" style="background:{{ $inventory->stock_qty <= $inventory->reorder_level ? '#7f1d1d22' : '#14532d22' }};">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $inventory->stock_qty <= $inventory->reorder_level ? '#f87171' : '#4ade80' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
        </div>
        <div class="stat-value" style="{{ $inventory->stock_qty <= $inventory->reorder_level ? 'color:var(--danger)' : '' }}">
            {{ number_format($inventory->stock_qty, 2) }}
            <span style="font-size:1rem;font-weight:500;color:var(--text-muted);margin-left:.25rem;">{{ $inventory->unit }}</span>
        </div>
        <div class="stat-sub">
            @if($inventory->stock_qty <= $inventory->reorder_level)
                <span class="danger">Below reorder level ({{ number_format($inventory->reorder_level, 2) }})</span>
            @else
                Reorder at {{ number_format($inventory->reorder_level, 2) }} {{ $inventory->unit }}
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Total Received</span></div>
        <div class="stat-value" style="color:var(--green-light);">{{ number_format($totalIn, 2) }}</div>
        <div class="stat-sub">{{ $inventory->unit }} stocked in total</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Total Used</span></div>
        <div class="stat-value" style="color:var(--warning);">{{ number_format($totalOut, 2) }}</div>
        <div class="stat-sub">{{ $inventory->unit }} removed in total</div>
    </div>
</div>

{{-- Transaction history --}}
<div class="card">
    <div class="card-title">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
        </svg>
        Transaction History
    </div>

    @if($transactions->isEmpty())
        <div class="empty-state">No transactions recorded yet.</div>
    @else
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Notes</th>
                <th>Logged By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
            <tr>
                <td style="font-size:.8125rem;">{{ $tx->transaction_date->format('M d, Y') }}</td>
                <td>
                    @if($tx->transaction_type === 'in')
                        <span class="badge badge-green"><span class="badge-dot"></span>Stock In</span>
                    @else
                        <span class="badge badge-red"><span class="badge-dot"></span>Stock Out</span>
                    @endif
                </td>
                <td style="font-weight:700;color:{{ $tx->transaction_type === 'in' ? 'var(--green-light)' : 'var(--danger)' }};">
                    {{ $tx->transaction_type === 'in' ? '+' : '-' }}{{ number_format($tx->quantity, 2) }} {{ $inventory->unit }}
                </td>
                <td style="font-size:.8125rem;color:var(--text-muted);">{{ $tx->notes ?: '—' }}</td>
                <td style="font-size:.8125rem;">{{ $tx->creator?->full_name ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:1rem;">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
