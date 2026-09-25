<?php

namespace App\Providers;

use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('super-admin', fn ($user) => $user->role === 'super_admin');

        Password::defaults(function () {
            $rule = Password::min(10)->mixedCase()->numbers()->symbols();

            // Breach check calls the HIBP API; keep tests offline.
            return app()->runningUnitTests() ? $rule : $rule->uncompromised();
        });

        $this->configureRateLimiting();

        // Share live badge counts with the app layout on every request.
        // Only runs queries when a user is authenticated (skips login page).
        // Cached briefly per-farm so every page load isn't two extra COUNT scans.
        View::composer('layouts.app', function ($view) {
            if (! Auth::check()) {
                return;
            }

            $farmKey = Auth::user()->farm_id ?? 'none';

            $badges = cache()->remember("nav.badges.{$farmKey}", 30, fn () => [
                'navPendingOrders' => Order::where('order_status', 'pending')->count(),
                'navLowStock' => Inventory::whereColumn('stock_qty', '<=', 'reorder_level')->count(),
            ]);

            $view->with($badges);
        });
    }

    private function configureRateLimiting(): void
    {
        $email = fn (Request $request) => Str::lower(trim((string) $request->input('email')));

        // Send the user back to the form with a readable message instead of a bare 429 page.
        $tooMany = fn (string $what) => function (Request $request, array $headers) use ($what) {
            $wait = (int) ($headers['Retry-After'] ?? 60);
            $when = $wait > 90 ? ceil($wait / 60).' minutes' : $wait.' seconds';

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => "Too many {$what}. Please try again in {$when}."]);
        };

        // Per account+IP stops guessing one password; per IP stops credential stuffing across accounts.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login:'.$email($request).'|'.$request->ip())->response($tooMany('login attempts')),
            Limit::perMinute(20)->by('login-ip:'.$request->ip())->response($tooMany('login attempts')),
        ]);

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(3)->by('reset-ip:'.$request->ip())->response($tooMany('reset requests')),
            Limit::perHour(5)->by('reset-email:'.$email($request))->response($tooMany('reset requests')),
        ]);

        // Only guards against hammering the form; farms actually created are capped in FarmRegistrationController.
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(20)->by('register:'.$request->ip())->response($tooMany('sign-up attempts')));
    }
}
