<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OrganettSeeder extends Seeder
{
    public function run(): void
    {
        // insertOrIgnore makes every table safe to seed multiple times
        // (re-deploys won't fail if rows already exist).

        DB::table('users')->insertOrIgnore([
            ['id' => 1, 'full_name' => 'Administrator', 'username' => 'admin', 'email' => 'admin@organett.local', 'password' => Hash::make('admin123'), 'role' => 'admin', 'status' => 'active', 'created_at' => '2026-01-01 08:00:00', 'updated_at' => '2026-01-01 08:00:00'],
            ['id' => 2, 'full_name' => 'Operations Staff', 'username' => 'staff', 'email' => 'staff@organett.local', 'password' => Hash::make('staff123'), 'role' => 'staff', 'status' => 'active', 'created_at' => '2026-01-02 08:30:00', 'updated_at' => '2026-01-02 08:30:00'],
            ['id' => 3, 'full_name' => 'Delivery Coordinator', 'username' => 'coordinator', 'email' => 'coordinator@organett.local', 'password' => Hash::make('staff123'), 'role' => 'staff', 'status' => 'inactive', 'created_at' => '2026-01-15 09:00:00', 'updated_at' => '2026-01-15 09:00:00'],
        ]);

        DB::table('settings')->insertOrIgnore([
            ['setting_key' => 'farm_name', 'setting_value' => 'ORGANETT Demo Farm', 'description' => 'Displayed system name'],
            ['setting_key' => 'farm_address', 'setting_value' => 'Sta. Cruz, Laguna, Philippines', 'description' => 'Business address'],
            ['setting_key' => 'farm_contact', 'setting_value' => '+63 917 555 1200', 'description' => 'Main contact number'],
            ['setting_key' => 'fresh_inventory_item_id', 'setting_value' => '1', 'description' => 'Fresh mushroom inventory item ID'],
        ]);

        DB::table('inventory')->insertOrIgnore([
            ['id' => 1, 'item_name' => 'Fresh Mushrooms', 'category' => 'Fresh Produce', 'unit' => 'kg', 'stock_qty' => 96.00, 'reorder_level' => 40.00, 'location' => 'Cold Room', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'item_name' => 'Dried Mushroom Packs', 'category' => 'Packaged Goods', 'unit' => 'packs', 'stock_qty' => 58.00, 'reorder_level' => 20.00, 'location' => 'Warehouse A', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'item_name' => 'Spawn Bottles', 'category' => 'Production Supplies', 'unit' => 'bottles', 'stock_qty' => 18.00, 'reorder_level' => 20.00, 'location' => 'Incubation Room', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'item_name' => 'Compost Bags', 'category' => 'Production Supplies', 'unit' => 'bags', 'stock_qty' => 12.00, 'reorder_level' => 15.00, 'location' => 'Storage Rack B', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'item_name' => 'Packaging Boxes', 'category' => 'Packaging', 'unit' => 'pcs', 'stock_qty' => 240.00, 'reorder_level' => 100.00, 'location' => 'Warehouse Shelf', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'item_name' => 'Cleaning Solution', 'category' => 'Maintenance', 'unit' => 'liters', 'stock_qty' => 35.00, 'reorder_level' => 10.00, 'location' => 'Utility Cabinet', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('production_batches')->insertOrIgnore([
            ['id' => 1, 'batch_code' => 'BATCH-2026-001', 'substrate_type' => 'Sawdust + Rice Bran', 'spawn_type' => 'Pink Oyster Spawn', 'inoculation_date' => '2026-01-05', 'expected_harvest_date' => '2026-02-02', 'status' => 'completed', 'notes' => 'Strong flush and healthy pinning.', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'batch_code' => 'BATCH-2026-002', 'substrate_type' => 'Corn Cobs + Rice Bran', 'spawn_type' => 'White Oyster Spawn', 'inoculation_date' => '2026-01-20', 'expected_harvest_date' => '2026-02-18', 'status' => 'harvested', 'notes' => 'Second flush produced mixed grades.', 'created_by' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'batch_code' => 'BATCH-2026-003', 'substrate_type' => 'Sawdust + Lime', 'spawn_type' => 'Pleurotus Spawn', 'inoculation_date' => '2026-02-10', 'expected_harvest_date' => '2026-03-05', 'status' => 'completed', 'notes' => 'High-yield batch.', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'batch_code' => 'BATCH-2026-004', 'substrate_type' => 'Banana Leaves + Sawdust', 'spawn_type' => 'White Oyster Spawn', 'inoculation_date' => '2026-02-28', 'expected_harvest_date' => '2026-03-25', 'status' => 'contaminated', 'notes' => 'Contamination detected on week two.', 'created_by' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'batch_code' => 'BATCH-2026-005', 'substrate_type' => 'Sawdust + Rice Bran', 'spawn_type' => 'King Oyster Spawn', 'inoculation_date' => '2026-03-08', 'expected_harvest_date' => '2026-04-02', 'status' => 'harvested', 'notes' => 'Two successful harvest windows.', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'batch_code' => 'BATCH-2026-006', 'substrate_type' => 'Rice Straw + Lime', 'spawn_type' => 'Oyster Spawn', 'inoculation_date' => '2026-03-20', 'expected_harvest_date' => '2026-04-15', 'status' => 'fruiting', 'notes' => 'Monitored for flush consistency.', 'created_by' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'batch_code' => 'BATCH-2026-007', 'substrate_type' => 'Sawdust + Rice Bran', 'spawn_type' => 'Button Mushroom Spawn', 'inoculation_date' => '2026-04-01', 'expected_harvest_date' => '2026-04-28', 'status' => 'inoculated', 'notes' => 'Spawn run stable after three days.', 'created_by' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'batch_code' => 'BATCH-2026-008', 'substrate_type' => 'Coffee Grounds + Sawdust', 'spawn_type' => 'Pink Oyster Spawn', 'inoculation_date' => '2026-04-10', 'expected_harvest_date' => '2026-05-08', 'status' => 'planned', 'notes' => 'Prepared for next cycle.', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('harvest_records')->insertOrIgnore([
            ['id' => 1, 'batch_id' => 1, 'harvest_date' => '2026-02-01', 'quantity_kg' => 48.50, 'quality_grade' => 'A', 'notes' => 'First flush collected.', 'created_by' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'batch_id' => 1, 'harvest_date' => '2026-02-04', 'quantity_kg' => 41.20, 'quality_grade' => 'A', 'notes' => 'Second flush premium quality.', 'created_by' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'batch_id' => 2, 'harvest_date' => '2026-02-20', 'quantity_kg' => 38.00, 'quality_grade' => 'B', 'notes' => 'Slight variation in cap size.', 'created_by' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'batch_id' => 2, 'harvest_date' => '2026-02-24', 'quantity_kg' => 22.50, 'quality_grade' => 'C', 'notes' => 'Late flush for dried processing.', 'created_by' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'batch_id' => 3, 'harvest_date' => '2026-03-07', 'quantity_kg' => 55.40, 'quality_grade' => 'A', 'notes' => 'Excellent density.', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'batch_id' => 3, 'harvest_date' => '2026-03-10', 'quantity_kg' => 46.10, 'quality_grade' => 'A', 'notes' => 'Second flush consistent.', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'batch_id' => 5, 'harvest_date' => '2026-04-03', 'quantity_kg' => 42.00, 'quality_grade' => 'A', 'notes' => 'Packed for wholesale.', 'created_by' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'batch_id' => 5, 'harvest_date' => '2026-04-06', 'quantity_kg' => 36.80, 'quality_grade' => 'B', 'notes' => 'Final harvest window.', 'created_by' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('customers')->insertOrIgnore([
            ['id' => 1, 'customer_name' => 'Greenleaf Market', 'contact_person' => 'Ana Cruz', 'phone' => '+63 917 400 1001', 'email' => 'procurement@greenleaf.local', 'address' => 'San Pablo City, Laguna', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'customer_name' => 'Fresh Basket Cafe', 'contact_person' => 'Luis Mendoza', 'phone' => '+63 917 400 1002', 'email' => 'orders@freshbasket.local', 'address' => 'Calamba City, Laguna', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'customer_name' => 'Laguna Community Coop', 'contact_person' => 'Mira Santos', 'phone' => '+63 917 400 1003', 'email' => 'coop@laguna.local', 'address' => 'Santa Cruz, Laguna', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'customer_name' => 'Harvest House Grocers', 'contact_person' => 'Joel Ramos', 'phone' => '+63 917 400 1004', 'email' => 'supply@harvesthouse.local', 'address' => 'Los Banos, Laguna', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'customer_name' => 'Manila Wellness Hub', 'contact_person' => 'Paolo Fernandez', 'phone' => '+63 917 400 1005', 'email' => 'orders@wellnesshub.local', 'address' => 'Quezon City, Metro Manila', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'customer_name' => 'Walk-in Buyer', 'contact_person' => 'N/A', 'phone' => '+63 917 400 1006', 'email' => 'walkin@local.test', 'address' => 'Farm Gate Pickup', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('orders')->insertOrIgnore([
            ['id' => 1, 'order_no' => 'ORD-2026-001', 'customer_id' => 1, 'order_date' => '2026-02-05', 'delivery_date' => '2026-02-06', 'item_name' => 'Fresh Mushrooms', 'quantity_kg' => 25.00, 'unit_price' => 190.00, 'total_amount' => 4750.00, 'payment_status' => 'paid', 'order_status' => 'completed', 'notes' => 'Morning wholesale delivery.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'order_no' => 'ORD-2026-002', 'customer_id' => 2, 'order_date' => '2026-03-18', 'delivery_date' => '2026-03-19', 'item_name' => 'Fresh Mushrooms', 'quantity_kg' => 18.00, 'unit_price' => 210.00, 'total_amount' => 3780.00, 'payment_status' => 'partial', 'order_status' => 'completed', 'notes' => 'Cafe order with partial payment.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'order_no' => 'ORD-2026-003', 'customer_id' => 4, 'order_date' => '2026-04-05', 'delivery_date' => '2026-04-06', 'item_name' => 'Fresh Mushrooms', 'quantity_kg' => 22.00, 'unit_price' => 205.00, 'total_amount' => 4510.00, 'payment_status' => 'paid', 'order_status' => 'completed', 'notes' => 'Delivered to grocery outlet.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'order_no' => 'ORD-2026-004', 'customer_id' => 3, 'order_date' => '2026-04-12', 'delivery_date' => '2026-04-20', 'item_name' => 'Fresh Mushrooms', 'quantity_kg' => 30.00, 'unit_price' => 185.00, 'total_amount' => 5550.00, 'payment_status' => 'unpaid', 'order_status' => 'pending', 'notes' => 'Coop bulk order.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'order_no' => 'ORD-2026-005', 'customer_id' => 5, 'order_date' => '2026-04-15', 'delivery_date' => '2026-04-19', 'item_name' => 'Fresh Mushrooms', 'quantity_kg' => 15.00, 'unit_price' => 225.00, 'total_amount' => 3375.00, 'payment_status' => 'partial', 'order_status' => 'processing', 'notes' => 'Metro Manila delivery.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'order_no' => 'ORD-2026-006', 'customer_id' => 1, 'order_date' => '2026-04-17', 'delivery_date' => '2026-04-22', 'item_name' => 'Fresh Mushrooms', 'quantity_kg' => 20.00, 'unit_price' => 210.00, 'total_amount' => 4200.00, 'payment_status' => 'unpaid', 'order_status' => 'pending', 'notes' => 'Weekend replenishment.', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('deliveries')->insertOrIgnore([
            ['id' => 1, 'order_id' => 1, 'destination' => 'San Pablo City, Laguna', 'delivery_date' => '2026-02-06', 'transport_status' => 'delivered', 'assigned_personnel' => 'Marco Dela Cruz', 'vehicle_info' => 'Van 1', 'remarks' => 'Received by market staff.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'order_id' => 2, 'destination' => 'Calamba City, Laguna', 'delivery_date' => '2026-03-19', 'transport_status' => 'delivered', 'assigned_personnel' => 'Nina Ortega', 'vehicle_info' => 'Motorbike', 'remarks' => 'Freshness confirmed.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'order_id' => 3, 'destination' => 'Los Banos, Laguna', 'delivery_date' => '2026-04-06', 'transport_status' => 'delivered', 'assigned_personnel' => 'Marco Dela Cruz', 'vehicle_info' => 'Van 1', 'remarks' => 'Before opening hours.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'order_id' => 4, 'destination' => 'Santa Cruz, Laguna', 'delivery_date' => '2026-04-20', 'transport_status' => 'scheduled', 'assigned_personnel' => 'Nina Ortega', 'vehicle_info' => 'Motorbike', 'remarks' => 'Next-day dispatch.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'order_id' => 5, 'destination' => 'Quezon City, Metro Manila', 'delivery_date' => '2026-04-19', 'transport_status' => 'in_transit', 'assigned_personnel' => 'Marco Dela Cruz', 'vehicle_info' => 'Refrigerated Van', 'remarks' => 'Left farm at 08:30 AM.', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('sales')->insertOrIgnore([
            ['id' => 1, 'order_id' => 1, 'customer_id' => 1, 'sale_date' => '2026-02-05', 'quantity_kg' => 25.00, 'amount' => 4750.00, 'payment_method' => 'Cash', 'remarks' => 'Paid in full.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'order_id' => 2, 'customer_id' => 2, 'sale_date' => '2026-03-18', 'quantity_kg' => 10.00, 'amount' => 2000.00, 'payment_method' => 'Bank Transfer', 'remarks' => 'Partial payment.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'order_id' => 3, 'customer_id' => 4, 'sale_date' => '2026-04-06', 'quantity_kg' => 22.00, 'amount' => 4510.00, 'payment_method' => 'GCash', 'remarks' => 'Paid upon delivery.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'order_id' => 5, 'customer_id' => 5, 'sale_date' => '2026-04-15', 'quantity_kg' => 8.00, 'amount' => 1800.00, 'payment_method' => 'Digital Wallet', 'remarks' => 'Initial payment.', 'created_at' => now(), 'updated_at' => now()],
            // Note: walk-in sales (id 5 & 6) removed — order_id is NOT NULL in this schema.
            // Walk-in sales must be linked to an order (e.g. order 4 or 6).
            ['id' => 5, 'order_id' => 4, 'customer_id' => 6, 'sale_date' => '2026-04-16', 'quantity_kg' => 4.00, 'amount' => 760.00, 'payment_method' => 'Cash', 'remarks' => 'Walk-in gate sale — linked to coop order.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'order_id' => 6, 'customer_id' => 1, 'sale_date' => '2026-04-17', 'quantity_kg' => 3.00, 'amount' => 630.00, 'payment_method' => 'Cash', 'remarks' => 'Supplemental sale linked to weekend order.', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('alerts')->insertOrIgnore([
            ['alert_type' => 'Low Stock', 'message' => 'Spawn Bottles is below reorder level.', 'severity' => 'warning', 'source_module' => 'Inventory', 'reference_id' => 3, 'is_resolved' => false, 'created_at' => now(), 'updated_at' => now()],
            ['alert_type' => 'Low Stock', 'message' => 'Compost Bags is below reorder level.', 'severity' => 'warning', 'source_module' => 'Inventory', 'reference_id' => 4, 'is_resolved' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('activity_logs')->insertOrIgnore([
            ['user_id' => 1, 'module' => 'Authentication', 'action' => 'Login', 'description' => 'admin logged in.', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 2, 'module' => 'Production', 'action' => 'Create', 'description' => 'Created batch BATCH-2026-008.', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 1, 'module' => 'Orders', 'action' => 'Create', 'description' => 'Created order ORD-2026-006.', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 1, 'module' => 'Sales', 'action' => 'Create', 'description' => 'Added a sale worth PHP 630.00.', 'created_at' => now(), 'updated_at' => now()],
        ]);

    }
}
