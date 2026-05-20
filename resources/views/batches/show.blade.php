@extends('layouts.app')
@section('title', $batch->batch_code)
@section('page-title', 'Batch Detail')

@section('content')

@php
    $records      = $batch->harvestRecords;
    $totalKg      = $records->sum('quantity_kg');
    $gradeA       = $records->where('quality_grade','A')->sum('quantity_kg');
    $gradeB       = $records->where('quality_grade','B')->sum('quantity_kg');
    $gradeC       = $records->where('quality_grade','C')->sum('quantity_kg');
    $flushCount   = $records->count();
    $daysSince    = $batch->inoculation_date->diffInDays(now());
    $daysLeft     = now()->lt($batch->expected_harvest_date)
                        ? now()->diffInDays($batch->expected_harvest_date)
                        : null;
    $isOverdue    = now()->gt($batch->expected_harvest_date) && !in_array($batch->status, ['harvested','completed','contaminated']);
    $statusMap    = ['planned'=>'badge-gray','inoculated'=>'badge-blue','fruiting'=>'badge-green','harvested'=>'badge-yellow','completed'=>'badge-green','contaminated'=>'badge-red'];
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h2>{{ $batch->batch_code }}</h2>
        <p>
            Created by {{ $batch->creator?->full_name ?? '—' }} · {{ $batch->created_at->format('M d, Y') }}
            &nbsp;·&nbsp;
            <span class="badge {{ $statusMap[$batch->status]??'badge-gray' }}">
                <span class="badge-dot"></span>{{ ucfirst($batch->status) }}
            </span>
            @if($isOverdue)
                &nbsp;<span class="badge badge-red"><span class="badge-dot"></span>Overdue</span>
            @endif
        </p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('batches.index') }}" class="btn-secondary" style="font-size:.875rem;padding:.45rem .9rem;">← Back</a>
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('edit-batch').showModal()">Edit Batch</button>
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('log-harvest').showModal()">+ Log Harvest</button>
    </div>
</div>

{{-- Stat cards --}}
<div class="grid-4" style="margin-bottom:1rem;">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Total Harvested</span>
            <div class="stat-icon" style="background:#14532d33;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--green-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 0 1 10 10"/><path d="M12 6v6l4 2"/></svg>
            </div>
        </div>
        <div class="stat-value">{{ number_format($totalKg, 2) }} <span style="font-size:1rem;font-weight:500;">kg</span></div>
        <div class="stat-sub">{{ $flushCount }} flush{{ $flushCount !== 1 ? 'es' : '' }} logged</div>
    </div>

    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Grade A (Premium)</span></div>
        <div class="stat-value" style="color:var(--green-light);">{{ number_format($gradeA, 2) }} <span style="font-size:1rem;font-weight:500;">kg</span></div>
        <div class="stat-sub">
            @if($totalKg > 0)
                <span>{{ number_format(($gradeA/$totalKg)*100, 1) }}%</span> of total
            @else
                No harvest yet
            @endif
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Grade B / C</span></div>
        <div class="stat-value" style="color:var(--warning);">{{ number_format($gradeB + $gradeC, 2) }} <span style="font-size:1rem;font-weight:500;">kg</span></div>
        <div class="stat-sub">B: {{ number_format($gradeB,2) }} kg · C: {{ number_format($gradeC,2) }} kg</div>
    </div>

    <div class="stat-card">
        <div class="stat-header"><span class="stat-label">Timeline</span></div>
        <div class="stat-value" style="font-size:1.375rem;">
            @if($daysLeft !== null)
                <span style="color:{{ $daysLeft <= 3 ? 'var(--warning)' : 'var(--text)' }};">{{ $daysLeft }}d</span>
                <span style="font-size:1rem;font-weight:400;color:var(--text-muted);"> left</span>
            @else
                <span style="color:{{ $isOverdue ? 'var(--danger)' : 'var(--text-muted)' }};">{{ $daysSince }}d</span>
                <span style="font-size:1rem;font-weight:400;color:var(--text-muted);"> {{ $isOverdue ? 'overdue' : 'in' }}</span>
            @endif
        </div>
        <div class="stat-sub">Inoculated {{ $batch->inoculation_date->format('M d') }} · Harvest {{ $batch->expected_harvest_date->format('M d') }}</div>
    </div>
</div>

{{-- Grade distribution bar --}}
@if($totalKg > 0)
<div class="card" style="margin-bottom:1rem;padding:.875rem 1.25rem;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;margin-bottom:.625rem;">
        <span style="font-size:.75rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);">Grade Distribution</span>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem 1rem;font-size:.75rem;color:var(--text-muted);">
            <span style="color:var(--green-light);">■ Grade A {{ number_format(($gradeA/$totalKg)*100,1) }}%</span>
            <span style="color:var(--warning);">■ Grade B {{ number_format(($gradeB/$totalKg)*100,1) }}%</span>
            <span style="color:var(--danger);">■ Grade C {{ number_format(($gradeC/$totalKg)*100,1) }}%</span>
        </div>
    </div>
    <div style="height:10px;background:var(--card-border);border-radius:999px;overflow:hidden;display:flex;">
        @if($gradeA > 0)<div style="width:{{ ($gradeA/$totalKg)*100 }}%;background:var(--green-accent);"></div>@endif
        @if($gradeB > 0)<div style="width:{{ ($gradeB/$totalKg)*100 }}%;background:var(--warning);"></div>@endif
        @if($gradeC > 0)<div style="width:{{ ($gradeC/$totalKg)*100 }}%;background:var(--danger);"></div>@endif
    </div>
