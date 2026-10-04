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
        if (auth()->user()->role === 'super_admin') {
            return redirect()->route('admin.farms.index');
        }

        $now = now();

        // ── Stat cards ────────────────────────────────────────────────────────

        // Active + fruiting counts in one query
        $batchCounts = ProductionBatch::selectRaw('status, COUNT(*) as total')
            ->whereIn('status', ['planned', 'inoculated', 'fruiting'])
            ->groupBy('status')
            ->pluck('total', 'status');
        $activeBatches = $batchCounts->sum();
        $fruitingCount = (int) ($batchCounts['fruiting'] ?? 0);

        // Monthly yield — load raw records and group by Y-m in PHP (works SQLite + MySQL)
        $sixMonthsAgo = $now->copy()->subMonths(5)->startOfMonth();
        $harvestByMonth = HarvestRecord::where('harvest_date', '>=', $sixMonthsAgo)
            ->get(['harvest_date', 'quantity_kg'])
            ->groupBy(fn ($r) => $r->harvest_date->format('Y-m'))
            ->map(fn ($g) => (float) $g->sum('quantity_kg'));

        $monthYield = (float) ($harvestByMonth[$now->format('Y-m')] ?? 0);
        $lastM = $now->copy()->subMonth();
        $lastMonthYield = (float) ($harvestByMonth[$lastM->format('Y-m')] ?? 0);

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

        // Bar chart: monthly yield last 6 months (built from the grouped query above)
        $monthlyYield = collect(range(5, 0))->map(function ($i) use ($now, $harvestByMonth) {
            $m = $now->copy()->subMonths($i);

            return [
                'label' => $m->format('M'),
                'kg' => (float) ($harvestByMonth[$m->format('Y-m')] ?? 0),
            ];
        });

        // Doughnut chart: harvest by grade (one grouped query)
        $gradeBreakdown = HarvestRecord::selectRaw('quality_grade, SUM(quantity_kg) as kg')
            ->groupBy('quality_grade')
            ->pluck('kg', 'quality_grade');
        $gradeA = (float) ($gradeBreakdown['A'] ?? 0);
        $gradeB = (float) ($gradeBreakdown['B'] ?? 0);
        $gradeC = (float) ($gradeBreakdown['C'] ?? 0);
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

        // ── Needs attention today ─────────────────────────────────────────────

        // A delivery due today is not late yet.
        $overdueOrders = Order::whereIn('order_status', ['pending', 'processing'])
            ->whereDate('delivery_date', '<', $now->toDateString())
            ->count();

        // Money still owed on orders that have not been cancelled: what they are worth
        // less what has been paid against them.
        $open = Order::where('order_status', '!=', 'cancelled')->whereIn('payment_status', ['unpaid', 'partial']);
        $owed = (float) (clone $open)->sum('total_amount')
            - (float) Sale::whereIn('order_id', (clone $open)->select('id'))->sum('amount');

        $attention = [
            'overdueOrders' => $overdueOrders,
            'openOrders' => (clone $open)->count(),
            'outstanding' => number_format(max(0, round($owed, 2)), 2, '.', ''),
            'lowStock' => $lowStockCount,
            'harvestsDue' => $harvestAlerts->count(),
        ];
        $attention['any'] = $attention['overdueOrders'] + $attention['openOrders'] + $attention['lowStock'] + $attention['harvestsDue'] > 0;

        return view('dashboard', compact(
            'attention',
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
