<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        // No allFarms() needed — FarmScope is bypassed entirely for super admins.
        // withTrashed on the farm: an archived farm would otherwise resolve to null
        // and its entries would read as platform-level ones.
        $query = ActivityLog::with(['user', 'farm' => fn ($q) => $q->withTrashed()])->latest();

        // 'platform' surfaces the farm_id = null rows, which is where super admin
        // actions such as the security kill switches land.
        if ($request->filled('farm')) {
            $request->farm === 'platform'
                ? $query->whereNull('farm_id')
                : $query->where('farm_id', $request->farm);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $query->whereLike('description', '%'.$request->search.'%');
        }

        return view('admin.audit', [
            'logs' => $query->paginate(30)->withQueryString(),
            // Archived farms stay in the filter — their history is still readable.
            'farms' => Farm::withTrashed()->orderBy('name')->get(['id', 'name', 'deleted_at']),
            'modules' => ActivityLog::distinct()->orderBy('module')->pluck('module'),
            'actions' => ActivityLog::distinct()->orderBy('action')->pluck('action'),
            'actors' => User::whereIn('id', ActivityLog::distinct()->whereNotNull('user_id')->pluck('user_id'))
                ->orderBy('full_name')
                ->get(['id', 'full_name']),
        ]);
    }
}
