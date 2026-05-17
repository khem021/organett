@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
@endpush

@section('content')

{{-- ── Stat Cards ── --}}
<div class="grid-4">

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Active Batches</span>
            <div class="stat-icon" style="background:#14532d22;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                    <polyline points="2 17 12 22 22 17"/>
                    <polyline points="2 12 12 17 22 12"/>
                </svg>
            </div>
        </div>
        <div class="stat-value">{{ $activeBatches }}</div>
        <div class="stat-sub">
            @if($fruitingCount > 0)
                <span>{{ $fruitingCount }}</span> fruiting now
            @else
                Planned · Inoculated · Fruiting
            @endif
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Monthly Yield</span>
            <div class="stat-icon" style="background:#78350f22;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a10 10 0 1 0 10 10"/>
                    <path d="M12 6v6l4 2"/>
                </svg>
            </div>
        </div>
        <div class="stat-value">
            {{ number_format($monthYield, 1) }}<span style="font-size:1rem;font-weight:500;color:var(--text-muted);margin-left:.25rem;">kg</span>
        </div>
        <div class="stat-sub">
            @if($yieldChange !== null)
                @if($yieldChange >= 0)
                    <span>+{{ $yieldChange }}%</span> vs last month
                @else
                    <span class="danger">{{ $yieldChange }}%</span> vs last month
                @endif
            @else
                This month so far
            @endif
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Pending Orders</span>
            <div class="stat-icon" style="background:#0c4a6e22;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13"/>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                    <circle cx="5.5" cy="18.5" r="2.5"/>
                    <circle cx="18.5" cy="18.5" r="2.5"/>
                </svg>
            </div>
        </div>
        <div class="stat-value">{{ $pendingOrders }}</div>
        <div class="stat-sub">
            @if($dispatchingToday > 0)
                <span class="warn">{{ $dispatchingToday }}</span> dispatching today
            @else
                No dispatches today
            @endif
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Stock Alerts</span>
            <div class="stat-icon" style="background:#7f1d1d22;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
        </div>
        <div class="stat-value" style="{{ $lowStockCount > 0 ? 'color:var(--danger)' : '' }}">{{ $lowStockCount }}</div>
        <div class="stat-sub">
            @if($lowStockCount > 0)
                <span class="danger">{{ $lowStockCount }} item{{ $lowStockCount > 1 ? 's' : '' }}</span> below reorder level
            @else
                All stock levels OK
            @endif
        </div>
    </div>

</div>

{{-- ── Charts ── --}}
<div class="grid-2 gap-top">

    {{-- Bar Chart: Monthly Yield --}}
    <div class="card">
        <div class="card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            Monthly Yield — Last 6 Months (kg)
        </div>
        <div style="position:relative;height:220px;">
            <canvas id="yieldChart"></canvas>
        </div>
    </div>

    {{-- Doughnut Chart: Harvest by Grade --}}
    <div class="card">
        <div class="card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 2a10 10 0 0 1 10 10"/>
            </svg>
            Harvest by Grade
        </div>
        @if($totalHarvest > 0)
        <div style="display:flex;align-items:center;gap:2rem;">
            <div style="position:relative;height:200px;width:200px;flex-shrink:0;">
                <canvas id="gradeChart"></canvas>
            </div>
            <div style="flex:1;display:flex;flex-direction:column;gap:.875rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <span style="width:10px;height:10px;border-radius:2px;background:#4ade80;flex-shrink:0;"></span>
                        <span style="font-size:.8125rem;color:var(--text);">Grade A</span>
                        <span style="font-size:.6875rem;color:var(--text-muted);">Premium</span>
                    </div>
                    <span style="font-size:.875rem;font-weight:700;color:var(--green-light);">{{ number_format($gradeA, 1) }} kg</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <span style="width:10px;height:10px;border-radius:2px;background:#fbbf24;flex-shrink:0;"></span>
                        <span style="font-size:.8125rem;color:var(--text);">Grade B</span>
                        <span style="font-size:.6875rem;color:var(--text-muted);">Standard</span>
                    </div>
                    <span style="font-size:.875rem;font-weight:700;color:var(--warning);">{{ number_format($gradeB, 1) }} kg</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <span style="width:10px;height:10px;border-radius:2px;background:#f87171;flex-shrink:0;"></span>
                        <span style="font-size:.8125rem;color:var(--text);">Grade C</span>
                        <span style="font-size:.6875rem;color:var(--text-muted);">Processing</span>
                    </div>
                    <span style="font-size:.875rem;font-weight:700;color:var(--danger);">{{ number_format($gradeC, 1) }} kg</span>
                </div>
                <div style="margin-top:.25rem;padding-top:.875rem;border-top:1px solid var(--card-border);">
                    <div style="display:flex;justify-content:space-between;font-size:.8125rem;">
                        <span style="color:var(--text-muted);">Total Harvest</span>
                        <span style="font-weight:700;color:var(--text);">{{ number_format($totalHarvest, 1) }} kg</span>
                    </div>
                </div>
            </div>
        </div>
        @else
            <div class="empty-state" style="padding:2rem 1rem;">No harvest records yet.</div>
        @endif
    </div>

