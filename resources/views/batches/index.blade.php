@extends('layouts.app')
@section('title','Batches')
@section('page-title','Production Batches')
@section('page-step','1')

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

{{-- Table --}}
<div class="card">
    @if($batches->isEmpty())
        <div class="empty-state">No batches found. Create your first batch to get started.</div>
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
            <tr>
                <td class="text-main">{{ $batch->batch_code }}</td>
                <td>{{ $batch->substrate_type }}</td>
                <td>{{ $batch->spawn_type }}</td>
                <td>
                    @php $map = ['planned'=>'badge-gray','inoculated'=>'badge-blue','fruiting'=>'badge-green','harvested'=>'badge-yellow','completed'=>'badge-green','contaminated'=>'badge-red']; @endphp
                    <span class="badge {{ $map[$batch->status] ?? 'badge-gray' }}"><span class="badge-dot"></span>{{ ucfirst($batch->status) }}</span>
                </td>
                <td>{{ $batch->inoculation_date->format('M d, Y') }}</td>
                <td>{{ $batch->expected_harvest_date->format('M d, Y') }}</td>
                <td>{{ $batch->creator?->full_name ?? '—' }}</td>
                <td style="white-space:nowrap;">
                    <a href="{{ route('batches.show', $batch) }}" class="btn-sm btn-sm-blue">View</a>
                    <form method="POST" action="{{ route('batches.destroy', $batch) }}" style="display:inline" onsubmit="return true">
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
                <input type="text" name="batch_code" class="form-input" placeholder="BATCH-2026-009" required>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach(['planned','inoculated','fruiting','harvested','completed','contaminated'] as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Substrate Type</label>
                <input type="text" name="substrate_type" class="form-input" placeholder="Sawdust + Rice Bran" required>
            </div>
            <div class="form-group">
                <label class="form-label">Spawn Type</label>
                <input type="text" name="spawn_type" class="form-input" placeholder="Pink Oyster Spawn" required>
            </div>
            <div class="form-group">
                <label class="form-label">Inoculation Date</label>
                <input type="date" name="inoculation_date" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Expected Harvest Date</label>
                <input type="date" name="expected_harvest_date" class="form-input" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-textarea" placeholder="Optional notes…"></textarea>
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
