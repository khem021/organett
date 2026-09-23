<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('customer')->latest('order_date');

        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }
        if ($request->filled('payment')) {
            $query->where('payment_status', $request->payment);
        }
        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $query->where(function ($q) use ($term) {
                $q->whereLike('order_no', $term)
                    ->orWhereHas('customer', fn ($c) => $c->whereLike('customer_name', $term));
            });
        }

        $orders = $query->paginate(15)->withQueryString();
        // Cache plain arrays (not Eloquent models) to avoid deserialization issues
        $customers = cache()->remember($this->farmCacheKey('customers.dropdown'), 3600, fn () => Customer::orderBy('customer_name')
            ->get(['id', 'customer_name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->customer_name])
            ->values()
            ->toArray()
        );
        $stats = Order::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN order_status  = 'pending'    THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN order_status  = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN payment_status = 'paid'      THEN total_amount ELSE 0 END) as revenue
        ")->first();
        $summary = [
            'total' => (int) $stats->total,
            'pending' => (int) $stats->pending,
            'processing' => (int) $stats->processing,
            'revenue' => (float) $stats->revenue,
        ];

        return view('orders.index', compact('orders', 'customers', 'summary'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'delivery_date' => 'required|date|after_or_equal:order_date',
            'item_name' => 'required|string|max:150',
            'quantity_kg' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'payment_status' => 'required|in:unpaid,partial,paid',
            'order_status' => 'required|in:pending,processing,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        // exists: rule bypasses the tenant scope — confirm the customer is ours.
        abort_unless(Customer::whereKey($data['customer_id'])->exists(), 404);

        $data['total_amount'] = $data['quantity_kg'] * $data['unit_price'];

        $order = DB::transaction(function () use ($data) {
            $year = now()->year;
            $prefix = "ORD-{$year}-";

            // Highest sequence already used by THIS farm for THIS year
            // (soft-deleted rows included so numbers are never reused).
            $lastNo = Order::withTrashed()
                ->whereLike('order_no', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('order_no')
                ->value('order_no');

            $seq = $lastNo ? ((int) substr($lastNo, strlen($prefix)) + 1) : 1;

            $data['order_no'] = $prefix.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

            return Order::create($data);
        });

        $order->load('customer');

        ActivityLogger::log('Orders', 'create', "Created order {$order->order_no} for {$order->customer?->customer_name}");

        return redirect()->route('orders.index')->with('success', 'Order created.');
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'delivery', 'sales']);

        return view('orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'order_status' => 'required|in:pending,processing,completed,cancelled',
            'payment_status' => 'required|in:unpaid,partial,paid',
        ]);
        $order->update($data);

        ActivityLogger::log('Orders', 'update', "Updated {$order->order_no} — status: {$data['order_status']}, payment: {$data['payment_status']}");

        return redirect()->back()->with('success', 'Order status updated.');
    }

    public function cancel(Order $order)
    {
        if (in_array($order->order_status, ['completed', 'cancelled'])) {
            return redirect()->back()->with('error', 'Cannot cancel a completed or already cancelled order.');
        }

        $order->update(['order_status' => 'cancelled']);

        ActivityLogger::log('Orders', 'cancel', "Cancelled order {$order->order_no}");

        return redirect()->back()->with('success', "Order {$order->order_no} has been cancelled.");
    }

    public function updateDelivery(Request $request, Order $order)
    {
        $data = $request->validate([
            'destination' => 'required|string',
            'delivery_date' => 'required|date|after_or_equal:'.$order->order_date->toDateString(),
            'transport_status' => 'required|in:scheduled,in_transit,delivered,cancelled',
            'assigned_personnel' => 'nullable|string|max:150',
            'vehicle_info' => 'nullable|string|max:150',
            'remarks' => 'nullable|string',
        ]);

        Delivery::updateOrCreate(['order_id' => $order->id], $data);

        ActivityLogger::log('Orders', 'delivery', "Updated delivery info for order {$order->order_no}");

        return redirect()->back()->with('success', 'Delivery info updated.');
    }

    public function printReceipt(Order $order)
    {
        $order->load(['customer', 'delivery', 'sales']);
        $farmName = Setting::getValue('farm_name', config('app.name', 'Organett'));
        $farmAddress = Setting::getValue('farm_address', '');
        $farmContact = Setting::getValue('farm_contact', '');

        return view('orders.print', compact('order', 'farmName', 'farmAddress', 'farmContact'));
    }

    public function destroy(Order $order)
    {
        $orderNo = $order->order_no;

        DB::transaction(function () use ($order) {
            $order->delivery?->delete();
            $order->sales()->delete();
            $order->delete();
        });

        ActivityLogger::log('Orders', 'delete', "Deleted order {$orderNo}");

        return redirect()->route('orders.index')->with('success', 'Order deleted.');
    }
}
