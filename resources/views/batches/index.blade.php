@extends('layouts.app')
@section('title','Batches')
@section('page-title','Production Batches')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h2>Production Batches</h2>
        <p>Track every grow cycle from inoculation to harvest</p>
    </div>
    <div class="page-header-right">
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('create-batch').showModal()">+ New Batch</button>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('batches.index') }}">
<div class="filter-bar">
    <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Search batch code…">
    <select name="status" class="filter-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        @foreach(['planned','inoculated','fruiting','harvested','completed','contaminated'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn-secondary">Filter</button>
    @if(request()->hasAny(['search','status']))
        <a href="{{ route('batches.index') }}" class="btn-secondary">Clear</a>
    @endif
</div>
</form>

{{-- Active filter pills --}}
@if(request()->hasAny(['search','status']))
<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;flex-wrap:wrap;">
    <span style="font-size:.75rem;color:var(--text-dim);">Filtered by:</span>
    @if(request('search'))
        <span class="filter-pill">Search: "{{ request('search') }}" <a href="{{ route('batches.index', array_diff_key(request()->query(), ['search'=>''])) }}">×</a></span>
    @endif
    @if(request('status'))
        <span class="filter-pill">{{ ucfirst(request('status')) }} <a href="{{ route('batches.index', array_diff_key(request()->query(), ['status'=>''])) }}">×</a></span>
    @endif
</div>
@endif

{{-- Table --}}
<div class="card">
    @if($batches->isEmpty())
        <div class="empty-state-full">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
            </div>
            @if(request()->hasAny(['search','status']))
                <p>No batches match your filters.</p>
                <a href="{{ route('batches.index') }}" class="btn-secondary">Clear filters</a>
            @else
                <p>No production batches yet.</p>
                <small>Create your first batch to start tracking your grow cycles.</small>
                <button class="btn-primary" onclick="document.getElementById('create-batch').showModal()">+ New Batch</button>
            @endif
        </div>
    @else
    <table>
        <thead>
            <tr>
                <th>Batch Code</th><th>Substrate</th><th>Spawn</th><th>Status</th>
                <th>Inoculated</th><th>Est. Harvest</th><th>Created By</th><th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($batches as $batch)
            @php
                $map = ['planned'=>'badge-gray','inoculated'=>'badge-blue','fruiting'=>'badge-green','harvested'=>'badge-yellow','completed'=>'badge-green','contaminated'=>'badge-red'];
                $isOverdue = $batch->expected_harvest_date->isPast() && $batch->status === 'fruiting';
            @endphp
            <tr data-href="{{ route('batches.show', $batch) }}"
                @if($isOverdue) style="background:#7f1d1d0a;" @endif>
                <td class="text-main">
                    {{ $batch->batch_code }}
                    @if($isOverdue) <span style="font-size:.65rem;color:var(--danger);margin-left:.25rem;">⚠ Overdue</span> @endif
                </td>
                <td style="font-size:.8125rem;">{{ $batch->substrate_type }}</td>
                <td style="font-size:.8125rem;">{{ $batch->spawn_type }}</td>
                <td>
                    <span class="badge {{ $map[$batch->status] ?? 'badge-gray' }}"><span class="badge-dot"></span>{{ ucfirst($batch->status) }}</span>
                </td>
                <td style="font-size:.8125rem;">{{ $batch->inoculation_date->format('M d, Y') }}</td>
                <td style="font-size:.8125rem;{{ $isOverdue ? 'color:var(--danger);font-weight:600;' : '' }}">
                    {{ $batch->expected_harvest_date->format('M d, Y') }}
                </td>
                <td style="font-size:.8125rem;">{{ $batch->creator?->full_name ?? '—' }}</td>
                <td style="white-space:nowrap;">
                    <a href="{{ route('batches.show', $batch) }}" class="btn-sm btn-sm-blue">View</a>
                    <form method="POST" action="{{ route('batches.destroy', $batch) }}" style="display:inline"
                          data-confirm="Delete batch &ldquo;{{ $batch->batch_code }}&rdquo;? This cannot be undone.">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-sm btn-sm-red">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:1rem;">{{ $batches->links() }}</div>
    @endif
</div>

{{-- Create Modal --}}
<dialog id="create-batch">
    <div class="modal-title">New Production Batch <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" action="{{ route('batches.store') }}">
        @csrf
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Batch Code</label>
                <input type="text" name="batch_code" class="form-input" value="{{ old('batch_code') }}" placeholder="BATCH-2026-009" required>
                @error('batch_code') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach(['planned','inoculated','fruiting','harvested','completed','contaminated'] as $s)
                        <option value="{{ $s }}" @selected(old('status', 'planned')===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                @error('status') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Substrate Type</label>
                <input type="text" name="substrate_type" class="form-input" value="{{ old('substrate_type') }}" placeholder="Sawdust + Rice Bran" required>
                @error('substrate_type') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Spawn Type</label>
                <input type="text" name="spawn_type" class="form-input" value="{{ old('spawn_type') }}" placeholder="Pink Oyster Spawn" required>
                @error('spawn_type') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Inoculation Date</label>
                <input type="date" name="inoculation_date" class="form-input" value="{{ old('inoculation_date') }}" required>
                @error('inoculation_date') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Expected Harvest Date</label>
                <input type="date" name="expected_harvest_date" class="form-input" value="{{ old('expected_harvest_date') }}" required>
                @error('expected_harvest_date') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-textarea" placeholder="Optional notes…">{{ old('notes') }}</textarea>
            @error('notes') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Create Batch</button>
        </div>
    </form>
</dialog>

@if($errors->any())
<script>document.getElementById('create-batch').showModal();</script>
@endif
@endsection
