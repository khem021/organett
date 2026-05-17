@extends('layouts.app')
@section('title','Harvest Logs')
@section('page-title','Harvest Logs')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h2>Harvest Logs</h2>
        <p>All harvests · <strong style="color:var(--green-light);">{{ number_format($totalKg,2) }} kg</strong> total · <strong style="color:var(--green-light);">{{ number_format($monthKg,2) }} kg</strong> this month</p>
    </div>
    <div class="page-header-right">
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('log-harvest').showModal()">+ Log Harvest</button>
    </div>
</div>

<form method="GET" action="{{ route('harvest.index') }}">
<div class="filter-bar">
    <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Search batch code…">
    <select name="grade" class="filter-select" onchange="this.form.submit()">
        <option value="">All Grades</option>
        <option value="A" @selected(request('grade')==='A')>Grade A</option>
        <option value="B" @selected(request('grade')==='B')>Grade B</option>
        <option value="C" @selected(request('grade')==='C')>Grade C</option>
    </select>
    <button type="submit" class="btn-secondary">Filter</button>
    @if(request()->hasAny(['search','grade']))
        <a href="{{ route('harvest.index') }}" class="btn-secondary">Clear</a>
    @endif
</div>
</form>

<div class="card">
    @if($records->isEmpty())
        <div class="empty-state">No harvest records found.</div>
    @else
    <table>
        <thead>
            <tr><th>Date</th><th>Batch</th><th>Substrate</th><th>Qty (kg)</th><th>Grade</th><th>Logged By</th><th>Notes</th><th></th></tr>
        </thead>
        <tbody>
            @foreach($records as $r)
            <tr>
                <td>{{ $r->harvest_date->format('M d, Y') }}</td>
                <td class="text-main">
                    <a href="{{ route('batches.show', $r->batch_id) }}" style="color:var(--green-light);text-decoration:none;">{{ $r->batch?->batch_code ?? '—' }}</a>
                </td>
                <td style="font-size:.75rem;">{{ $r->batch?->substrate_type ?? '—' }}</td>
                <td style="color:var(--green-light);font-weight:700;">{{ number_format($r->quantity_kg,2) }}</td>
                <td>
                    @php $gc=['A'=>'badge-green','B'=>'badge-yellow','C'=>'badge-red']; @endphp
                    <span class="badge {{ $gc[$r->quality_grade]??'badge-gray' }}"><span class="badge-dot"></span>Grade {{ $r->quality_grade }}</span>
                </td>
                <td>{{ $r->creator?->full_name ?? '—' }}</td>
                <td style="font-size:.75rem;color:var(--text-muted);max-width:180px;">{{ Str::limit($r->notes ?? '', 80, '…') ?: '—' }}</td>
                <td>
                    <form method="POST" action="{{ route('harvest.destroy', $r) }}" onsubmit="return true">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-sm btn-sm-red">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:1rem;">{{ $records->links() }}</div>
    @endif
</div>

{{-- Log Harvest Modal --}}
<dialog id="log-harvest">
    <div class="modal-title">Log Harvest Record <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" action="{{ route('harvest.store') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Batch</label>
            <select name="batch_id" class="form-select" required>
                <option value="">Select batch…</option>
                @foreach($batches as $b)
                    <option value="{{ $b->id }}">{{ $b->batch_code }} — {{ ucfirst($b->status) }}</option>
                @endforeach
            </select>
        </div>
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
            <textarea name="notes" class="form-textarea" placeholder="Flush notes…"></textarea>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Save Record</button>
        </div>
    </form>
</dialog>

@if($errors->any())
<script>document.getElementById('log-harvest').showModal();</script>
@endif
@endsection
