<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SecuritySetting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SecurityController extends Controller
{
    public function index()
    {
        $toggles = SecuritySetting::TOGGLES;
        $states = SecuritySetting::states();

        $history = SecuritySetting::with('updatedBy')
            ->orderByDesc('updated_at')
            ->get()
            ->keyBy('setting_key');

        return view('admin.security', compact('toggles', 'states', 'history'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', Rule::in([...array_keys(SecuritySetting::TOGGLES), 'all'])],
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = $request->boolean('enabled');
        $keys = $validated['key'] === 'all'
            ? array_keys(SecuritySetting::TOGGLES)
            : [$validated['key']];

        foreach ($keys as $key) {
            SecuritySetting::setEnabled($key, $enabled, Auth::id());

            ActivityLogger::log(
                'Security',
                $enabled ? 'enable' : 'disable',
                sprintf(
                    '%s platform protection "%s"',
                    $enabled ? 'Turned ON' : 'Turned OFF',
                    SecuritySetting::TOGGLES[$key]['label'],
                ),
            );
        }

        $what = $validated['key'] === 'all'
            ? 'All protections'
            : SecuritySetting::TOGGLES[$keys[0]]['label'];

        return back()->with('status', $enabled
            ? "{$what} back on."
            : "{$what} now OFF for every farm on this platform.");
    }
}
