<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Sale;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'sale_date'      => 'required|date',
            'quantity_kg'    => 'required|numeric|min:0.01',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:Cash,GCash,Maya,Bank Transfer,Cheque,Other',
            'remarks'        => 'nullable|string',
        ]);

        $data['order_id']    = $order->id;
        $data['customer_id'] = $order->customer_id;

        Sale::create($data);
        $this->syncPaymentStatus($order);

        ActivityLogger::log(
            'Sales',
            'create',
            "Recorded ₱" . number_format($data['amount'], 2) . " payment via {$data['payment_method']} for order {$order->order_no}"
        );

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Order $order, Sale $sale)
    {
        $amount = $sale->amount;
        $sale->delete();
        $this->syncPaymentStatus($order);

        ActivityLogger::log('Sales', 'delete', "Deleted ₱" . number_format($amount, 2) . " payment record from order {$order->order_no}");

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment record deleted.');
    }

    private function syncPaymentStatus(Order $order): void
    {
        $totalPaid = $order->sales()->sum('amount');

        if ($totalPaid <= 0) {
            $status = 'unpaid';
        } elseif ($totalPaid >= $order->total_amount) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }

        $order->update(['payment_status' => $status]);
    }
}
