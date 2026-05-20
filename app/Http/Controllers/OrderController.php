<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Order;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

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
            $query->where('order_no', 'like', '%' . $request->search . '%')
                  ->orWhereHas('customer', fn ($q) => $q->where('customer_name', 'like', '%' . $request->search . '%'));
        }

        $orders    = $query->paginate(15)->withQueryString();
        $customers = Customer::orderBy('customer_name')->get();
        $summary   = [
            'total'      => Order::count(),
            'pending'    => Order::where('order_status', 'pending')->count(),
            'processing' => Order::where('order_status', 'processing')->count(),
            'revenue'    => Order::where('payment_status', 'paid')->sum('total_amount'),
        ];

        return view('orders.index', compact('orders', 'customers', 'summary'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'order_date'     => 'required|date',
            'delivery_date'  => 'required|date|after_or_equal:order_date',
            'item_name'      => 'required|string|max:150',
            'quantity_kg'    => 'required|numeric|min:0.01',
            'unit_price'     => 'required|numeric|min:0',
            'payment_status' => 'required|in:unpaid,partial,paid',
            'order_status'   => 'required|in:pending,processing,completed,cancelled',
            'notes'          => 'nullable|string',
        ]);

        $data['total_amount'] = $data['quantity_kg'] * $data['unit_price'];
        $data['order_no']     = 'ORD-' . now()->year . '-' . str_pad(Order::count() + 1, 3, '0', STR_PAD_LEFT);

        $order = Order::create($data);
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
            'order_status'   => 'required|in:pending,processing,completed,cancelled',
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
            'destination'         => 'required|string',
            'delivery_date'       => 'required|date',
            'transport_status'    => 'required|in:scheduled,in_transit,delivered,cancelled',
            'assigned_personnel'  => 'nullable|string|max:150',
            'vehicle_info'        => 'nullable|string|max:150',
            'remarks'             => 'nullable|string',
        ]);

        Delivery::updateOrCreate(['order_id' => $order->id], $data);

        ActivityLogger::log('Orders', 'delivery', "Updated delivery info for order {$order->order_no}");

        return redirect()->back()->with('success', 'Delivery info updated.');
    }

    public function printReceipt(Order $order)
    {
        $order->load(['customer', 'delivery', 'sales']);
        $farmName    = \App\Models\Setting::getValue('farm_name', config('app.name', 'Organett'));
        $farmAddress = \App\Models\Setting::getValue('farm_address', '');
        $farmContact = \App\Models\Setting::getValue('farm_contact', '');
        return view('orders.print', compact('order', 'farmName', 'farmAddress', 'farmContact'));
    }

    public function destroy(Order $order)
    {
        $orderNo = $order->order_no;
        $order->delivery?->delete();
        $order->sales()->delete();
        $order->delete();

        ActivityLogger::log('Orders', 'delete', "Deleted order {$orderNo}");

        return redirect()->route('orders.index')->with('success', 'Order deleted.');
    }
}
