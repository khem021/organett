@extends('layouts.app')
@section('title','Orders')
@section('page-title','Orders')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h2>Orders & Distribution</h2>
        <p>{{ $summary['total'] }} total · {{ $summary['pending'] }} pending · {{ $summary['processing'] }} processing</p>
    </div>
    <div class="page-header-right">
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('create-order').showModal()">+ New Order</button>
    </div>
</div>

{{-- Summary Cards --}}
<div class="grid-4" style="margin-bottom:1rem;">
    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Total Orders</span></div>
        <div class="stat-value">{{ $summary['total'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Pending</span></div>
        <div class="stat-value" style="color:var(--warning);">{{ $summary['pending'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Processing</span></div>
        <div class="stat-value" style="color:#38bdf8;">{{ $summary['processing'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Revenue (Paid)</span></div>
        <div class="stat-value" style="font-size:1.25rem;">₱{{ number_format($summary['revenue'],2) }}</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('orders.index') }}">
<div class="filter-bar">
    <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Search order no. or customer…">
    <select name="status" class="filter-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        @foreach(['pending','processing','completed','cancelled'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <select name="payment" class="filter-select" onchange="this.form.submit()">
        <option value="">All Payments</option>
        <option value="unpaid" @selected(request('payment')==='unpaid')>Unpaid</option>
        <option value="partial" @selected(request('payment')==='partial')>Partial</option>
        <option value="paid" @selected(request('payment')==='paid')>Paid</option>
    </select>
    <button type="submit" class="btn-secondary">Filter</button>
    @if(request()->hasAny(['search','status','payment']))
        <a href="{{ route('orders.index') }}" class="btn-secondary">Clear</a>
    @endif
</div>
</form>

{{-- Table --}}
<div class="card">
    @if($orders->isEmpty())
        <div class="empty-state">No orders found.</div>
    @else
    <table>
        <thead>
            <tr><th>Order No.</th><th>Customer</th><th>Item</th><th>Qty</th><th>Total</th><th>Payment</th><th>Status</th><th>Delivery Date</th><th></th></tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            @php
                $pmap = ['unpaid'=>'badge-red','partial'=>'badge-yellow','paid'=>'badge-green'];
                $smap = ['pending'=>'badge-gray','processing'=>'badge-blue','completed'=>'badge-green','cancelled'=>'badge-red'];
            @endphp
            <tr>
                <td class="text-main">{{ $order->order_no }}</td>
                <td>{{ $order->customer?->customer_name ?? '—' }}</td>
                <td style="font-size:.75rem;">{{ $order->item_name }}</td>
                <td>{{ number_format($order->quantity_kg,2) }} kg</td>
                <td style="color:var(--green-light);font-weight:600;">₱{{ number_format($order->total_amount,2) }}</td>
                <td><span class="badge {{ $pmap[$order->payment_status] ?? 'badge-gray' }}"><span class="badge-dot"></span>{{ ucfirst($order->payment_status) }}</span></td>
                <td><span class="badge {{ $smap[$order->order_status] ?? 'badge-gray' }}"><span class="badge-dot"></span>{{ ucfirst($order->order_status) }}</span></td>
                <td style="font-size:.75rem;">{{ $order->delivery_date->format('M d, Y') }}</td>
                <td style="white-space:nowrap;">
                    <a href="{{ route('orders.show', $order) }}" class="btn-sm btn-sm-blue">View</a>
                    <form method="POST" action="{{ route('orders.destroy', $order) }}" style="display:inline" onsubmit="return true">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-sm btn-sm-red">Del</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:1rem;">{{ $orders->links() }}</div>
    @endif
</div>

{{-- Create Order Modal --}}
<dialog id="create-order">
    <div class="modal-title">New Order <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" action="{{ route('orders.store') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Customer</label>
            <select name="customer_id" class="form-select" required>
                <option value="">Select customer…</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Order Date</label>
                <input type="date" name="order_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Delivery Date</label>
                <input type="date" name="delivery_date" class="form-input" required>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Item Name</label>
                <input type="text" name="item_name" class="form-input" value="Fresh Mushrooms" required>
            </div>
            <div class="form-group">
                <label class="form-label">Quantity (kg)</label>
                <input type="number" name="quantity_kg" id="qty" class="form-input" step="0.01" min="0.01" placeholder="0.00" required oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label class="form-label">Unit Price (₱/kg)</label>
                <input type="number" name="unit_price" id="price" class="form-input" step="0.01" min="0" placeholder="0.00" required oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label class="form-label">Payment Status</label>
                <select name="payment_status" class="form-select" required>
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Order Status</label>
                <select name="order_status" class="form-select" required>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>
        <div style="background:#0a1a0e;border:1px solid var(--card-border);border-radius:.5rem;padding:.75rem 1rem;margin-bottom:.875rem;display:flex;justify-content:space-between;">
            <span style="font-size:.8125rem;color:var(--text-muted);">Total Amount</span>
            <span style="font-weight:700;color:var(--green-light);" id="total-display">₱0.00</span>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-textarea" placeholder="Delivery instructions, remarks…"></textarea>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Create Order</button>
        </div>
    </form>
</dialog>

<script>
function calcTotal() {
    const qty = parseFloat(document.getElementById('qty').value) || 0;
    const price = parseFloat(document.getElementById('price').value) || 0;
    document.getElementById('total-display').textContent = '₱' + (qty * price).toLocaleString('en-PH', {minimumFractionDigits:2});
}
</script>

@if($errors->any())
<script>document.getElementById('create-order').showModal();</script>
@endif
@endsection
