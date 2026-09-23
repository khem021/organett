<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\FarmFeature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FarmRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register-farm');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farm_name' => ['required', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $slug = Str::slug($data['farm_name']);
            $baseSlug = $slug;
            $i = 1;
            while (Farm::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$i}";
                $i++;
            }

            $farm = Farm::create([
                'name' => $data['farm_name'],
                'slug' => $slug,
                'status' => 'active',
            ]);

            // Seed all default features as enabled
            $defaultFeatures = ['reports', 'activity_logs', 'export'];
            foreach ($defaultFeatures as $feature) {
                FarmFeature::create([
                    'farm_id' => $farm->id,
                    'feature_key' => $feature,
                    'is_enabled' => true,
                ]);
            }

            $user = User::create([
                'farm_id' => $farm->id,
                'full_name' => $data['full_name'],
                'username' => Str::slug($data['full_name']).rand(100, 999),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'farm_admin',
                'status' => 'active',
            ]);

            return ['farm' => $farm, 'user' => $user];
        });

        Auth::login($result['user']);
        $farm = $result['farm'];

        return redirect('/dashboard')->with('status', "Welcome to Organett! Your farm '{$farm->name}' is ready.");
    }
}
