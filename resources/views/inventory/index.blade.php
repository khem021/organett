@extends('layouts.app')
@section('title','Inventory')
@section('page-title','Inventory')
@section('page-step','3')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h2>Inventory</h2>
        <p>Manage supplies, materials, and stock levels</p>
    </div>
    <div class="page-header-right">
        @if($lowCount > 0)
            <span class="badge badge-red" style="font-size:.75rem;padding:.3rem .7rem;">⚠ {{ $lowCount }} low stock</span>
        @endif
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('add-item').showModal()">+ Add Item</button>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('inventory.index') }}">
<div class="filter-bar">
    <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Search item name…">
    <select name="category" class="filter-select" onchange="this.form.submit()">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
            <option value="{{ $cat }}" @selected(request('category')===$cat)>{{ $cat }}</option>
        @endforeach
    </select>
    <select name="filter" class="filter-select" onchange="this.form.submit()">
        <option value="">All Levels</option>
        <option value="low" @selected(request('filter')==='low')>Low Stock Only</option>
    </select>
    <button type="submit" class="btn-secondary">Filter</button>
    @if(request()->hasAny(['search','category','filter']))
        <a href="{{ route('inventory.index') }}" class="btn-secondary">Clear</a>
    @endif
</div>
</form>

{{-- Main grid --}}
<div class="grid-main">
    {{-- Inventory Table --}}
    <div class="card">
        @if($items->isEmpty())
            <div class="empty-state">No inventory items found.</div>
        @else
        <table>
            <thead>
                <tr><th>Item</th><th>Category</th><th>Stock</th><th>Unit</th><th>Reorder At</th><th>Location</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                @php $low = $item->stock_qty <= $item->reorder_level; @endphp
                <tr>
                    <td class="text-main">{{ $item->item_name }}</td>
                    <td style="font-size:.75rem;">{{ $item->category }}</td>
                    <td style="font-weight:700;color:{{ $low ? 'var(--danger)' : 'var(--green-light)' }};">
                        {{ number_format($item->stock_qty, 2) }}
                    </td>
                    <td style="font-size:.75rem;color:var(--text-muted);">{{ $item->unit }}</td>
                    <td style="font-size:.75rem;color:var(--text-muted);">{{ number_format($item->reorder_level, 2) }}</td>
                    <td style="font-size:.75rem;color:var(--text-muted);">{{ $item->location ?? '—' }}</td>
                    <td>
                        @if($low)
                            <span class="badge badge-red"><span class="badge-dot"></span>Low Stock</span>
                        @else
                            <span class="badge badge-green"><span class="badge-dot"></span>OK</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap;">
                        <button class="btn-sm btn-sm-green"
                            onclick="openAdjust({{ $item->id }}, '{{ addslashes($item->item_name) }}', {{ $item->stock_qty }}, '{{ $item->unit }}')">
                            Adjust
                        </button>
                        <form method="POST" action="{{ route('inventory.destroy', $item) }}" style="display:inline"
                              data-confirm="Delete &ldquo;{{ $item->item_name }}&rdquo; from inventory? This cannot be undone.">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-sm btn-sm-red" title="Delete item">Del</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $items->links() }}</div>
        @endif
    </div>

    {{-- Recent Transactions --}}
    <div class="card">
        <div class="card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
            </svg>
            Recent Transactions
        </div>
        @forelse($recent as $tx)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.625rem 0;border-bottom:1px solid #0f2018;">
            <div>
                <div style="font-size:.8125rem;font-weight:600;color:var(--text);">{{ $tx->inventoryItem?->item_name ?? '—' }}</div>
                <div style="font-size:.7rem;color:var(--text-muted);margin-top:.125rem;">
                    {{ $tx->transaction_date->format('M d') }} · {{ $tx->notes ?? 'No notes' }}
                </div>
            </div>
            <span class="badge {{ $tx->transaction_type === 'in' ? 'badge-green' : 'badge-red' }}">
                {{ $tx->transaction_type === 'in' ? '+' : '-' }}{{ number_format($tx->quantity,2) }}
            </span>
        </div>
        @empty
            <div class="empty-state" style="padding:1.5rem 0;">No transactions yet.</div>
        @endforelse
    </div>
</div>

{{-- Add Item Modal --}}
<dialog id="add-item">
    <div class="modal-title">Add Inventory Item <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" action="{{ route('inventory.store') }}">
        @csrf
        <div class="form-grid-2">
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Item Name</label>
                <input type="text" name="item_name" class="form-input" placeholder="Fresh Mushrooms" required>
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-input" placeholder="Production Supplies" required>
            </div>
            <div class="form-group">
                <label class="form-label">Unit</label>
                <input type="text" name="unit" class="form-input" placeholder="kg / pcs / bottles" required>
            </div>
            <div class="form-group">
                <label class="form-label">Initial Stock</label>
                <input type="number" name="stock_qty" class="form-input" step="0.01" min="0" value="0" required>
            </div>
            <div class="form-group">
                <label class="form-label">Reorder Level</label>
                <input type="number" name="reorder_level" class="form-input" step="0.01" min="0" value="0" required>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Storage Location</label>
                <input type="text" name="location" class="form-input" placeholder="Warehouse A">
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Add Item</button>
        </div>
    </form>
</dialog>

{{-- Adjust Stock Modal --}}
<dialog id="adjust-stock">
    <div class="modal-title">
        Adjust Stock — <span id="adj-name"></span>
        <button class="modal-close" onclick="this.closest('dialog').close()">×</button>
    </div>
    <form method="POST" id="adj-form">
        @csrf
        <div style="background:#0a1a0e;border:1px solid var(--card-border);border-radius:.5rem;padding:.875rem;margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:.8125rem;color:var(--text-muted);">Current Stock</span>
            <span style="font-size:1.25rem;font-weight:700;color:var(--green-light);" id="adj-current"></span>
        </div>
        <div class="form-group">
            <label class="form-label">Transaction Type</label>
            <select name="transaction_type" class="form-select" required>
                <option value="in">Stock In (Add)</option>
                <option value="out">Stock Out (Remove)</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Quantity</label>
            <input type="number" name="quantity" class="form-input" step="0.01" min="0.01" placeholder="0.00" required>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <input type="text" name="notes" class="form-input" placeholder="Reason for adjustment…">
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Adjust Stock</button>
        </div>
    </form>
</dialog>

<script>
function openAdjust(id, name, current, unit) {
    document.getElementById('adj-name').textContent = name;
    document.getElementById('adj-current').textContent = current + ' ' + unit;
    document.getElementById('adj-form').action = '/inventory/' + id + '/adjust';
    document.getElementById('adjust-stock').showModal();
}
</script>

@if($errors->any())
<script>document.getElementById('add-item').showModal();</script>
@endif
@endsection
