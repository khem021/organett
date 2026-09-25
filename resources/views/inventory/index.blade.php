@extends('layouts.app')
@section('title','Inventory')
@section('page-title','Inventory')

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

{{-- Active filter pills --}}
@if(request()->hasAny(['search','category','filter']))
<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;flex-wrap:wrap;">
    <span style="font-size:.75rem;color:var(--text-dim);">Filtered by:</span>
    @if(request('search'))<span class="filter-pill">Search: "{{ request('search') }}" <a href="{{ route('inventory.index', array_diff_key(request()->query(), ['search'=>''])) }}">×</a></span>@endif
    @if(request('category'))<span class="filter-pill">{{ request('category') }} <a href="{{ route('inventory.index', array_diff_key(request()->query(), ['category'=>''])) }}">×</a></span>@endif
    @if(request('filter') === 'low')<span class="filter-pill">Low Stock only <a href="{{ route('inventory.index', array_diff_key(request()->query(), ['filter'=>''])) }}">×</a></span>@endif
</div>
@endif

{{-- Main grid --}}
<div class="grid-main">
    {{-- Inventory Table --}}
    <div class="card">
        @if($items->isEmpty())
            <div class="empty-state-full">
                <div class="empty-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
                @if(request()->hasAny(['search','category','filter']))
                    <p>No items match your filters.</p>
                    <a href="{{ route('inventory.index') }}" class="btn-secondary">Clear filters</a>
                @else
                    <p>No inventory items yet.</p>
                    <small>Add supplies, materials, and packaging to track stock levels.</small>
                    <button class="btn-primary" onclick="document.getElementById('add-item').showModal()">+ Add Item</button>
                @endif
            </div>
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
                        <button class="btn-sm btn-sm-yellow"
                            onclick="openEdit({{ $item->id }}, @js($item->item_name), @js($item->category), @js($item->unit), @js($item->reorder_level), @js($item->location ?? ''))">
                            Edit
                        </button>
                        <button class="btn-sm btn-sm-green"
                            onclick="openAdjust({{ $item->id }}, '{{ addslashes($item->item_name) }}', {{ $item->stock_qty }}, '{{ $item->unit }}')">
                            Adjust
                        </button>
                        <form method="POST" action="{{ route('inventory.destroy', $item) }}" style="display:inline"
                              data-confirm="Delete &ldquo;{{ $item->item_name }}&rdquo; from inventory? This cannot be undone.">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-sm btn-sm-red" title="Delete item">Delete</button>
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
                <input type="text" name="item_name" class="form-input" value="{{ old('item_name') }}" placeholder="Fresh Mushrooms" required>
                @error('item_name') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-input" value="{{ old('category') }}" placeholder="Production Supplies" required>
                @error('category') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Unit</label>
                <input type="text" name="unit" class="form-input" value="{{ old('unit') }}" placeholder="kg / pcs / bottles" required>
                @error('unit') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Initial Stock</label>
                <input type="number" name="stock_qty" class="form-input" step="0.01" min="0" value="{{ old('stock_qty', 0) }}" required>
                @error('stock_qty') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Reorder Level</label>
                <input type="number" name="reorder_level" class="form-input" step="0.01" min="0" value="{{ old('reorder_level', 0) }}" required>
                @error('reorder_level') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Storage Location</label>
                <input type="text" name="location" class="form-input" value="{{ old('location') }}" placeholder="Warehouse A">
                @error('location') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Add Item</button>
        </div>
    </form>
</dialog>

{{-- Edit Item Modal --}}
<dialog id="edit-item">
    <div class="modal-title">Edit Inventory Item <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" id="edit-item-form" action="{{ old('_edit_id') ? route('inventory.update', old('_edit_id')) : '' }}">
        @csrf @method('PUT')
        <input type="hidden" name="_edit_id" id="edit-item-id-field" value="{{ old('_edit_id') }}">
        <div class="form-grid-2">
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Item Name</label>
                <input type="text" name="item_name" id="edit-item-name" class="form-input" value="{{ old('item_name') }}" required maxlength="150">
                @error('item_name') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="category" id="edit-item-category" class="form-input" value="{{ old('category') }}" required maxlength="120">
                @error('category') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Unit</label>
                <input type="text" name="unit" id="edit-item-unit" class="form-input" value="{{ old('unit') }}" required maxlength="50">
                @error('unit') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Reorder Level</label>
                <input type="number" name="reorder_level" id="edit-item-reorder" class="form-input" step="0.01" min="0.01" value="{{ old('reorder_level') }}" required>
                @error('reorder_level') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Storage Location</label>
                <input type="text" name="location" id="edit-item-location" class="form-input" value="{{ old('location') }}" maxlength="150" placeholder="Optional">
                @error('location') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <p style="font-size:.75rem;color:var(--text-dim);margin-bottom:1rem;">To change the stock quantity, use the <strong style="color:var(--text-muted);">Adjust</strong> button instead.</p>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Save Changes</button>
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
function openEdit(id, name, category, unit, reorder, location) {
    document.getElementById('edit-item-form').action = '/inventory/' + id;
    document.getElementById('edit-item-id-field').value = id;
    document.getElementById('edit-item-name').value     = name;
    document.getElementById('edit-item-category').value = category;
    document.getElementById('edit-item-unit').value     = unit;
    document.getElementById('edit-item-reorder').value  = reorder;
    document.getElementById('edit-item-location').value = location;
    document.getElementById('edit-item').showModal();
}
function openAdjust(id, name, current, unit) {
    document.getElementById('adj-name').textContent = name;
    document.getElementById('adj-current').textContent = current + ' ' + unit;
    document.getElementById('adj-form').action = '/inventory/' + id + '/adjust';
    document.getElementById('adjust-stock').showModal();
}
</script>

@if($errors->any())
<script>document.getElementById('{{ old('_edit_id') ? 'edit-item' : 'add-item' }}').showModal();</script>
@endif
@endsection
