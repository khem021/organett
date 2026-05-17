<?php

namespace App\Http\Controllers;

use App\Models\HarvestRecord;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductionBatch;
use App\Models\Sale;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();

        // ── Stat cards ────────────────────────────────────────────────────────

        // Active batches = planned + inoculated + fruiting
        $activeBatches = ProductionBatch::whereIn('status', ['planned', 'inoculated', 'fruiting'])->count();

        // Fruiting batches (ready to harvest)
        $fruitingCount = ProductionBatch::where('status', 'fruiting')->count();

        // Monthly yield (this month)
        $monthYield = (float) HarvestRecord::whereMonth('harvest_date', $now->month)
                                           ->whereYear('harvest_date', $now->year)
                                           ->sum('quantity_kg');

        // Last month yield (for % change)
        $lastMonthYield = (float) HarvestRecord::whereMonth('harvest_date', $now->copy()->subMonth()->month)
                                               ->whereYear('harvest_date', $now->copy()->subMonth()->year)
                                               ->sum('quantity_kg');

        $yieldChange = $lastMonthYield > 0
            ? round((($monthYield - $lastMonthYield) / $lastMonthYield) * 100)
            : null;

        // Pending orders
        $pendingOrders = Order::where('order_status', 'pending')->count();

        // Orders dispatching today (processing + delivery_date = today)
        $dispatchingToday = Order::where('order_status', 'processing')
                                 ->whereDate('delivery_date', $now->toDateString())
                                 ->count();

        // Low-stock items
        $lowStockItems = Inventory::whereColumn('stock_qty', '<=', 'reorder_level')->get();
        $lowStockCount = $lowStockItems->count();

        // ── Charts ────────────────────────────────────────────────────────────

        // Bar chart: monthly yield last 6 months
        $monthlyYield = collect(range(5, 0))->map(function ($i) use ($now) {
            $m = $now->copy()->subMonths($i);
            return [
                'label' => $m->format('M'),
                'kg'    => (float) HarvestRecord::whereMonth('harvest_date', $m->month)
                                                ->whereYear('harvest_date', $m->year)
                                                ->sum('quantity_kg'),
            ];
        });

        // Doughnut chart: harvest by grade (all time)
        $gradeA = (float) HarvestRecord::where('quality_grade', 'A')->sum('quantity_kg');
        $gradeB = (float) HarvestRecord::where('quality_grade', 'B')->sum('quantity_kg');
        $gradeC = (float) HarvestRecord::where('quality_grade', 'C')->sum('quantity_kg');
        $totalHarvest = $gradeA + $gradeB + $gradeC;

        // ── Active batches table ──────────────────────────────────────────────

        $activeBatchList = ProductionBatch::whereIn('status', ['fruiting', 'inoculated', 'planned'])
                                          ->orderByRaw("CASE status
                                              WHEN 'fruiting'   THEN 1
                                              WHEN 'inoculated' THEN 2
                                              WHEN 'planned'    THEN 3
                                              END")
                                          ->orderBy('expected_harvest_date')
                                          ->take(6)
                                          ->get();

        // ── Alerts ───────────────────────────────────────────────────────────

        // Fruiting batches past or near their expected harvest date
        $harvestAlerts = ProductionBatch::where('status', 'fruiting')
                                        ->where('expected_harvest_date', '<=', $now->copy()->addDays(3))
                                        ->orderBy('expected_harvest_date')
                                        ->take(3)
                                        ->get();

        // ── Inventory levels ─────────────────────────────────────────────────

        $inventoryItems = Inventory::orderByRaw('stock_qty <= reorder_level DESC')
                                   ->orderBy('item_name')
                                   ->get();

        // Max qty for progress bar scaling
        $maxStock = $inventoryItems->max('stock_qty') ?: 1;

        return view('dashboard', compact(
            'activeBatches', 'fruitingCount',
            'monthYield', 'lastMonthYield', 'yieldChange',
            'pendingOrders', 'dispatchingToday',
            'lowStockCount', 'lowStockItems',
            'monthlyYield',
            'gradeA', 'gradeB', 'gradeC', 'totalHarvest',
            'activeBatchList',
            'harvestAlerts',
            'inventoryItems', 'maxStock'
        ));
    }
}
