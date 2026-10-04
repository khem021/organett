<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\FarmFeature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class FarmRegistrationController extends Controller
{
    /** Farms that can be created from one IP per hour. Failed attempts don't count. */
    private const MAX_FARMS_PER_HOUR = 10;

    public function create()
    {
        return view('auth.register-farm');
    }

    public function store(Request $request)
    {
        $capKey = 'farms-created:'.$request->ip();

        if (RateLimiter::tooManyAttempts($capKey, self::MAX_FARMS_PER_HOUR)) {
            throw ValidationException::withMessages([
                'email' => 'Too many farms were registered from your network recently. Please try again in '
                    .(int) ceil(RateLimiter::availableIn($capKey) / 60).' minutes.',
            ]);
        }

        // Stored in lower case so "Ana@x.com" and "ana@x.com" cannot become two accounts.
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'farm_name' => ['required', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $result = DB::transaction(function () use ($data) {
            $slug = Str::slug($data['farm_name']);
            $baseSlug = $slug;
            $i = 1;
            // withTrashed: an archived farm keeps its slug, and farms.slug is unique.
            while (Farm::withTrashed()->where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$i}";
                $i++;
            }

            // Pending until a super admin approves it — see the admin farms screen.
            $farm = Farm::create([
                'name' => $data['farm_name'],
                'slug' => $slug,
                'status' => 'pending',
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
                'username' => $this->uniqueUsername($data['full_name']),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'farm_admin',
                'status' => 'active',
            ]);

            return ['farm' => $farm, 'user' => $user];
        });

        RateLimiter::hit($capKey, 3600);

        $farm = $result['farm'];

        // Deliberately not signed in: CheckActiveUser logs out any farm user whose
        // farm is not active, so logging them in here would boot them on the very
        // next request with a confusing message.
        return redirect('/login')->with('status', "Thanks! '{$farm->name}' has been submitted for review. "
            .'You can sign in once a platform administrator approves it.');
    }

    /**
     * A username that fits users.username (80 characters) and is not taken.
     * Str::slug() returns nothing for a name with no Latin letters, so fall back
     * to a plain word rather than a bare number.
     */
    private function uniqueUsername(string $fullName): string
    {
        $base = Str::limit(Str::slug($fullName), 70, '') ?: 'user';

        do {
            $username = $base.random_int(100, 9999);
        } while (User::where('username', $username)->exists());

        return $username;
    }
}
