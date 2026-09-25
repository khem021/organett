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

        // Per-account limit stops distributed brute force; per-IP limit stops credential stuffing.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login:'.$email($request).'|'.$request->ip()),
            Limit::perMinute(20)->by('login-ip:'.$request->ip()),
        ]);

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(3)->by('reset-ip:'.$request->ip()),
            Limit::perHour(5)->by('reset-email:'.$email($request)),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(3)->by('register:'.$request->ip()));
    }
}