</div>

{{-- ── Active Batches + Alerts ── --}}
<div class="grid-main gap-top">

    {{-- Active Batches Table --}}
    <div class="card">
        <div class="card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                <polyline points="2 17 12 22 22 17"/>
                <polyline points="2 12 12 17 22 12"/>
            </svg>
            Active Batches
            <a href="{{ route('batches.index') }}" style="margin-left:auto;font-size:.6875rem;color:var(--green-accent);text-decoration:none;font-weight:600;">View all →</a>
        </div>
        @if($activeBatchList->isEmpty())
            <div class="empty-state" style="padding:2rem 1rem;">No active batches right now.</div>
        @else
        <table>
            <thead>
                <tr>
                    <th>Batch Code</th>
                    <th>Substrate</th>
                    <th>Stage</th>
                    <th>Inoculated</th>
                    <th>Est. Harvest</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activeBatchList as $batch)
                @php
                    $statusMap = [
                        'fruiting'   => 'badge-green',
                        'inoculated' => 'badge-blue',
                        'planned'    => 'badge-gray',
                    ];
                    $badgeClass = $statusMap[$batch->status] ?? 'badge-gray';
                    $isOverdue  = $batch->expected_harvest_date->isPast() && $batch->status === 'fruiting';
                @endphp
                <tr>
                    <td class="text-main">{{ $batch->batch_code }}</td>
                    <td style="font-size:.75rem;">{{ $batch->substrate_type }}</td>
                    <td><span class="badge {{ $badgeClass }}"><span class="badge-dot"></span>{{ ucfirst($batch->status) }}</span></td>
                    <td style="font-size:.75rem;">{{ $batch->inoculation_date->format('M d') }}</td>
                    <td style="font-size:.8125rem;font-weight:600;color:{{ $isOverdue ? 'var(--danger)' : 'var(--green-light)' }};">
                        {{ $batch->expected_harvest_date->format('M d, Y') }}
                        @if($isOverdue) <span style="font-size:.65rem;">⚠ Overdue</span> @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Alerts & Quick Actions --}}
    <div style="display:flex;flex-direction:column;gap:1rem;">

        {{-- Alerts --}}
        <div class="card">
            <div class="card-title">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                Alerts
            </div>

            @if($lowStockItems->isEmpty() && $harvestAlerts->isEmpty())
                <div class="empty-state" style="padding:1.5rem 1rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--green-accent)" stroke-width="2" style="margin-bottom:.5rem;"><polyline points="20 6 9 17 4 12"/></svg>
                    <div>All clear — no alerts right now.</div>
                </div>
            @else
                {{-- Low stock alerts --}}
                @foreach($lowStockItems->take(3) as $item)
                <div class="alert-item">
                    <div class="alert-icon" style="background:#7f1d1d22;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div class="alert-body">
                        <div class="alert-title">{{ $item->item_name }} Low</div>
                        <div class="alert-desc">{{ number_format($item->stock_qty, 1) }} {{ $item->unit }} left — reorder at {{ number_format($item->reorder_level, 0) }}</div>
                    </div>
                    <div class="alert-time">Stock</div>
                </div>
                @endforeach

                {{-- Harvest-ready alerts --}}
                @foreach($harvestAlerts as $batch)
                <div class="alert-item">
                    <div class="alert-icon" style="background:#14532d22;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <div class="alert-body">
                        <div class="alert-title">{{ $batch->batch_code }} Ready</div>
                        <div class="alert-desc">
                            Fruiting — harvest by {{ $batch->expected_harvest_date->format('M d, Y') }}
                            @if($batch->expected_harvest_date->isPast())
                                <span style="color:var(--danger);"> (overdue)</span>
                            @endif
                        </div>
                    </div>
                    <div class="alert-time">Harvest</div>
                </div>
                @endforeach
            @endif
        </div>

        {{-- Quick Actions --}}
        <div class="card">
            <div class="card-title">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                Quick Actions
            </div>
            <div style="display:flex;flex-direction:column;gap:.5rem;">
                <a href="{{ route('batches.index') }}" style="display:flex;align-items:center;gap:.625rem;padding:.625rem .75rem;border-radius:.5rem;background:#0a1a0e;border:1px solid var(--card-border);text-decoration:none;color:var(--text);font-size:.8125rem;font-weight:500;transition:border-color .15s;" onmouseover="this.style.borderColor='var(--green-accent)'" onmouseout="this.style.borderColor='var(--card-border)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--green-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                    New Batch
                </a>
                <a href="{{ route('harvest.index') }}" style="display:flex;align-items:center;gap:.625rem;padding:.625rem .75rem;border-radius:.5rem;background:#0a1a0e;border:1px solid var(--card-border);text-decoration:none;color:var(--text);font-size:.8125rem;font-weight:500;transition:border-color .15s;" onmouseover="this.style.borderColor='var(--green-accent)'" onmouseout="this.style.borderColor='var(--card-border)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--green-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Log Harvest
                </a>
                <a href="{{ route('orders.index') }}" style="display:flex;align-items:center;gap:.625rem;padding:.625rem .75rem;border-radius:.5rem;background:#0a1a0e;border:1px solid var(--card-border);text-decoration:none;color:var(--text);font-size:.8125rem;font-weight:500;transition:border-color .15s;" onmouseover="this.style.borderColor='var(--green-accent)'" onmouseout="this.style.borderColor='var(--card-border)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--green-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    New Order
                </a>
                <a href="{{ route('inventory.index') }}" style="display:flex;align-items:center;gap:.625rem;padding:.625rem .75rem;border-radius:.5rem;background:#0a1a0e;border:1px solid var(--card-border);text-decoration:none;color:var(--text);font-size:.8125rem;font-weight:500;transition:border-color .15s;" onmouseover="this.style.borderColor='var(--green-accent)'" onmouseout="this.style.borderColor='var(--card-border)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--green-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    Update Inventory
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ── Inventory Levels ── --}}
<div class="card gap-top-lg">
    <div class="card-title">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
        </svg>
        Inventory Levels
        <a href="{{ route('inventory.index') }}" style="margin-left:auto;font-size:.6875rem;color:var(--green-accent);text-decoration:none;font-weight:600;">Manage →</a>
    </div>
    @if($inventoryItems->isEmpty())
        <div class="empty-state">No inventory items yet.</div>
    @else
    <div class="grid-3" style="gap:1rem;">
        @foreach($inventoryItems as $item)
        @php
            $pct     = $maxStock > 0 ? round(($item->stock_qty / $maxStock) * 100) : 0;
            $isLow   = $item->stock_qty <= $item->reorder_level;
            $barColor = $isLow ? ($item->stock_qty == 0 ? 'var(--danger)' : 'var(--warning)') : 'linear-gradient(90deg,var(--green-mid),var(--green-accent))';
            $textColor = $isLow ? ($item->stock_qty == 0 ? 'var(--danger)' : 'var(--warning)') : 'inherit';
        @endphp
        <div class="progress-row">
            <div class="progress-label" style="color:{{ $textColor }}">
                <span>{{ $item->item_name }}</span>
                <span style="color:{{ $textColor }};font-weight:{{ $isLow ? '700' : '600' }}">
                    {{ number_format($item->stock_qty, 1) }} {{ $item->unit }}
                    @if($isLow) ⚠ @endif
                </span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" style="width:{{ max($pct, $item->stock_qty > 0 ? 2 : 0) }}%;background:{{ $barColor }};"></div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ── Chart.js ── --}}
