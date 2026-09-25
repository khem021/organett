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

{{-- Active filter pills --}}
@if(request()->hasAny(['search','grade']))
<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;flex-wrap:wrap;">
    <span style="font-size:.75rem;color:var(--text-dim);">Filtered by:</span>
    @if(request('search'))<span class="filter-pill">Batch: "{{ request('search') }}" <a href="{{ route('harvest.index', array_diff_key(request()->query(), ['search'=>''])) }}">×</a></span>@endif
    @if(request('grade'))<span class="filter-pill">Grade {{ request('grade') }} <a href="{{ route('harvest.index', array_diff_key(request()->query(), ['grade'=>''])) }}">×</a></span>@endif
</div>
@endif

<div class="card">
    @if($records->isEmpty())
        <div class="empty-state-full">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            @if(request()->hasAny(['search','grade']))
                <p>No harvest records match your filters.</p>
                <a href="{{ route('harvest.index') }}" class="btn-secondary">Clear filters</a>
            @else
                <p>No harvest records yet.</p>
                <small>Log your first harvest from a fruiting batch.</small>
                <button class="btn-primary" onclick="document.getElementById('log-harvest').showModal()">+ Log Harvest</button>
            @endif
        </div>
    @else
    <table>
        <thead>
            <tr><th>Date</th><th>Batch</th><th>Substrate</th><th>Qty (kg)</th><th>Grade</th><th>Logged By</th><th>Notes</th><th></th></tr>
        </thead>
        <tbody>
            @foreach($records as $r)
            @php $gc=['A'=>'badge-green','B'=>'badge-yellow','C'=>'badge-red']; @endphp
            <tr data-href="{{ route('batches.show', $r->batch_id) }}">
                <td style="font-size:.8125rem;">{{ $r->harvest_date->format('M d, Y') }}</td>
                <td class="text-main">{{ $r->batch?->batch_code ?? '—' }}</td>
                <td style="font-size:.75rem;">{{ $r->batch?->substrate_type ?? '—' }}</td>
                <td style="color:var(--green-light);font-weight:700;">{{ number_format($r->quantity_kg, 2) }}</td>
                <td><span class="badge {{ $gc[$r->quality_grade] ?? 'badge-gray' }}"><span class="badge-dot"></span>Grade {{ $r->quality_grade }}</span></td>
                <td style="font-size:.8125rem;">{{ $r->creator?->full_name ?? '—' }}</td>
                <td class="cell-wrap" style="font-size:.75rem;color:var(--text-muted);max-width:180px;">{{ Str::limit($r->notes ?? '', 80, '…') ?: '—' }}</td>
                <td style="white-space:nowrap;">
                    <button class="btn-sm btn-sm-yellow"
                        onclick="openEditHarvest({{ $r->id }}, {{ $r->batch_id }}, '{{ $r->harvest_date->format('Y-m-d') }}', {{ $r->quantity_kg }}, '{{ $r->quality_grade }}', '{{ addslashes($r->notes ?? '') }}')">
                        Edit
                    </button>
                    <form method="POST" action="{{ route('harvest.destroy', $r) }}"
                          data-confirm="Delete this harvest record ({{ number_format($r->quantity_kg,2) }} kg, {{ $r->harvest_date->format('M d') }})?" style="display:inline">
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
                    <option value="{{ $b->id }}" @selected(old('batch_id') == $b->id)>{{ $b->batch_code }} — {{ ucfirst($b->status) }}</option>
                @endforeach
            </select>
            @error('batch_id') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Harvest Date</label>
                <input type="date" name="harvest_date" class="form-input" value="{{ old('harvest_date', now()->format('Y-m-d')) }}" required>
                @error('harvest_date') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Quality Grade</label>
                <select name="quality_grade" class="form-select" required>
                    <option value="A" @selected(old('quality_grade')==='A')>Grade A — Premium</option>
                    <option value="B" @selected(old('quality_grade')==='B')>Grade B — Standard</option>
                    <option value="C" @selected(old('quality_grade')==='C')>Grade C — Processing</option>
                </select>
                @error('quality_grade') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Quantity (kg)</label>
                <input type="number" name="quantity_kg" class="form-input" step="0.01" min="0.01" value="{{ old('quantity_kg') }}" placeholder="0.00" required>
                @error('quantity_kg') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-textarea" placeholder="Flush notes…">{{ old('notes') }}</textarea>
            @error('notes') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Save Record</button>
        </div>
    </form>
</dialog>

{{-- Edit Harvest Record Modal --}}
<dialog id="edit-harvest">
    <div class="modal-title">Edit Harvest Record <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" id="edit-harvest-form" action="{{ old('_edit_id') ? route('harvest.update', old('_edit_id')) : '' }}">
        @csrf @method('PUT')
        <input type="hidden" name="_edit_id" id="edit-harvest-id-field" value="{{ old('_edit_id') }}">
        <div class="form-group">
            <label class="form-label">Batch</label>
            <select name="batch_id" class="form-select" required>
                <option value="">Select batch…</option>
                @foreach($batches as $b)
                    <option value="{{ $b->id }}" @selected(old('batch_id') == $b->id)>{{ $b->batch_code }} — {{ ucfirst($b->status) }}</option>
                @endforeach
            </select>
            @error('batch_id') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Harvest Date</label>
                <input type="date" name="harvest_date" id="edit-harvest-date" class="form-input" value="{{ old('harvest_date') }}" required>
                @error('harvest_date') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Quality Grade</label>
                <select name="quality_grade" id="edit-harvest-grade" class="form-select" required>
                    <option value="A" @selected(old('quality_grade')==='A')>Grade A — Premium</option>
                    <option value="B" @selected(old('quality_grade')==='B')>Grade B — Standard</option>
                    <option value="C" @selected(old('quality_grade')==='C')>Grade C — Processing</option>
                </select>
                @error('quality_grade') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Quantity (kg)</label>
                <input type="number" name="quantity_kg" id="edit-harvest-qty" class="form-input" step="0.01" min="0.01" value="{{ old('quantity_kg') }}" required>
                @error('quantity_kg') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" id="edit-harvest-notes" class="form-textarea" placeholder="Flush notes…">{{ old('notes') }}</textarea>
            @error('notes') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Save Changes</button>
        </div>
    </form>
</dialog>

<script>
function openEditHarvest(id, batchId, date, qty, grade, notes) {
    document.getElementById('edit-harvest-form').action = '/harvest/' + id;
    document.getElementById('edit-harvest-id-field').value = id;
    document.getElementById('edit-harvest-date').value  = date;
    document.getElementById('edit-harvest-qty').value   = qty;
    document.getElementById('edit-harvest-grade').value = grade;
    document.getElementById('edit-harvest-notes').value = notes;
    // Select the batch in the dropdown
    var sel = document.querySelector('#edit-harvest select[name="batch_id"]');
    if (sel) { sel.value = batchId; }
    document.getElementById('edit-harvest').showModal();
}
</script>

@if($errors->any())
<script>document.getElementById('{{ old('_edit_id') ? 'edit-harvest' : 'log-harvest' }}').showModal();</script>
@endif
@endsection
