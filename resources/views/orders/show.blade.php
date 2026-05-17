@extends('layouts.app')
@section('title', $order->order_no)
@section('page-title','Order Detail')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h2>{{ $order->order_no }}</h2>
        <p>Placed {{ $order->order_date->format('M d, Y') }} · {{ $order->customer?->customer_name ?? '—' }}</p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('orders.index') }}" class="btn-secondary" style="font-size:.875rem;padding:.45rem .9rem;">← Back</a>
        <a href="{{ route('orders.print', $order) }}" target="_blank" class="btn-secondary" style="font-size:.875rem;padding:.45rem .9rem;">🖨 Print</a>
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('record-payment').showModal()">+ Record Payment</button>
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('update-status').showModal()">Update Status</button>
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('update-delivery').showModal()">
            {{ $order->delivery ? 'Edit Delivery' : '+ Add Delivery' }}
        </button>
    </div>
</div>

{{-- Payment progress bar --}}
@php
    $totalPaid  = $order->sales->sum('amount');
    $pct        = $order->total_amount > 0 ? min(100, ($totalPaid / $order->total_amount) * 100) : 0;
    $remaining  = max(0, $order->total_amount - $totalPaid);
@endphp
<div class="card" style="margin-bottom:1rem;padding:1rem 1.25rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.625rem;">
        <div style="font-size:.8125rem;font-weight:600;color:var(--text);">
            Payment Progress
            @php $pmap=['unpaid'=>'badge-red','partial'=>'badge-yellow','paid'=>'badge-green']; @endphp
            <span class="badge {{ $pmap[$order->payment_status]??'badge-gray' }}" style="margin-left:.5rem;">
                <span class="badge-dot"></span>{{ ucfirst($order->payment_status) }}
            </span>
        </div>
        <div style="font-size:.8125rem;color:var(--text-muted);">
            <span style="color:var(--green-light);font-weight:700;">₱{{ number_format($totalPaid,2) }}</span>
            of ₱{{ number_format($order->total_amount,2) }}
            @if($remaining > 0)
                &nbsp;·&nbsp;<span style="color:var(--warning);">₱{{ number_format($remaining,2) }} remaining</span>
            @endif
        </div>
    </div>
    <div class="progress-track" style="height:8px;">
        <div class="progress-fill" style="width:{{ $pct }}%;background:{{ $pct >= 100 ? 'var(--green-accent)' : ($pct > 0 ? 'var(--warning)' : 'var(--card-border)') }};"></div>
    </div>
</div>

