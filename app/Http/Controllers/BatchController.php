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
            $query->whereLike('batch_code', '%'.$request->search.'%');
        }

        $batches = $query->paginate(15)->withQueryString();

        return view('batches.index', compact('batches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'batch_code' => 'required|string|max:100|unique:production_batches',
            'substrate_type' => 'required|string|max:150',
            'spawn_type' => 'required|string|max:150',
            'inoculation_date' => 'required|date',
            'expected_harvest_date' => 'required|date|after:inoculation_date',
            'status' => 'required|in:planned,inoculated,fruiting,harvested,completed,contaminated',
            'notes' => 'nullable|string',
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

    private const VALID_TRANSITIONS = [
        'planned' => ['planned', 'inoculated', 'contaminated'],
        'inoculated' => ['inoculated', 'fruiting', 'contaminated'],
        'fruiting' => ['fruiting', 'harvested', 'contaminated'],
        'harvested' => ['harvested', 'completed'],
        'completed' => ['completed'],
        'contaminated' => ['contaminated'],
    ];

    public function update(Request $request, ProductionBatch $batch)
    {
        $data = $request->validate([
            'substrate_type' => 'required|string|max:150',
            'spawn_type' => 'required|string|max:150',
            'inoculation_date' => 'required|date',
            'expected_harvest_date' => 'required|date|after:inoculation_date',
            'status' => 'required|in:planned,inoculated,fruiting,harvested,completed,contaminated',
            'notes' => 'nullable|string',
        ]);

        $allowed = self::VALID_TRANSITIONS[$batch->status] ?? [];
        if (! in_array($data['status'], $allowed)) {
            return back()->withErrors(['status' => "Cannot move batch from \"{$batch->status}\" to \"{$data['status']}\". ".
                'Valid next states: '.implode(', ', $allowed).'.',
            ]);
        }

        $batch->update($data);

        ActivityLogger::log('Batches', 'update', "Updated batch {$batch->batch_code} — status: {$batch->status}");

        return redirect()->route('batches.show', $batch)->with('success', 'Batch updated.');
    }

    public function destroy(ProductionBatch $batch)
    {
        if ($batch->harvestRecords()->count() > 0) {
            return redirect()->route('batches.index')
                ->with('error', "Cannot delete batch \"{$batch->batch_code}\" — it has harvest records attached. Delete those first.");
        }

        $code = $batch->batch_code;
        $batch->delete();

        ActivityLogger::log('Batches', 'delete', "Deleted batch {$code}");

        return redirect()->route('batches.index')->with('success', 'Batch deleted.');
    }
}
