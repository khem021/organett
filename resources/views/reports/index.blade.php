@extends('layouts.app')
@section('title','Reports')
@section('page-title','Reports & Analytics')
@section('page-step','6')

@section('content')

{{-- ── Export Income Report ── --}}
<div class="card" style="margin-bottom:1.25rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div class="card-title" style="margin-bottom:.25rem;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export Income Report
            </div>
            <p style="font-size:.8125rem;color:var(--text-muted);">Download a CSV file that opens directly in Excel. Choose a preset or select a custom period.</p>
        </div>

        {{-- Quick preset buttons --}}
        <div style="display:flex;align-items:center;gap:.625rem;flex-wrap:wrap;">
            @php
            $presets = [
                'weekly'  => ['Weekly',  '#0c4a6e22', '#38bdf8', '#0c4a6e55'],
                'monthly' => ['Monthly', '#14532d22', '#4ade80', '#14532d55'],
                'yearly'  => ['Yearly',  '#78350f22', '#fbbf24', '#78350f55'],
            ];
            @endphp

            @foreach($presets as $key => [$label, $bg, $color, $border])
                @if(in_array($key, $exportPeriods))
                <a href="{{ route('reports.export', ['period' => $key]) }}"
                   style="display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;font-size:.8125rem;font-weight:600;font-family:inherit;border-radius:.5rem;background:{{ $bg }};color:{{ $color }};border:1px solid {{ $border }};text-decoration:none;transition:opacity .15s;"
                   onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    {{ $label }}
                </a>
                @endif
            @endforeach

            {{-- Custom period selector --}}
            @if(count($exportPeriods) > 0)
            <div style="display:flex;align-items:center;gap:.5rem;padding:.375rem .75rem;background:var(--card-bg);border:1px solid var(--card-border);border-radius:.5rem;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <select id="custom-period" style="background:transparent;border:none;color:var(--text-muted);font-size:.8125rem;font-family:inherit;outline:none;cursor:pointer;">
                    <option value="">Custom period…</option>
                    @foreach(['daily'=>'Daily (Last 30 days)','weekly'=>'Weekly (Last 12 weeks)','monthly'=>'Monthly (Last 12 months)','yearly'=>'Yearly (All time)'] as $key => $lbl)
                        @if(in_array($key, $exportPeriods))
                            <option value="{{ $key }}">{{ $lbl }}</option>
                        @endif
                    @endforeach
                </select>
                <button onclick="exportCustom()" style="padding:.25rem .625rem;font-size:.75rem;font-weight:600;font-family:inherit;background:var(--green-mid);color:var(--green-light);border:1px solid var(--card-border);border-radius:.375rem;cursor:pointer;transition:opacity .15s;" onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
                    Export
                </button>
            </div>
            @endif

            @if(empty($exportPeriods))
            <span style="font-size:.8125rem;color:var(--text-muted);font-style:italic;">No export formats enabled. Contact your administrator.</span>
            @endif
        </div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="grid-4">
    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Total Yield</span>
            <div class="stat-icon" style="background:#14532d22;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
            </div>
        </div>
        <div class="stat-value">{{ number_format($totalYield,1) }}<span style="font-size:1rem;font-weight:500;color:var(--text-muted);">kg</span></div>
        <div class="stat-sub">This month: <span>{{ number_format($monthYield,1) }} kg</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Total Revenue</span>
            <div class="stat-icon" style="background:#14532d22;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:1.375rem;">₱{{ number_format($totalRevenue,0) }}</div>
        <div class="stat-sub">This month: <span>₱{{ number_format($monthRevenue,0) }}</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Completed Batches</span>
            <div class="stat-icon" style="background:#14532d22;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
        </div>
        <div class="stat-value">{{ $batchStats->get('completed', 0) + $batchStats->get('harvested', 0) }}</div>
        <div class="stat-sub">Contaminated: <span style="color:var(--danger);">{{ $batchStats->get('contaminated', 0) }}</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Active Batches</span>
            <div class="stat-icon" style="background:#0c4a6e22;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
        </div>
        <div class="stat-value">{{ ($batchStats->get('planned',0) + $batchStats->get('inoculated',0) + $batchStats->get('fruiting',0)) }}</div>
        <div class="stat-sub">Planned · Inoculated · Fruiting</div>
    </div>
</div>