<div class="grid-2">
    {{-- Order Info --}}
    <div class="card">
        <div class="card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            Order Details
        </div>
        <table>
            <tbody>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;width:40%;">Customer</td><td class="text-main" style="padding:.5rem .75rem;">{{ $order->customer?->customer_name ?? '—' }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Contact</td><td style="padding:.5rem .75rem;font-size:.8125rem;color:var(--text-muted);">{{ $order->customer?->phone ?? '—' }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Item</td><td class="text-main" style="padding:.5rem .75rem;">{{ $order->item_name }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Quantity</td><td style="padding:.5rem .75rem;color:var(--green-light);font-weight:700;">{{ number_format($order->quantity_kg,2) }} kg</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Unit Price</td><td style="padding:.5rem .75rem;">₱{{ number_format($order->unit_price,2) }}/kg</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Total Amount</td><td style="padding:.5rem .75rem;color:var(--green-light);font-weight:700;font-size:1.125rem;">₱{{ number_format($order->total_amount,2) }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Payment</td>
                    <td style="padding:.5rem .75rem;">
                        <span class="badge {{ $pmap[$order->payment_status]??'badge-gray' }}"><span class="badge-dot"></span>{{ ucfirst($order->payment_status) }}</span>
                    </td>
                </tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Order Status</td>
                    <td style="padding:.5rem .75rem;">
                        @php $smap=['pending'=>'badge-gray','processing'=>'badge-blue','completed'=>'badge-green','cancelled'=>'badge-red']; @endphp
                        <span class="badge {{ $smap[$order->order_status]??'badge-gray' }}"><span class="badge-dot"></span>{{ ucfirst($order->order_status) }}</span>
                    </td>
                </tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Order Date</td><td style="padding:.5rem .75rem;">{{ $order->order_date->format('M d, Y') }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Delivery Date</td><td style="padding:.5rem .75rem;">{{ $order->delivery_date->format('M d, Y') }}</td></tr>
                @if($order->notes)
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Notes</td><td style="padding:.5rem .75rem;font-size:.8125rem;color:var(--text-muted);">{{ $order->notes }}</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Right column --}}
    <div style="display:flex;flex-direction:column;gap:1rem;">

        {{-- Delivery Info --}}
        <div class="card">
            <div class="card-title">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Delivery Information
            </div>
            @if($order->delivery)
            @php
                $dmap = ['scheduled'=>'badge-gray','in_transit'=>'badge-blue','delivered'=>'badge-green','cancelled'=>'badge-red'];
                $d = $order->delivery;
            @endphp
            <table>
                <tbody>
                    <tr><td style="color:var(--text-muted);padding:.5rem .75rem;width:40%;">Destination</td><td class="text-main" style="padding:.5rem .75rem;">{{ $d->destination }}</td></tr>
                    <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Delivery Date</td><td style="padding:.5rem .75rem;">{{ $d->delivery_date->format('M d, Y') }}</td></tr>
                    <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Status</td>
                        <td style="padding:.5rem .75rem;">
                            <span class="badge {{ $dmap[$d->transport_status]??'badge-gray' }}"><span class="badge-dot"></span>{{ ucfirst(str_replace('_',' ',$d->transport_status)) }}</span>
                        </td>
                    </tr>
                    <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Personnel</td><td style="padding:.5rem .75rem;">{{ $d->assigned_personnel ?? '—' }}</td></tr>
                    <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Vehicle</td><td style="padding:.5rem .75rem;">{{ $d->vehicle_info ?? '—' }}</td></tr>
                    @if($d->remarks)
                    <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Remarks</td><td style="padding:.5rem .75rem;font-size:.8125rem;color:var(--text-muted);">{{ $d->remarks }}</td></tr>
                    @endif
                </tbody>
            </table>
            @else
                <div class="empty-state" style="padding:1.5rem 1rem;">No delivery record yet.<br><button onclick="document.getElementById('update-delivery').showModal()" class="btn-sm btn-sm-blue" style="margin-top:.5rem;">+ Add Delivery</button></div>
            @endif
        </div>

        {{-- Payment Records --}}
        <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                <div class="card-title" style="margin-bottom:0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Payment Records
                </div>
                <button class="btn-sm btn-sm-green" onclick="document.getElementById('record-payment').showModal()">+ Record</button>
            </div>

            @if($order->sales->isEmpty())
                <div class="empty-state" style="padding:1.25rem 1rem;">No payments recorded yet.</div>
            @else
            <table>
                <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Remarks</th><th></th></tr></thead>
                <tbody>
                    @foreach($order->sales->sortByDesc('sale_date') as $sale)
                    <tr>
                        <td style="font-size:.75rem;">{{ $sale->sale_date->format('M d, Y') }}</td>
                        <td style="color:var(--green-light);font-weight:600;">₱{{ number_format($sale->amount,2) }}</td>
                        <td style="font-size:.75rem;">{{ $sale->payment_method }}</td>
                        <td style="font-size:.75rem;color:var(--text-muted);max-width:100px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $sale->remarks ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('sales.destroy', [$order, $sale]) }}"
                                  onsubmit="return true">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-sm btn-sm-red">Del</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:.75rem;padding-top:.75rem;border-top:1px solid var(--card-border);display:flex;justify-content:space-between;font-size:.8125rem;">
                <span style="color:var(--text-muted);">Total Paid</span>
                <span style="font-weight:700;color:var(--green-light);">₱{{ number_format($totalPaid,2) }}</span>
            </div>
            @endif
        </div>

    </div>
</div>

