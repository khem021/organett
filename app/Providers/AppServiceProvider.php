<?php

namespace App\Providers;

use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Share live badge counts with the app layout on every request.
        // Only runs queries when a user is authenticated (skips login page).
        View::composer('layouts.app', function ($view) {
            if (! Auth::check()) {
                return;
            }

            $view->with([
                'navPendingOrders' => Order::where('order_status', 'pending')->count(),
                'navLowStock'      => Inventory::whereColumn('stock_qty', '<=', 'reorder_level')->count(),
            ]);
        });
    }
}