<script>
Chart.defaults.color        = '#5a8a6a';
Chart.defaults.borderColor  = '#1a3322';
Chart.defaults.font.family  = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.font.size    = 11;

// ── Bar Chart ────────────────────────────────────────────────────────────────
const yieldLabels = @json($monthlyYield->pluck('label'));
const yieldData   = @json($monthlyYield->pluck('kg'));
const barColors   = yieldData.map((_, i) =>
    i === yieldData.length - 1 ? '#16a34acc' : '#14532d99'
);
const barBorders  = yieldData.map((_, i) =>
    i === yieldData.length - 1 ? '#4ade80' : '#16a34a'
);

new Chart(document.getElementById('yieldChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: yieldLabels,
        datasets: [{
            label: 'Yield (kg)',
            data: yieldData,
            backgroundColor: barColors,
            borderColor: barBorders,
            borderWidth: 1.5,
            borderRadius: 5,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0d1f12',
                borderColor: '#1a3322',
                borderWidth: 1,
                titleColor: '#d1fae5',
                bodyColor: '#4ade80',
                callbacks: { label: ctx => ` ${ctx.parsed.y} kg` }
            }
        },
        scales: {
            x: { grid: { color: '#1a332288' }, ticks: { color: '#5a8a6a' } },
            y: {
                grid: { color: '#1a332288' },
                ticks: { color: '#5a8a6a', callback: v => v + ' kg' },
                beginAtZero: true,
            }
        }
    }
});

// ── Doughnut Chart ───────────────────────────────────────────────────────────
@if($totalHarvest > 0)
new Chart(document.getElementById('gradeChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Grade A', 'Grade B', 'Grade C'],
        datasets: [{
            data: [{{ $gradeA }}, {{ $gradeB }}, {{ $gradeC }}],
            backgroundColor: ['#16a34a99', '#fbbf2499', '#f8717199'],
            borderColor:     ['#4ade80',   '#fbbf24',   '#f87171'],
            borderWidth: 1.5,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0d1f12',
                borderColor: '#1a3322',
                borderWidth: 1,
                titleColor: '#d1fae5',
                bodyColor: '#4ade80',
                callbacks: {
                    label: ctx => ` ${ctx.parsed} kg (${Math.round(ctx.parsed / {{ $totalHarvest }} * 100)}%)`
                }
            }
        }
    }
});
@endif
</script>

@endsection