<div class="grid-2 gap-top">

    {{-- Monthly Yield Chart --}}
    <div class="card">
        <div class="card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Monthly Yield — Last 6 Months (kg)
        </div>
        <div style="display:flex;align-items:flex-end;gap:.625rem;height:140px;padding-bottom:.5rem;border-bottom:1px solid var(--card-border);">
            @foreach($monthlyYield as $m)
            @php $h = $maxMonthlyYield > 0 ? round(($m['kg'] / $maxMonthlyYield) * 120) : 0; @endphp
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:.375rem;">
                <span style="font-size:.6rem;color:var(--text-muted);">{{ $m['kg'] > 0 ? number_format($m['kg'],0) : '' }}</span>
                <div style="width:100%;height:{{ $h }}px;background:linear-gradient(180deg,#16a34a,#14532d);border-radius:.25rem .25rem 0 0;min-height:{{ $m['kg'] > 0 ? 4 : 0 }}px;transition:height .3s;"></div>
            </div>
            @endforeach
        </div>
        <div style="display:flex;gap:.625rem;margin-top:.5rem;">
            @foreach($monthlyYield as $m)
            <div style="flex:1;text-align:center;font-size:.6rem;color:var(--text-dim);">{{ $m['label'] }}</div>
            @endforeach
        </div>
    </div>

    {{-- Monthly Revenue Chart --}}
    <div class="card">
        <div class="card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Monthly Revenue — Last 6 Months (₱)
        </div>
        <div style="display:flex;align-items:flex-end;gap:.625rem;height:140px;padding-bottom:.5rem;border-bottom:1px solid var(--card-border);">
            @foreach($monthlyRevenue as $m)
            @php $h = $maxMonthlyRevenue > 0 ? round(($m['amount'] / $maxMonthlyRevenue) * 120) : 0; @endphp
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:.375rem;">
                <span style="font-size:.6rem;color:var(--text-muted);">{{ $m['amount'] > 0 ? '₱'.number_format($m['amount']/1000,1).'k' : '' }}</span>
                <div style="width:100%;height:{{ $h }}px;background:linear-gradient(180deg,#38bdf8,#0c4a6e);border-radius:.25rem .25rem 0 0;min-height:{{ $m['amount'] > 0 ? 4 : 0 }}px;"></div>
            </div>
            @endforeach
        </div>
        <div style="display:flex;gap:.625rem;margin-top:.5rem;">
            @foreach($monthlyRevenue as $m)
            <div style="flex:1;text-align:center;font-size:.6rem;color:var(--text-dim);">{{ $m['label'] }}</div>
            @endforeach
        </div>
    </div>

</div>

<div class="grid-2 gap-top">

    {{-- Top Customers --}}
    <div class="card">
        <div class="card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Top Customers by Revenue
        </div>
        @forelse($topCustomers as $i => $c)
        @php $maxSales = $topCustomers->first()->total_sales ?: 1; @endphp
        <div style="margin-bottom:.875rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.375rem;">
                <div style="display:flex;align-items:center;gap:.5rem;">
                    <span style="width:1.25rem;height:1.25rem;background:var(--green-mid);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.625rem;font-weight:700;color:var(--green-light);">{{ $i+1 }}</span>
                    <span style="font-size:.8125rem;font-weight:600;color:var(--text);">{{ $c->customer_name }}</span>
                </div>
                <span style="font-size:.8125rem;font-weight:700;color:var(--green-light);">₱{{ number_format($c->total_sales,0) }}</span>
            </div>
            <div style="height:4px;background:var(--card-border);border-radius:999px;overflow:hidden;">
                <div style="width:{{ round(($c->total_sales/$maxSales)*100) }}%;height:100%;background:linear-gradient(90deg,var(--green-mid),var(--green-accent));border-radius:999px;"></div>
            </div>
        </div>
        @empty
            <div class="empty-state">No sales data yet.</div>
        @endforelse
    </div>

    {{-- Batch Status + Harvest Grade Breakdown --}}
    <div class="card">
        <div class="card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Batch Status Breakdown
        </div>
        @foreach(['planned'=>'badge-gray','inoculated'=>'badge-blue','fruiting'=>'badge-green','harvested'=>'badge-yellow','completed'=>'badge-green','contaminated'=>'badge-red'] as $status => $cls)
        @if(($batchStats->get($status,0)) > 0)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #0f2018;">
            <span class="badge {{ $cls }}"><span class="badge-dot"></span>{{ ucfirst($status) }}</span>
            <span style="font-size:.875rem;font-weight:700;color:var(--text);">{{ $batchStats->get($status,0) }}</span>
        </div>
        @endif
        @endforeach

        <div class="card-title" style="margin-top:1.25rem;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
            Harvest by Grade (kg)
        </div>
        @foreach(['A'=>['badge-green','Premium'],'B'=>['badge-yellow','Standard'],'C'=>['badge-red','Processing']] as $grade => [$cls,$label])
        @php $kg = $gradeBreakdown->get($grade, 0); @endphp
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #0f2018;">
            <div style="display:flex;align-items:center;gap:.5rem;">
                <span class="badge {{ $cls }}">Grade {{ $grade }}</span>
                <span style="font-size:.75rem;color:var(--text-muted);">{{ $label }}</span>
            </div>
            <span style="font-size:.875rem;font-weight:700;color:var(--text);">{{ number_format($kg,2) }} kg</span>
        </div>
        @endforeach
    </div>

</div>

<script>
function exportCustom() {
    const sel = document.getElementById('custom-period');
    if (!sel.value) { sel.focus(); return; }
    window.location.href = '{{ route('reports.export') }}?period=' + sel.value;
}
</script>

@endsection