{{-- Record Payment Modal --}}
<dialog id="record-payment">
    <div class="modal-title">
        Record Payment — {{ $order->order_no }}
        <button class="modal-close" onclick="this.closest('dialog').close()">×</button>
    </div>
    <form method="POST" action="{{ route('sales.store', $order) }}">
        @csrf
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Payment Date <span style="color:var(--danger);">*</span></label>
                <input type="date" name="sale_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Method <span style="color:var(--danger);">*</span></label>
                <select name="payment_method" class="form-select" required>
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                    <option value="Maya">Maya</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Amount Paid (₱) <span style="color:var(--danger);">*</span></label>
                <input type="number" name="amount" class="form-input" step="0.01" min="0.01"
                       placeholder="{{ number_format($remaining, 2) }}" required>
                @if($remaining > 0)
                <span style="font-size:.75rem;color:var(--text-muted);margin-top:.25rem;">Balance: ₱{{ number_format($remaining,2) }}</span>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label">Quantity (kg)</label>
                <input type="number" name="quantity_kg" class="form-input" step="0.01" min="0.01"
                       value="{{ number_format($order->quantity_kg - $order->sales->sum('quantity_kg'), 2, '.', '') }}"
                       placeholder="{{ number_format($order->quantity_kg,2) }}">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Remarks</label>
            <textarea name="remarks" class="form-textarea" placeholder="Reference no., notes…"></textarea>
        </div>

        {{-- Summary box --}}
        <div style="background:#0a1a0e;border:1px solid var(--card-border);border-radius:.5rem;padding:.75rem 1rem;margin-bottom:.875rem;font-size:.8125rem;">
            <div style="display:flex;justify-content:space-between;margin-bottom:.25rem;">
                <span style="color:var(--text-muted);">Order Total</span>
                <span style="color:var(--text);">₱{{ number_format($order->total_amount,2) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:.25rem;">
                <span style="color:var(--text-muted);">Already Paid</span>
                <span style="color:var(--green-light);">₱{{ number_format($totalPaid,2) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;border-top:1px solid var(--card-border);padding-top:.5rem;margin-top:.5rem;">
                <span style="color:var(--text-muted);">Remaining Balance</span>
                <span style="color:{{ $remaining > 0 ? 'var(--warning)' : 'var(--green-light)' }};font-weight:700;">
                    ₱{{ number_format($remaining,2) }}
                </span>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Save Payment</button>
        </div>
    </form>
</dialog>

{{-- Update Status Modal --}}
<dialog id="update-status">
    <div class="modal-title">Update Order Status <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" action="{{ route('orders.status', $order) }}">
        @csrf @method('PATCH')
        <div class="form-group">
            <label class="form-label">Order Status</label>
            <select name="order_status" class="form-select" required>
                @foreach(['pending','processing','completed','cancelled'] as $s)
                    <option value="{{ $s }}" @selected($order->order_status===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Payment Status</label>
            <select name="payment_status" class="form-select" required>
                <option value="unpaid" @selected($order->payment_status==='unpaid')>Unpaid</option>
                <option value="partial" @selected($order->payment_status==='partial')>Partial</option>
                <option value="paid" @selected($order->payment_status==='paid')>Paid</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Save</button>
        </div>
    </form>
</dialog>

{{-- Delivery Modal --}}
<dialog id="update-delivery">
    <div class="modal-title">{{ $order->delivery ? 'Edit' : 'Add' }} Delivery Info <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" action="{{ route('orders.delivery', $order) }}">
        @csrf @method('PATCH')
        <div class="form-group">
            <label class="form-label">Destination</label>
            <input type="text" name="destination" class="form-input" value="{{ $order->delivery?->destination ?? $order->customer?->address }}" required>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Delivery Date</label>
                <input type="date" name="delivery_date" class="form-input" value="{{ $order->delivery?->delivery_date?->format('Y-m-d') ?? $order->delivery_date->format('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Transport Status</label>
                <select name="transport_status" class="form-select" required>
                    @foreach(['scheduled','in_transit','delivered','cancelled'] as $s)
                        <option value="{{ $s }}" @selected(($order->delivery?->transport_status??'scheduled')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Assigned Personnel</label>
                <input type="text" name="assigned_personnel" class="form-input" value="{{ $order->delivery?->assigned_personnel }}" placeholder="Driver/courier name">
            </div>
            <div class="form-group">
                <label class="form-label">Vehicle</label>
                <input type="text" name="vehicle_info" class="form-input" value="{{ $order->delivery?->vehicle_info }}" placeholder="Van 1, Motorbike…">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Remarks</label>
            <textarea name="remarks" class="form-textarea" placeholder="Delivery notes…">{{ $order->delivery?->remarks }}</textarea>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Save Delivery</button>
        </div>
    </form>
</dialog>
@endsection
