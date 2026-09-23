<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('search')) {
            $query->whereLike('description', '%'.$request->search.'%');
        }

        $logs = $query->paginate(30)->withQueryString();
        $modules = ActivityLog::distinct()->orderBy('module')->pluck('module');

        return view('activity-logs.index', compact('logs', 'modules'));
    }
}
