<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmFeature;
use App\Models\User;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    private const ALL_FEATURES = ['reports', 'activity_logs', 'export'];

    public function index()
    {
        $farms = Farm::withCount(['users', 'productionBatches', 'orders'])
            ->latest()
            ->get();

        $stats = [
            'total' => $farms->count(),
            'active' => $farms->where('status', 'active')->count(),
            'users' => User::whereNotNull('farm_id')->count(),
        ];

        return view('admin.farms.index', compact('farms', 'stats'));
    }

    public function show(Farm $farm)
    {
        $farm->load(['users', 'features']);
        $farm->loadCount(['productionBatches', 'orders', 'customers']);

        return view('admin.farms.show', compact('farm'));
    }

    public function updateStatus(Request $request, Farm $farm)
    {
        $request->validate(['status' => ['required', 'in:active,inactive,pending']]);
        $farm->update(['status' => $request->status]);

        return back()->with('status', "Farm status updated to {$request->status}.");
    }

    public function features(Farm $farm)
    {
        $features = collect(self::ALL_FEATURES)->mapWithKeys(function ($key) use ($farm) {
            $record = $farm->features()->where('feature_key', $key)->first();

            return [$key => $record ? $record->is_enabled : true];
        });

        return view('admin.farms.features', compact('farm', 'features'));
    }

    public function updateFeatures(Request $request, Farm $farm)
    {
        foreach (self::ALL_FEATURES as $key) {
            FarmFeature::updateOrCreate(
                ['farm_id' => $farm->id, 'feature_key' => $key],
                ['is_enabled' => $request->boolean($key)]
            );
        }

        return back()->with('status', 'Feature settings saved.');
    }
}
