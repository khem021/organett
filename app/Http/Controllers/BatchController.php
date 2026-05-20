<?php

namespace App\Http\Controllers;

use App\Models\ProductionBatch;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductionBatch::with('creator')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('batch_code', 'like', '%' . $request->search . '%');
        }

        $batches = $query->paginate(15)->withQueryString();
        return view('batches.index', compact('batches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'batch_code'           => 'required|string|max:100|unique:production_batches',
            'substrate_type'       => 'required|string|max:150',
            'spawn_type'           => 'required|string|max:150',
            'inoculation_date'     => 'required|date',
            'expected_harvest_date'=> 'required|date|after:inoculation_date',
            'status'               => 'required|in:planned,inoculated,fruiting,harvested,completed,contaminated',
            'notes'                => 'nullable|string',
        ]);
        $data['created_by'] = Auth::id();
        $batch = ProductionBatch::create($data);

        ActivityLogger::log('Batches', 'create', "Created batch {$batch->batch_code}");

        return redirect()->route('batches.index')->with('success', 'Batch created successfully.');
    }

    public function show(ProductionBatch $batch)
    {
        $batch->load(['harvestRecords', 'creator']);
        return view('batches.show', compact('batch'));
    }

    public function update(Request $request, ProductionBatch $batch)
    {
        $data = $request->validate([
            'substrate_type'       => 'required|string|max:150',
            'spawn_type'           => 'required|string|max:150',
            'inoculation_date'     => 'required|date',
            'expected_harvest_date'=> 'required|date',
            'status'               => 'required|in:planned,inoculated,fruiting,harvested,completed,contaminated',
            'notes'                => 'nullable|string',
        ]);
        $batch->update($data);

        ActivityLogger::log('Batches', 'update', "Updated batch {$batch->batch_code} — status: {$batch->status}");

        return redirect()->route('batches.show', $batch)->with('success', 'Batch updated.');
    }

    public function destroy(ProductionBatch $batch)
    {
        $code = $batch->batch_code;
        $batch->delete();

        ActivityLogger::log('Batches', 'delete', "Deleted batch {$code}");

        return redirect()->route('batches.index')->with('success', 'Batch deleted.');
    }
}