</div>
@endif

<div class="grid-2">
    {{-- Batch Info --}}
    <div class="card">
        <div class="card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
            Batch Information
        </div>
        <table>
            <tbody>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;width:45%;">Batch Code</td><td class="text-main" style="padding:.5rem .75rem;font-family:monospace;letter-spacing:.03em;">{{ $batch->batch_code }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Substrate</td><td class="text-main" style="padding:.5rem .75rem;">{{ $batch->substrate_type }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Spawn Type</td><td class="text-main" style="padding:.5rem .75rem;">{{ $batch->spawn_type }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Status</td>
                    <td style="padding:.5rem .75rem;">
                        <span class="badge {{ $statusMap[$batch->status]??'badge-gray' }}"><span class="badge-dot"></span>{{ ucfirst($batch->status) }}</span>
                    </td>
                </tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Inoculation Date</td><td class="text-main" style="padding:.5rem .75rem;">{{ $batch->inoculation_date->format('M d, Y') }}</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Expected Harvest</td>
                    <td style="padding:.5rem .75rem;">
                        <span style="color:{{ $isOverdue ? 'var(--danger)' : 'var(--text)' }};font-weight:500;">
                            {{ $batch->expected_harvest_date->format('M d, Y') }}
                            @if($isOverdue) <span style="font-size:.75rem;">(overdue)</span>@endif
                        </span>
                    </td>
                </tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Days Running</td><td style="padding:.5rem .75rem;color:var(--text-muted);">{{ $daysSince }} days since inoculation</td></tr>
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Total Harvested</td><td style="padding:.5rem .75rem;color:var(--green-light);font-weight:700;">{{ number_format($totalKg,2) }} kg</td></tr>
                @if($batch->notes)
                <tr><td style="color:var(--text-muted);padding:.5rem .75rem;">Notes</td><td style="padding:.5rem .75rem;font-size:.8125rem;color:var(--text-muted);">{{ $batch->notes }}</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Harvest Records --}}
    <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <div class="card-title" style="margin-bottom:0;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Harvest Records
            </div>
            <button class="btn-sm btn-sm-green" onclick="document.getElementById('log-harvest').showModal()">+ Log</button>
        </div>

        @if($records->isEmpty())
            <div class="empty-state">No harvests logged yet for this batch.</div>
        @else
        <table>
            <thead>
                <tr><th>Date</th><th>Qty (kg)</th><th>Grade</th><th>Notes</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($records->sortByDesc('harvest_date') as $hr)
                @php $gc=['A'=>'badge-green','B'=>'badge-yellow','C'=>'badge-red']; @endphp
                <tr>
                    <td style="font-size:.75rem;">{{ $hr->harvest_date->format('M d, Y') }}</td>
                    <td style="color:var(--green-light);font-weight:600;">{{ number_format($hr->quantity_kg,2) }}</td>
                    <td><span class="badge {{ $gc[$hr->quality_grade]??'badge-gray' }}">Grade {{ $hr->quality_grade }}</span></td>
                    <td style="font-size:.75rem;color:var(--text-muted);max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $hr->notes ?? '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('harvest.destroy', $hr) }}" onsubmit="return true">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-sm btn-sm-red">Del</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Per-flush totals footer --}}
        <div style="margin-top:.75rem;padding-top:.75rem;border-top:1px solid var(--card-border);display:flex;justify-content:space-between;font-size:.8125rem;">
            <span style="color:var(--text-muted);">Total yield · {{ $flushCount }} flush{{ $flushCount !== 1 ? 'es' : '' }}</span>
            <span style="font-weight:700;color:var(--green-light);">{{ number_format($totalKg, 2) }} kg</span>
        </div>
        @endif
    </div>
</div>

{{-- Edit Modal --}}
<dialog id="edit-batch">
    <div class="modal-title">Edit Batch <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" action="{{ route('batches.update', $batch) }}">
        @csrf @method('PUT')
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Substrate Type</label>
                <input type="text" name="substrate_type" class="form-input" value="{{ $batch->substrate_type }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Spawn Type</label>
                <input type="text" name="spawn_type" class="form-input" value="{{ $batch->spawn_type }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Inoculation Date</label>
                <input type="date" name="inoculation_date" class="form-input" value="{{ $batch->inoculation_date->format('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Expected Harvest Date</label>
                <input type="date" name="expected_harvest_date" class="form-input" value="{{ $batch->expected_harvest_date->format('Y-m-d') }}" required>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach(['planned','inoculated','fruiting','harvested','completed','contaminated'] as $s)
                        <option value="{{ $s }}" @selected($batch->status===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-textarea">{{ $batch->notes }}</textarea>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Save Changes</button>
        </div>
    </form>
</dialog>

{{-- Log Harvest Modal --}}
<dialog id="log-harvest">
    <div class="modal-title">Log Harvest — {{ $batch->batch_code }} <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" action="{{ route('harvest.store') }}">
        @csrf
        <input type="hidden" name="batch_id" value="{{ $batch->id }}">
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Harvest Date</label>
                <input type="date" name="harvest_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Quality Grade</label>
                <select name="quality_grade" class="form-select" required>
                    <option value="A">Grade A — Premium</option>
                    <option value="B">Grade B — Standard</option>
                    <option value="C">Grade C — Processing</option>
                </select>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Quantity (kg)</label>
                <input type="number" name="quantity_kg" class="form-input" step="0.01" min="0.01" placeholder="0.00" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-textarea" placeholder="Flush no., observations, room conditions…"></textarea>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Log Harvest</button>
        </div>
    </form>
</dialog>

@endsection
