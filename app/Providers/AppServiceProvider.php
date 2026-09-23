<?php

namespace App\Providers;

use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('super-admin', fn ($user) => $user->role === 'super_admin');

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
}
