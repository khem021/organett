<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $order->order_no }} — Receipt</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green: #16a34a;
            --green-dark: #14532d;
            --text: #111827;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --bg-light: #f9fafb;
        }

        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            background: #f3f4f6;
            color: var(--text);
            padding: 2rem;
        }

        .page {
            background: #fff;
            max-width: 780px;
            margin: 0 auto;
            padding: 2.5rem;
            border-radius: .75rem;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
        }

        /* ── Header ── */
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--green);
            margin-bottom: 1.5rem;
        }
        .farm-logo {
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .logo-icon {
            width: 4rem;
            height: 4rem;
            object-fit: contain;
        }
        .farm-name { font-size: 1.25rem; font-weight: 700; color: var(--text); }
        .farm-sub  { font-size: .75rem; color: var(--text-muted); margin-top: 2px; }

        .receipt-meta { text-align: right; }
        .receipt-no   { font-size: 1.25rem; font-weight: 700; color: var(--green); }
        .receipt-date { font-size: .8125rem; color: var(--text-muted); margin-top: .25rem; }

        /* ── Section title ── */
        .section-title {
            font-size: .6875rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: .625rem;
        }

        /* ── Info grid ── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .info-box {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: .5rem;
            padding: .875rem 1rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: .8125rem;
            padding: .2rem 0;
        }
        .info-label { color: var(--text-muted); }
        .info-value { font-weight: 500; color: var(--text); text-align: right; }

        /* ── Line items table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .875rem;
            margin-bottom: 1.5rem;
        }
        thead th {
            font-size: .6875rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--text-muted);
            text-align: left;
            padding: .5rem .75rem .625rem;
            border-bottom: 1px solid var(--border);
        }
        tbody td {
            padding: .625rem .75rem;
            border-bottom: 1px solid var(--bg-light);
            color: var(--text);
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }

        /* ── Totals ── */
        .totals {
            margin-left: auto;
            width: 300px;
            border-top: 1px solid var(--border);
            padding-top: .875rem;
            margin-bottom: 1.5rem;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: .875rem;
            padding: .25rem 0;
        }
        .total-row.grand {
            border-top: 2px solid var(--green);
            margin-top: .5rem;
            padding-top: .625rem;
            font-size: 1rem;
            font-weight: 700;
            color: var(--green);
        }

        /* ── Payment records ── */
        .payment-section { margin-bottom: 1.5rem; }

        /* ── Status badges ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .6875rem;
            font-weight: 700;
            padding: .2rem .55rem;
            border-radius: 999px;
            text-transform: capitalize;
        }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-yellow { background: #fef9c3; color: #a16207; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-gray   { background: #f3f4f6; color: #6b7280; }
        .badge-blue   { background: #dbeafe; color: #1d4ed8; }

        /* ── Delivery slip section ── */
        .slip-divider {
            border: none;
            border-top: 2px dashed var(--border);
            margin: 2rem 0;
        }
        .slip-header {
            text-align: center;
            margin-bottom: 1.25rem;
        }
        .slip-header h3 { font-size: 1rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .slip-header p  { font-size: .8125rem; color: var(--text-muted); margin-top: .25rem; }

        /* ── Footer ── */
        .receipt-footer {
            text-align: center;
            font-size: .75rem;
            color: var(--text-muted);
            border-top: 1px solid var(--border);
            padding-top: 1rem;
            margin-top: 1.5rem;
        }
        .receipt-footer strong { color: var(--text); }

        /* ── Print button (screen only) ── */
        .print-actions {
            display: flex;
            justify-content: flex-end;
            gap: .75rem;
            margin-bottom: 1.5rem;
        }
        .btn-print {
            padding: .5rem 1.25rem;
            font-size: .875rem;
            font-weight: 600;
            font-family: inherit;
            color: #fff;
            background: linear-gradient(135deg, var(--green-dark), var(--green));
            border: none;
            border-radius: .5rem;
            cursor: pointer;
        }
        .btn-back {
            padding: .5rem 1.25rem;
            font-size: .875rem;
            font-weight: 600;
            font-family: inherit;
            color: var(--text-muted);
            background: transparent;
            border: 1px solid var(--border);
            border-radius: .5rem;
            cursor: pointer;
            text-decoration: none;
        }

        /* ── Print styles ── */
        @media print {
            body { background: #fff; padding: 0; }
            .page { box-shadow: none; border-radius: 0; max-width: 100%; padding: 1.5rem; }
            .print-actions { display: none; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body>

@php
    $totalPaid  = $order->sales->sum('amount');
    $remaining  = max(0, $order->total_amount - $totalPaid);
    $pmap = ['unpaid'=>'badge-red','partial'=>'badge-yellow','paid'=>'badge-green'];
    $smap = ['pending'=>'badge-gray','processing'=>'badge-blue','completed'=>'badge-green','cancelled'=>'badge-red'];
    $dmap = ['scheduled'=>'badge-gray','in_transit'=>'badge-blue','delivered'=>'badge-green','cancelled'=>'badge-red'];
@endphp

{{-- Print / Back buttons (screen only) --}}
<div class="print-actions">
    <a href="{{ route('orders.show', $order) }}" class="btn-back">← Back to Order</a>
    <button class="btn-print" onclick="window.print()">🖨 Print</button>
</div>

<div class="page">

    {{-- ─── RECEIPT ─────────────────────────────────────────────── --}}
    <div class="receipt-header">
        <div class="farm-logo">
            <img src="{{ asset('logo-mushroom.png') }}" alt="Logo" class="logo-icon">
            <div>
                <div class="farm-name">{{ $farmName }}</div>
                @if($farmAddress)<div class="farm-sub">{{ $farmAddress }}</div>@endif
                @if($farmContact)<div class="farm-sub">{{ $farmContact }}</div>@endif
            </div>
        </div>
        <div class="receipt-meta">
            <div class="receipt-no">{{ $order->order_no }}</div>
            <div class="receipt-date">Order Date: {{ $order->order_date->format('F j, Y') }}</div>
            <div class="receipt-date">Delivery Date: {{ $order->delivery_date->format('F j, Y') }}</div>
            <div style="margin-top:.375rem;">
                <span class="badge {{ $smap[$order->order_status]??'badge-gray' }}">{{ ucfirst($order->order_status) }}</span>
                <span class="badge {{ $pmap[$order->payment_status]??'badge-gray' }}" style="margin-left:.25rem;">{{ ucfirst($order->payment_status) }}</span>
            </div>
        </div>
    </div>

    {{-- Customer & Order Info --}}
    <div class="info-grid">
        <div class="info-box">
            <div class="section-title">Bill To</div>
            <div style="font-size:.9375rem;font-weight:600;color:var(--text);margin-bottom:.375rem;">{{ $order->customer?->customer_name ?? '—' }}</div>
            @if($order->customer?->contact_person)
            <div style="font-size:.8125rem;color:var(--text-muted);">Attn: {{ $order->customer->contact_person }}</div>
            @endif
            @if($order->customer?->phone)
            <div style="font-size:.8125rem;color:var(--text-muted);">📞 {{ $order->customer->phone }}</div>
            @endif
            @if($order->customer?->email)
            <div style="font-size:.8125rem;color:var(--text-muted);">✉ {{ $order->customer->email }}</div>
            @endif
            @if($order->customer?->address)
            <div style="font-size:.8125rem;color:var(--text-muted);margin-top:.25rem;">{{ $order->customer->address }}</div>
            @endif
        </div>

        <div class="info-box">
            <div class="section-title">Order Summary</div>
            <div class="info-row"><span class="info-label">Order No.</span><span class="info-value" style="color:var(--green);font-weight:700;">{{ $order->order_no }}</span></div>
            <div class="info-row"><span class="info-label">Order Date</span><span class="info-value">{{ $order->order_date->format('M d, Y') }}</span></div>
            <div class="info-row"><span class="info-label">Delivery Date</span><span class="info-value">{{ $order->delivery_date->format('M d, Y') }}</span></div>
            @if($order->notes)
            <div class="info-row" style="margin-top:.375rem;"><span class="info-label">Notes</span><span class="info-value" style="font-size:.75rem;text-align:right;max-width:160px;">{{ $order->notes }}</span></div>
            @endif
        </div>
    </div>

    {{-- Line Items --}}
    <div class="section-title">Items</div>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Qty (kg)</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $order->item_name }}</strong>
                    <div style="font-size:.75rem;color:var(--text-muted);">Fresh mushrooms — {{ $order->order_no }}</div>
                </td>
                <td class="text-right">{{ number_format($order->quantity_kg, 2) }}</td>
                <td class="text-right">₱{{ number_format($order->unit_price, 2) }}</td>
                <td class="text-right" style="font-weight:600;">₱{{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <div class="total-row"><span>Subtotal</span><span>₱{{ number_format($order->total_amount, 2) }}</span></div>
        <div class="total-row"><span>Amount Paid</span><span style="color:#15803d;">₱{{ number_format($totalPaid, 2) }}</span></div>
        @if($remaining > 0)
        <div class="total-row"><span style="color:#b91c1c;">Balance Due</span><span style="color:#b91c1c;">₱{{ number_format($remaining, 2) }}</span></div>
        @endif
        <div class="total-row grand">
            <span>Total</span>
            <span>₱{{ number_format($order->total_amount, 2) }}</span>
        </div>
    </div>

    {{-- Payment Records --}}
    @if($order->sales->isNotEmpty())
    <div class="payment-section">
        <div class="section-title">Payment History</div>
        <table>
            <thead>
                <tr><th>Date</th><th>Method</th><th>Remarks</th><th class="text-right">Amount</th></tr>
            </thead>
            <tbody>
                @foreach($order->sales->sortBy('sale_date') as $sale)
                <tr>
                    <td>{{ $sale->sale_date->format('M d, Y') }}</td>
                    <td>{{ $sale->payment_method }}</td>
                    <td style="color:var(--text-muted);font-size:.8125rem;">{{ $sale->remarks ?? '—' }}</td>
                    <td class="text-right" style="color:#15803d;font-weight:600;">₱{{ number_format($sale->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ─── CUT HERE / DELIVERY SLIP ─────────────────────────────── --}}
    <hr class="slip-divider">

    <div class="slip-header">
        <h3>Delivery Slip</h3>
        <p>{{ $order->order_no }} · {{ $order->delivery_date->format('F j, Y') }}</p>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="section-title">Deliver To</div>
            <div style="font-size:.9375rem;font-weight:600;margin-bottom:.375rem;">{{ $order->customer?->customer_name ?? '—' }}</div>
            @if($order->delivery?->destination)
                <div style="font-size:.8125rem;color:var(--text-muted);">{{ $order->delivery->destination }}</div>
            @elseif($order->customer?->address)
                <div style="font-size:.8125rem;color:var(--text-muted);">{{ $order->customer->address }}</div>
            @endif
            @if($order->customer?->phone)
            <div style="font-size:.8125rem;color:var(--text-muted);margin-top:.25rem;">📞 {{ $order->customer->phone }}</div>
            @endif
        </div>

        <div class="info-box">
            <div class="section-title">Delivery Details</div>
            @if($order->delivery)
            <div class="info-row"><span class="info-label">Status</span>
                <span class="info-value"><span class="badge {{ $dmap[$order->delivery->transport_status]??'badge-gray' }}">{{ ucfirst(str_replace('_',' ',$order->delivery->transport_status)) }}</span></span>
            </div>
            <div class="info-row"><span class="info-label">Date</span><span class="info-value">{{ $order->delivery->delivery_date->format('M d, Y') }}</span></div>
            @if($order->delivery->assigned_personnel)
            <div class="info-row"><span class="info-label">Personnel</span><span class="info-value">{{ $order->delivery->assigned_personnel }}</span></div>
            @endif
            @if($order->delivery->vehicle_info)
            <div class="info-row"><span class="info-label">Vehicle</span><span class="info-value">{{ $order->delivery->vehicle_info }}</span></div>
            @endif
            @else
            <div style="font-size:.8125rem;color:var(--text-muted);">No delivery record assigned.</div>
            @endif
        </div>
    </div>

    {{-- Goods list on slip --}}
    <table>
        <thead>
            <tr><th>Item</th><th class="text-right">Qty (kg)</th><th class="text-right">Unit Price</th><th class="text-right">Total</th></tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $order->item_name }}</strong></td>
                <td class="text-right">{{ number_format($order->quantity_kg, 2) }}</td>
                <td class="text-right">₱{{ number_format($order->unit_price, 2) }}</td>
                <td class="text-right" style="font-weight:600;">₱{{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Signature lines --}}
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:2rem;margin-top:2rem;">
        @foreach(['Prepared by','Checked by','Received by'] as $label)
        <div>
            <div style="border-top:1px solid var(--text);padding-top:.375rem;font-size:.75rem;color:var(--text-muted);text-align:center;">{{ $label }}</div>
            <div style="height:2.5rem;"></div>
            <div style="border-top:1px solid var(--border);"></div>
            <div style="font-size:.6875rem;color:var(--text-muted);text-align:center;margin-top:.25rem;">Signature / Date</div>
        </div>
        @endforeach
    </div>

    <div class="receipt-footer">
        <strong>{{ $farmName }}</strong>
        @if($farmAddress) · {{ $farmAddress }} @endif
        @if($farmContact) · {{ $farmContact }} @endif
        <br>
        <span>Generated {{ now()->format('M d, Y H:i') }} · {{ $order->order_no }}</span>
    </div>

</div>

<script>
    // Auto-print when opened in a new tab (optional — comment out if unwanted)
    // window.onload = () => window.print();
</script>

</body>
</html>
