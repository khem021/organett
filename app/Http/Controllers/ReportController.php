<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\HarvestRecord;
use App\Models\Order;
use App\Models\ProductionBatch;
use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $now = now();

        // Yield
        $totalYield     = HarvestRecord::sum('quantity_kg');
        $monthYield     = HarvestRecord::whereMonth('harvest_date', $now->month)
                              ->whereYear('harvest_date', $now->year)->sum('quantity_kg');
        $lastMonthYield = HarvestRecord::whereMonth('harvest_date', $now->copy()->subMonth()->month)
                              ->whereYear('harvest_date', $now->copy()->subMonth()->year)->sum('quantity_kg');

        // Revenue
        $totalRevenue     = Sale::sum('amount');
        $monthRevenue     = Sale::whereMonth('sale_date', $now->month)
                                ->whereYear('sale_date', $now->year)->sum('amount');
        $lastMonthRevenue = Sale::whereMonth('sale_date', $now->copy()->subMonth()->month)
                                ->whereYear('sale_date', $now->copy()->subMonth()->year)->sum('amount');

        // Batch stats
        $batchStats = ProductionBatch::select('status', DB::raw('count(*) as total'))
                          ->groupBy('status')->pluck('total', 'status');

        // Monthly yield — last 6 months
        $monthlyYield = collect(range(5, 0))->map(function ($i) use ($now) {
            $m = $now->copy()->subMonths($i);
            return [
                'label' => $m->format('M'),
                'kg'    => (float) HarvestRecord::whereMonth('harvest_date', $m->month)
                               ->whereYear('harvest_date', $m->year)->sum('quantity_kg'),
            ];
        });

        // Monthly revenue — last 6 months
        $monthlyRevenue = collect(range(5, 0))->map(function ($i) use ($now) {
            $m = $now->copy()->subMonths($i);
            return [
                'label'  => $m->format('M'),
                'amount' => (float) Sale::whereMonth('sale_date', $m->month)
                                ->whereYear('sale_date', $m->year)->sum('amount'),
            ];
        });

        // Top customers
        $topCustomers = Customer::withSum('sales as total_sales', 'amount')
                            ->orderByDesc('total_sales')
                            ->take(5)->get();

        // Harvest by grade
        $gradeBreakdown = HarvestRecord::select('quality_grade', DB::raw('SUM(quantity_kg) as kg'))
                              ->groupBy('quality_grade')->pluck('kg', 'quality_grade');

        $maxMonthlyYield   = $monthlyYield->max('kg') ?: 1;
        $maxMonthlyRevenue = $monthlyRevenue->max('amount') ?: 1;

        // Enabled export periods from settings
        $exportPeriods = json_decode(Setting::getValue('export_periods', '["daily","weekly","monthly","yearly"]'), true)
                         ?? ['daily', 'weekly', 'monthly', 'yearly'];

        return view('reports.index', compact(
            'totalYield', 'monthYield', 'lastMonthYield',
            'totalRevenue', 'monthRevenue', 'lastMonthRevenue',
            'batchStats', 'monthlyYield', 'monthlyRevenue',
            'topCustomers', 'gradeBreakdown',
            'maxMonthlyYield', 'maxMonthlyRevenue',
            'exportPeriods'
        ));
    }

    // ── Export ────────────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        $period = $request->input('period', 'monthly');

        if (! in_array($period, ['daily', 'weekly', 'monthly', 'yearly'])) {
            return back()->with('error', 'Invalid export period.');
        }

        // Check if this period is enabled (admin bypasses restriction)
        if (auth()->user()->role !== 'admin') {
            $allowed = json_decode(Setting::getValue('export_periods', '["daily","weekly","monthly","yearly"]'), true)
                       ?? ['daily', 'weekly', 'monthly', 'yearly'];
            if (! in_array($period, $allowed)) {
                return back()->with('error', 'This export format has been disabled by the administrator.');
            }
        }

        $farmName = Setting::getValue('farm_name', 'Organett Farm');
        $filename  = 'organett-income-' . $period . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $rows     = $this->buildRows($period);
        $columns  = $this->columns($period);
        $label    = $this->periodLabel($period);

        $callback = function () use ($rows, $columns, $farmName, $label, $period) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens accented chars correctly
            fputs($out, "\xEF\xBB\xBF");

            // ── Report header ────────────────────────────────
            fputcsv($out, [$farmName]);
            fputcsv($out, ["Income Report — {$label}"]);
            fputcsv($out, ['Generated: ' . now()->format('F d, Y  h:i A')]);
            fputcsv($out, ['']);

            // ── Column headers ───────────────────────────────
            fputcsv($out, $columns);

            // ── Data rows ────────────────────────────────────
            $totalRevenue = 0;
            $totalSales   = 0;

            foreach ($rows as $row) {
                $totalRevenue += $row['_revenue'];
                $totalSales   += $row['_count'];

                // Strip internal keys before writing to CSV
                $csvRow = array_filter($row, fn($k) => !str_starts_with((string)$k, '_'), ARRAY_FILTER_USE_KEY);
                fputcsv($out, array_values($csvRow));
            }

            // ── Totals ───────────────────────────────────────
            fputcsv($out, ['']);
            $avg = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
            if ($period === 'weekly') {
                fputcsv($out, ['', 'TOTAL', number_format($totalRevenue, 2), $totalSales, number_format($avg, 2)]);
            } else {
                fputcsv($out, ['TOTAL', number_format($totalRevenue, 2), $totalSales, number_format($avg, 2)]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function periodLabel(string $period): string
    {
        return match ($period) {
            'daily'   => 'Daily — Last 30 Days',
            'weekly'  => 'Weekly — Last 12 Weeks',
            'monthly' => 'Monthly — Last 12 Months',
            'yearly'  => 'Yearly — All Time',
        };
    }

    private function columns(string $period): array
    {
        $base = ['Revenue (PHP)', 'No. of Sales', 'Avg per Sale (PHP)'];
        return match ($period) {
            'daily'   => array_merge(['Date'],          $base),
            'weekly'  => array_merge(['Week', 'Period'], $base),
            'monthly' => array_merge(['Month'],          $base),
            'yearly'  => array_merge(['Year'],           $base),
        };
    }

    private function buildRows(string $period): array
    {
        return match ($period) {
            'daily'   => $this->dailyRows(),
            'weekly'  => $this->weeklyRows(),
            'monthly' => $this->monthlyRows(),
            'yearly'  => $this->yearlyRows(),
        };
    }

    private function dailyRows(): array
    {
        $rows = [];
        for ($i = 29; $i >= 0; $i--) {
            $date    = now()->subDays($i)->startOfDay();
            $revenue = (float) Sale::whereDate('sale_date', $date->toDateString())->sum('amount');
            $count   = (int)   Sale::whereDate('sale_date', $date->toDateString())->count();
            $avg     = $count > 0 ? $revenue / $count : 0;
            $rows[]  = [
                $date->format('F d, Y'),
                number_format($revenue, 2),
                $count,
                number_format($avg, 2),
                '_revenue' => $revenue,
                '_count'   => $count,
            ];
        }
        return $rows;
    }

    private function weeklyRows(): array
    {
        $rows = [];
        for ($i = 11; $i >= 0; $i--) {
            $start   = now()->startOfWeek()->subWeeks($i);
            $end     = $start->copy()->endOfWeek();
            $revenue = (float) Sale::whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])->sum('amount');
            $count   = (int)   Sale::whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])->count();
            $avg     = $count > 0 ? $revenue / $count : 0;
            $weekNo  = 'Week ' . (12 - $i);
            $rows[]  = [
                $weekNo,
                $start->format('M d') . ' – ' . $end->format('M d, Y'),
                number_format($revenue, 2),
                $count,
                number_format($avg, 2),
                '_revenue' => $revenue,
                '_count'   => $count,
            ];
        }
        return $rows;
    }

    private function monthlyRows(): array
    {
        $rows = [];
        for ($i = 11; $i >= 0; $i--) {
            $m       = now()->subMonths($i);
            $revenue = (float) Sale::whereMonth('sale_date', $m->month)->whereYear('sale_date', $m->year)->sum('amount');
            $count   = (int)   Sale::whereMonth('sale_date', $m->month)->whereYear('sale_date', $m->year)->count();
            $avg     = $count > 0 ? $revenue / $count : 0;
            $rows[]  = [
                $m->format('F Y'),
                number_format($revenue, 2),
                $count,
                number_format($avg, 2),
                '_revenue' => $revenue,
                '_count'   => $count,
            ];
        }
        return $rows;
    }

    private function yearlyRows(): array
    {
        $rows  = [];
        $years = Sale::selectRaw('YEAR(sale_date) as yr')
                     ->groupBy('yr')->orderBy('yr')->pluck('yr');

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        foreach ($years as $year) {
            $revenue = (float) Sale::whereYear('sale_date', $year)->sum('amount');
            $count   = (int)   Sale::whereYear('sale_date', $year)->count();
            $avg     = $count > 0 ? $revenue / $count : 0;
            $rows[]  = [
                $year,
                number_format($revenue, 2),
                $count,
                number_format($avg, 2),
                '_revenue' => $revenue,
                '_count'   => $count,
            ];
        }
        return $rows;
    }
}
