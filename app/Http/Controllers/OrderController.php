<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Support\Money;
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
        // "open" is what the dashboard links to: orders that still owe money.
        if ($request->payment === 'open') {
            $query->where('order_status', '!=', 'cancelled')->whereIn('payment_status', ['unpaid', 'partial']);
        } elseif ($request->filled('payment')) {
            $query->where('payment_status', $request->payment);
        }
        if ($request->boolean('overdue')) {
            $query->whereIn('order_status', ['pending', 'processing'])
                ->whereDate('delivery_date', '<', now()->toDateString());
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
            'quantity_kg' => Money::rules(),
            'unit_price' => Money::rules('0'),
            'order_status' => 'required|in:pending,processing,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        // exists: rule bypasses the tenant scope — confirm the customer is ours.
        abort_unless(Customer::whereKey($data['customer_id'])->exists(), 404);

        // Exact decimal arithmetic, and a total the column can actually store.
        $data['total_amount'] = Money::total($data['quantity_kg'], $data['unit_price']);
        Money::assertFits($data['total_amount'], 'unit_price', 'That order total');

        // Payment status follows the payments recorded against the order (see SaleController),
        // so every order starts unpaid whatever else is submitted with it.
        $data['payment_status'] = 'unpaid';

        $order = DB::transaction(function () use ($data) {
            $year = now()->year;
            $prefix = "ORD-{$year}-";

            // Highest sequence already used by THIS farm for THIS year
            // (soft-deleted rows included so numbers are never reused).
            // Longest first: as text "999" sorts above "1000", which would hand out 1000 twice.
            $lastNo = Order::withTrashed()
                ->whereLike('order_no', $prefix.'%')
                ->lockForUpdate()
                ->orderByRaw('LENGTH(order_no) DESC')
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
        // Payment status is not editable: it is worked out from the payments on record.
        $data = $request->validate([
            'order_status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        if ($data['order_status'] === 'cancelled' && $order->order_status !== 'cancelled' && $blocked = $this->paymentsBlock($order, 'cancelled')) {
            return redirect()->back()->with('error', $blocked);
        }

        $order->update($data);

        ActivityLogger::log('Orders', 'update', "Updated {$order->order_no} — status: {$data['order_status']}");

        return redirect()->back()->with('success', 'Order status updated.');
    }

    public function cancel(Order $order)
    {
        if (in_array($order->order_status, ['completed', 'cancelled'])) {
            return redirect()->back()->with('error', 'Cannot cancel a completed or already cancelled order.');
        }

        if ($blocked = $this->paymentsBlock($order, 'cancelled')) {
            return redirect()->back()->with('error', $blocked);
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
        if ($blocked = $this->paymentsBlock($order, 'deleted')) {
            return redirect()->back()->with('error', $blocked);
        }

        $orderNo = $order->order_no;

        DB::transaction(function () use ($order) {
            $order->delivery?->delete();
            $order->sales()->delete();
            $order->delete();
        });

        ActivityLogger::log('Orders', 'delete', "Deleted order {$orderNo}");

        return redirect()->route('orders.index')->with('success', 'Order deleted.');
    }

    /**
     * An order that money has been paid against can be neither cancelled nor deleted:
     * either would leave recorded payments, and the revenue reports, pointing at nothing.
     * The payments have to be removed first, which is a deliberate step.
     */
    private function paymentsBlock(Order $order, string $verb): ?string
    {
        $paid = round((float) $order->sales()->sum('amount'), 2);

        if ($paid <= 0) {
            return null;
        }

        return "Order {$order->order_no} has ₱".number_format($paid, 2)." in payments recorded, so it cannot be {$verb}. "
            .'Delete its payment records first if they were entered by mistake.';
    }
}
