<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmFeature;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    private const ALL_FEATURES = ['reports', 'activity_logs', 'export'];

    public function index()
    {
        $farms = Farm::withCount(['users', 'productionBatches', 'orders'])
            // The pending block names whoever registered the farm.
            ->with(['users' => fn ($q) => $q->where('role', 'farm_admin')->orderBy('id')])
            ->latest()
            ->get();

        $stats = [
            'total' => $farms->count(),
            'active' => $farms->where('status', 'active')->count(),
            'pending' => $farms->where('status', 'pending')->count(),
            'users' => User::whereNotNull('farm_id')->count(),
        ];

        return view('admin.farms.index', [
            'pendingFarms' => $farms->where('status', 'pending')->values(),
            'farms' => $farms->where('status', '!=', 'pending')->values(),
            'stats' => $stats,
        ]);
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

        ActivityLogger::logForFarm(
            $farm->id,
            'Farms',
            $request->status === 'active' ? 'enable' : 'suspend',
            "Set farm \"{$farm->name}\" to {$request->status}.",
        );

        return back()->with('status', "Farm status updated to {$request->status}.");
    }

    public function approve(Farm $farm)
    {
        // Guard against a double submit reviving a farm that was later suspended.
        abort_unless($farm->status === 'pending', 409);

        $farm->update(['status' => 'active']);

        ActivityLogger::logForFarm($farm->id, 'Farms', 'approve', "Approved farm registration for \"{$farm->name}\".");

        return back()->with('status', "'{$farm->name}' approved. Its administrator can now sign in.");
    }

    public function reject(Request $request, Farm $farm)
    {
        abort_unless($farm->status === 'pending', 409);

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        // Stored as its own status, not 'inactive', so the owner is told their
        // registration was turned down rather than that the farm was suspended.
        // The reason stays in the audit trail.
        $farm->update(['status' => 'rejected']);

        ActivityLogger::logForFarm(
            $farm->id,
            'Farms',
            'reject',
            "Rejected farm registration for \"{$farm->name}\"."
                .($request->filled('reason') ? " Reason: {$request->reason}" : ''),
        );

        return back()->with('status', "'{$farm->name}' rejected.");
    }

    public function archived()
    {
        $farms = Farm::onlyTrashed()
            ->withCount(['users', 'productionBatches', 'orders'])
            ->orderByDesc('deleted_at')
            ->get();

        return view('admin.farms.archived', compact('farms'));
    }

    public function archive(Farm $farm)
    {
        // Nothing is removed: the farm and all its rows stay in place, but the
        // farm stops resolving through relations, which is what locks its users
        // out (see CheckActiveUser) and hides it from every listing.
        $farm->delete();

        ActivityLogger::logForFarm(
            $farm->id,
            'Farms',
            'archive',
            "Archived farm \"{$farm->name}\" ({$farm->slug}). Its users can no longer sign in.",
        );

        return redirect()
            ->route('admin.farms.index')
            ->with('status', "'{$farm->name}' archived. You can restore it from Archived Farms.");
    }

    public function restore(Farm $farm)
    {
        abort_unless($farm->trashed(), 409);

        $farm->restore();

        ActivityLogger::logForFarm($farm->id, 'Farms', 'restore', "Restored farm \"{$farm->name}\" from the archive.");

        return redirect()
            ->route('admin.farms.index')
            ->with('status', "'{$farm->name}' restored with its previous status ({$farm->status}).");
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

        ActivityLogger::logForFarm($farm->id, 'Farms', 'update', "Updated feature flags for \"{$farm->name}\".");

        return back()->with('status', 'Feature settings saved.');
    }
}
