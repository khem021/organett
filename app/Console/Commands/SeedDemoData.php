<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Farm;
use App\Models\HarvestRecord;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\ProductionBatch;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fills one farm with realistic operating data so the dashboard, reports and
 * list screens can be demonstrated.
 *
 * Unlike OrganettSeeder this creates no user accounts, so it is safe to run
 * against a deployed environment — DatabaseSeeder is blocked outside
 * local/testing precisely because its demo logins ship known passwords.
 *
 * Dates are generated relative to today, so the "last 6 months" charts and the
 * "this month so far" figures are populated whenever it is run.
 */
class SeedDemoData extends Command
{
    protected $signature = 'organett:seed-demo
                            {--farm= : Farm id or slug to attach the data to}
                            {--force : Seed even if the farm already holds data}
                            {--clear : Delete this farm\'s existing data first}';

    protected $description = 'Populate one farm with demo production, inventory and sales data (creates no user accounts)';

    private Farm $farm;

    private ?int $createdBy = null;

    public function handle(): int
    {
        if (! $this->resolveFarm()) {
            return self::FAILURE;
        }

        $this->createdBy = User::withoutGlobalScopes()
            ->where('farm_id', $this->farm->id)
            ->orderByRaw("CASE WHEN role = 'farm_admin' THEN 0 ELSE 1 END")
            ->value('id');

        if ($this->option('clear') && ! $this->clearExisting()) {
            return self::FAILURE;
        }

        $existing = ProductionBatch::allFarms()->where('farm_id', $this->farm->id)->count()
            + Order::allFarms()->where('farm_id', $this->farm->id)->count();

        if ($existing > 0 && ! $this->option('force')) {
            $this->error("{$this->farm->name} already holds {$existing} batches/orders.");
            $this->line('Re-run with --force to add to it, or --clear to replace it.');

            return self::FAILURE;
        }

        DB::transaction(function () {
            $inventory = $this->seedInventory();
            $this->seedInventoryTransactions($inventory);
            $customers = $this->seedCustomers();
            $batches = $this->seedBatches();
            $this->seedHarvests($batches);
            $this->seedOrders($customers);
        });

        $this->newLine();
        $this->info("Demo data written to {$this->farm->name}.");
        $this->summarise();

        return self::SUCCESS;
    }

    private function resolveFarm(): bool
    {
        $key = $this->option('farm');

        if (! $key) {
            $this->error('Pass --farm with a farm id or slug. Available farms:');
            $this->newLine();
            $this->table(
                ['id', 'slug', 'name', 'status'],
                Farm::orderBy('id')->get(['id', 'slug', 'name', 'status'])->toArray()
            );

            return false;
        }

        $farm = Farm::where('slug', $key)
            ->when(is_numeric($key), fn ($q) => $q->orWhere('id', (int) $key))
            ->first();

        if (! $farm) {
            $this->error("No farm matches \"{$key}\".");

            return false;
        }

        $this->farm = $farm;

        return true;
    }

    private function clearExisting(): bool
    {
        if (! $this->option('force') && ! $this->confirm("Delete ALL existing data for {$this->farm->name}? This cannot be undone.")) {
            $this->line('Left unchanged.');

            return false;
        }

        $id = $this->farm->id;

        DB::transaction(function () use ($id) {
            DB::table('sales')->where('farm_id', $id)->delete();
            DB::table('deliveries')->where('farm_id', $id)->delete();
            DB::table('orders')->where('farm_id', $id)->delete();
            DB::table('customers')->where('farm_id', $id)->delete();
            DB::table('harvest_records')->where('farm_id', $id)->delete();
            DB::table('production_batches')->where('farm_id', $id)->delete();
            DB::table('inventory_transactions')->where('farm_id', $id)->delete();
            DB::table('inventory')->where('farm_id', $id)->delete();
            DB::table('alerts')->where('farm_id', $id)->delete();
        });

        $this->warn("Cleared existing data for {$this->farm->name}.");

        return true;
    }

    /** @return array<string, Inventory> */
    private function seedInventory(): array
    {
        // The last two sit at or below their reorder level so the dashboard's
        // stock-alert card and the low-stock filter both have something to show.
        $rows = [
            ['Fresh Mushrooms', 'Fresh Produce', 'kg', 128, 40, 'Cold Room A'],
            ['Dried Mushroom Packs', 'Packaged Goods', 'packs', 64, 20, 'Dry Storage'],
            ['Spawn Bottles', 'Production Supplies', 'bottles', 186, 50, 'Lab Shelf 2'],
            ['Substrate Bags', 'Production Supplies', 'bags', 95, 30, 'Prep Area'],
            ['Packaging Boxes', 'Packaging', 'pcs', 240, 100, 'Packing Station'],
            ['Cleaning Solution', 'Maintenance', 'liters', 8, 10, 'Utility Room'],
            ['Grow Bags', 'Production Supplies', 'bags', 25, 25, 'Prep Area'],
        ];

        $items = [];

        foreach ($rows as [$name, $category, $unit, $qty, $reorder, $location]) {
            $items[$name] = Inventory::create([
                'farm_id' => $this->farm->id,
                'item_name' => $name,
                'category' => $category,
                'unit' => $unit,
                'stock_qty' => $qty,
                'reorder_level' => $reorder,
                'location' => $location,
            ]);
        }

        return $items;
    }

    /** @param array<string, Inventory> $items */
    private function seedInventoryTransactions(array $items): void
    {
        $moves = [
            ['Fresh Mushrooms', 'in', 45, 12, 'Flush harvested into cold room'],
            ['Fresh Mushrooms', 'out', 25, 9, 'Wholesale dispatch'],
            ['Spawn Bottles', 'in', 60, 20, 'Lab batch prepared'],
            ['Spawn Bottles', 'out', 24, 6, 'Issued for inoculation'],
            ['Substrate Bags', 'out', 30, 4, 'Used on current cycle'],
            ['Packaging Boxes', 'in', 100, 15, 'Supplier delivery'],
            ['Cleaning Solution', 'out', 6, 3, 'Monthly sanitation'],
        ];

        foreach ($moves as [$item, $type, $qty, $daysAgo, $note]) {
            InventoryTransaction::create([
                'farm_id' => $this->farm->id,
                'inventory_id' => $items[$item]->id,
                'transaction_type' => $type,
                'quantity' => $qty,
                'transaction_date' => now()->copy()->subDays($daysAgo)->toDateString(),
                'notes' => $note,
                'created_by' => $this->createdBy,
            ]);
        }
    }

    /** @return array<int, Customer> */
    private function seedCustomers(): array
    {
        $rows = [
            ['Greenleaf Market', 'Ana Cruz', '+63 917 400 1001', 'procurement@greenleaf.test', 'San Pablo City, Laguna'],
            ['Fresh Basket Cafe', 'Luis Mendoza', '+63 917 400 1002', 'orders@freshbasket.test', 'Calamba City, Laguna'],
            ['Laguna Community Coop', 'Mira Santos', '+63 917 400 1003', 'coop@laguna.test', 'Santa Cruz, Laguna'],
            ['Harvest House Grocers', 'Joel Ramos', '+63 917 400 1004', 'supply@harvesthouse.test', 'Los Baños, Laguna'],
            ['Manila Wellness Hub', 'Paolo Fernandez', '+63 917 400 1005', 'orders@wellnesshub.test', 'Quezon City, Metro Manila'],
            ['Walk-in Buyer', 'N/A', '+63 917 400 1006', 'walkin@local.test', 'Farm Gate Pickup'],
        ];

        return array_map(fn ($r) => Customer::create([
            'farm_id' => $this->farm->id,
            'customer_name' => $r[0],
            'contact_person' => $r[1],
            'phone' => $r[2],
            'email' => $r[3],
            'address' => $r[4],
        ]), $rows);
    }

    /** @return array<int, ProductionBatch> */
    private function seedBatches(): array
    {
        $year = now()->year;
        $seq = $this->nextBatchSequence($year);

        // [months back inoculated, cycle length in days, status, substrate, spawn, note]
        $plan = [
            [6, 28, 'completed', 'Sawdust + Rice Bran', 'Pink Oyster Spawn', 'Strong first flush.'],
            [5, 28, 'completed', 'Corn Cobs + Rice Bran', 'White Oyster Spawn', 'Consistent yield across flushes.'],
            [4, 26, 'completed', 'Sawdust + Lime', 'Pleurotus Spawn', 'High-yield cycle.'],
            [3, 30, 'contaminated', 'Banana Leaves + Sawdust', 'White Oyster Spawn', 'Green mould found on week two.'],
            [3, 27, 'harvested', 'Sawdust + Rice Bran', 'King Oyster Spawn', 'Two harvest windows.'],
            [2, 26, 'harvested', 'Rice Straw + Lime', 'Oyster Spawn', 'Good pinning, even caps.'],
            [1, 28, 'fruiting', 'Sawdust + Rice Bran', 'Pink Oyster Spawn', 'Pinning well, harvest imminent.'],
            [1, 34, 'fruiting', 'Coffee Grounds + Sawdust', 'Pleurotus Spawn', 'Second flush forming.'],
            [0, 29, 'inoculated', 'Sawdust + Rice Bran', 'Button Mushroom Spawn', 'Spawn run stable.'],
            [0, 31, 'inoculated', 'Corn Cobs + Rice Bran', 'White Oyster Spawn', 'Colonising on schedule.'],
            [0, 35, 'planned', 'Sawdust + Lime', 'King Oyster Spawn', 'Prepared for next cycle.'],
        ];

        $batches = [];

        foreach ($plan as [$monthsBack, $cycle, $status, $substrate, $spawn, $note]) {
            $inoculated = now()->copy()->subMonths($monthsBack)->subDays(($seq * 3) % 11);

            // One fruiting batch is deliberately due within three days so the
            // dashboard's harvest-alert panel is not empty.
            $expected = $status === 'fruiting' && ! isset($batches['due'])
                ? now()->copy()->addDays(2)
                : $inoculated->copy()->addDays($cycle);

            $batch = ProductionBatch::create([
                'farm_id' => $this->farm->id,
                'batch_code' => sprintf('BATCH-%d-%03d', $year, $seq),
                'substrate_type' => $substrate,
                'spawn_type' => $spawn,
                'inoculation_date' => $inoculated->toDateString(),
                'expected_harvest_date' => $expected->toDateString(),
                'status' => $status,
                'notes' => $note,
                'created_by' => $this->createdBy,
            ]);

            if ($status === 'fruiting') {
                $batches['due'] = true;
            }

            $batches[] = $batch;
            $seq++;
        }

        unset($batches['due']);

        return array_values($batches);
    }

    private function nextBatchSequence(int $year): int
    {
        $last = ProductionBatch::allFarms()
            ->where('farm_id', $this->farm->id)
            ->where('batch_code', 'like', "BATCH-{$year}-%")
            ->orderByDesc('batch_code')
            ->value('batch_code');

        return $last ? ((int) substr($last, strlen("BATCH-{$year}-")) + 1) : 1;
    }

    /** @param array<int, ProductionBatch> $batches */
    private function seedHarvests(array $batches): void
    {
        $harvestable = array_values(array_filter(
            $batches,
            fn ($b) => in_array($b->status, ['completed', 'harvested', 'fruiting'], true)
        ));

        if ($harvestable === []) {
            return;
        }

        // Two records per month across the last six months, including the
        // current one, so the yield chart and "this month so far" both fill in.
        $weights = [[18.5, 'A'], [12.0, 'B'], [22.4, 'A'], [9.6, 'C'], [26.8, 'A'], [15.2, 'B']];
        $i = 0;

        foreach (collect(range(5, 0)) as $monthsBack) {
            $month = now()->copy()->subMonths($monthsBack);

            // Harvests are never logged in the future, but the current month
            // still needs records or the "this month so far" card reads 0 — so
            // its days are pulled back to today rather than skipped.
            $days = $month->isSameMonth(now())
                ? array_values(array_unique([max(1, intdiv(now()->day, 2)), now()->day]))
                : [8, 21];

            foreach ($days as $day) {
                $date = $month->copy()->startOfMonth()->addDays($day - 1);

                [$kg, $grade] = $weights[$i % count($weights)];
                $batch = $harvestable[$i % count($harvestable)];

                HarvestRecord::create([
                    'farm_id' => $this->farm->id,
                    'batch_id' => $batch->id,
                    'harvest_date' => $date->toDateString(),
                    'quantity_kg' => $kg,
                    'quality_grade' => $grade,
                    'notes' => "Flush from {$batch->batch_code}.",
                    'created_by' => $this->createdBy,
                ]);

                $i++;
            }
        }
    }

    /** @param array<int, Customer> $customers */
    private function seedOrders(array $customers): void
    {
        $year = now()->year;
        $seq = $this->nextOrderSequence($year);

        // [days back ordered, lead days, kg, price, order status, payment, transport]
        // Ordered oldest first so order numbers run with time. Sales are dated
        // on delivery, so the last two settled rows land inside the current
        // month and the reports' "this month" revenue is not zero.
        $plan = [
            [140, 1, 25.0, 190.0, 'completed', 'paid', 'delivered'],
            [120, 1, 18.0, 210.0, 'completed', 'paid', 'delivered'],
            [96, 2, 22.0, 205.0, 'completed', 'partial', 'delivered'],
            [74, 1, 30.0, 185.0, 'completed', 'paid', 'delivered'],
            [58, 2, 15.0, 225.0, 'completed', 'paid', 'delivered'],
            [41, 1, 20.0, 210.0, 'completed', 'partial', 'delivered'],
            [27, 2, 24.0, 200.0, 'completed', 'paid', 'delivered'],
            [12, 1, 16.0, 215.0, 'processing', 'partial', 'in_transit'],
            [5, 7, 32.0, 188.0, 'pending', 'unpaid', 'scheduled'],
            [3, 2, 21.0, 212.0, 'completed', 'paid', 'delivered'],
            // Lead time equals days back, so this one delivers today and the
            // dashboard's "dispatching today" counter has something to report.
            [2, 2, 28.0, 195.0, 'processing', 'unpaid', 'scheduled'],
            [1, 1, 17.5, 218.0, 'completed', 'paid', 'delivered'],
            [1, 6, 14.0, 220.0, 'pending', 'unpaid', 'scheduled'],
            [0, 7, 19.0, 205.0, 'pending', 'unpaid', null],
        ];

        foreach ($plan as $i => [$daysBack, $lead, $kg, $price, $status, $payment, $transport]) {
            $customer = $customers[$i % count($customers)];
            $orderDate = now()->copy()->subDays($daysBack);

            // The second 'processing' row dispatches today, which is what the
            // dashboard's "dispatching today" counter reads.
            $deliveryDate = $orderDate->copy()->addDays($lead);
            $total = round($kg * $price, 2);

            $order = Order::create([
                'farm_id' => $this->farm->id,
                'order_no' => sprintf('ORD-%d-%03d', $year, $seq),
                'customer_id' => $customer->id,
                'order_date' => $orderDate->toDateString(),
                'delivery_date' => $deliveryDate->toDateString(),
                'item_name' => 'Fresh Mushrooms',
                'quantity_kg' => $kg,
                'unit_price' => $price,
                'total_amount' => $total,
                'payment_status' => $payment,
                'order_status' => $status,
                'notes' => $customer->customer_name.' order.',
            ]);

            if ($payment !== 'unpaid') {
                Sale::create([
                    'farm_id' => $this->farm->id,
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'sale_date' => $deliveryDate->toDateString(),
                    'quantity_kg' => $kg,
                    'amount' => $payment === 'paid' ? $total : round($total / 2, 2),
                    'payment_method' => ['Cash', 'GCash', 'Bank Transfer', 'Maya'][$i % 4],
                    'remarks' => $payment === 'paid' ? 'Settled in full.' : 'Balance on delivery.',
                ]);
            }

            if ($transport) {
                Delivery::create([
                    'farm_id' => $this->farm->id,
                    'order_id' => $order->id,
                    'destination' => $customer->address,
                    'delivery_date' => $deliveryDate->toDateString(),
                    'transport_status' => $transport,
                    'assigned_personnel' => ['Marco Dela Cruz', 'Nina Ortega'][$i % 2],
                    'vehicle_info' => ['Van 1', 'Motorbike', 'Refrigerated Van'][$i % 3],
                    'remarks' => $transport === 'delivered' ? 'Received by store staff.' : 'Scheduled for dispatch.',
                ]);
            }

            $seq++;
        }
    }

    private function nextOrderSequence(int $year): int
    {
        $last = Order::allFarms()->withTrashed()
            ->where('farm_id', $this->farm->id)
            ->where('order_no', 'like', "ORD-{$year}-%")
            ->orderByDesc('order_no')
            ->value('order_no');

        return $last ? ((int) substr($last, strlen("ORD-{$year}-")) + 1) : 1;
    }

    private function summarise(): void
    {
        $id = $this->farm->id;

        $this->table(['table', 'rows'], [
            ['inventory', Inventory::allFarms()->where('farm_id', $id)->count()],
            ['customers', Customer::allFarms()->where('farm_id', $id)->count()],
            ['production_batches', ProductionBatch::allFarms()->where('farm_id', $id)->count()],
            ['harvest_records', HarvestRecord::allFarms()->where('farm_id', $id)->count()],
            ['orders', Order::allFarms()->where('farm_id', $id)->count()],
            ['sales', Sale::allFarms()->where('farm_id', $id)->count()],
            ['deliveries', Delivery::allFarms()->where('farm_id', $id)->count()],
        ]);

        $this->line('No user accounts were created.');
    }
}
