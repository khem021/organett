<?php

namespace App\Http\Controllers;

use App\Models\HarvestRecord;
use App\Models\ProductionBatch;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HarvestController extends Controller
{
    public function index(Request $request)
    {
        $query = HarvestRecord::with(['batch', 'creator'])->latest('harvest_date');

        if ($request->filled('grade')) {
            $query->where('quality_grade', $request->grade);
        }
        if ($request->filled('search')) {
            $query->whereHas('batch', fn ($q) => $q->where('batch_code', 'like', '%' . $request->search . '%'));
        }

        $records  = $query->paginate(15)->withQueryString();
        $batches  = ProductionBatch::whereIn('status', ['fruiting', 'harvested'])->orderBy('batch_code')->get();
        $totalKg  = HarvestRecord::sum('quantity_kg');
        $monthKg  = HarvestRecord::whereMonth('harvest_date', now()->month)->sum('quantity_kg');

        return view('harvest.index', compact('records', 'batches', 'totalKg', 'monthKg'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'batch_id'      => 'required|exists:production_batches,id',
            'harvest_date'  => 'required|date',
            'quantity_kg'   => 'required|numeric|min:0.01',
            'quality_grade' => 'required|in:A,B,C',
            'notes'         => 'nullable|string',
        ]);
        $data['created_by'] = Auth::id();
        $record = HarvestRecord::create($data);
        $record->load('batch');

        ActivityLogger::log(
            'Harvest',
            'create',
            "Logged {$record->quantity_kg}kg Grade-{$record->quality_grade} harvest for batch {$record->batch->batch_code}"
        );

        return redirect()->route('harvest.index')->with('success', 'Harvest record logged.');
    }

    public function destroy(HarvestRecord $harvest)
    {
        $harvest->load('batch');
        $desc = "Deleted harvest record #{$harvest->id} from batch {$harvest->batch?->batch_code}";
        $harvest->delete();

        ActivityLogger::log('Harvest', 'delete', $desc);

        return redirect()->route('harvest.index')->with('success', 'Record deleted.');
    }
}
