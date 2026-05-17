<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('setting_value', 'setting_key');

        // Decode export periods into an array for the view
        $exportPeriods = json_decode(
            $settings->get('export_periods', '["daily","weekly","monthly","yearly"]'),
            true
        ) ?? ['daily', 'weekly', 'monthly', 'yearly'];

        return view('settings.index', compact('settings', 'exportPeriods'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'farm_name'    => 'required|string|max:150',
            'farm_address' => 'nullable|string|max:255',
            'farm_contact' => 'nullable|string|max:50',
        ]);

        // Farm info
        foreach (['farm_name', 'farm_address', 'farm_contact'] as $key) {
            Setting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $request->input($key, '')]
            );
        }

        // Export periods — store as JSON array of checked values
        $allowed  = ['daily', 'weekly', 'monthly', 'yearly'];
        $selected = array_values(array_intersect($request->input('export_periods', []), $allowed));

        // Always keep at least one period enabled
        if (empty($selected)) {
            $selected = ['monthly'];
        }

        Setting::updateOrCreate(
            ['setting_key' => 'export_periods'],
            ['setting_value' => json_encode($selected), 'description' => 'Enabled income export periods']
        );

        return back()->with('success', 'Settings saved successfully.');
    }
}
