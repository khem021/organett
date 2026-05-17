<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'sale_date'      => 'required|date',
            'quantity_kg'    => 'required|numeric|min:0.01',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:100',
            'remarks'        => 'nullable|string',
        ]);

        $data['order_id']    = $order->id;
        $data['customer_id'] = $order->customer_id;

        Sale::create($data);

        // Auto-update payment_status based on total paid
        $this->syncPaymentStatus($order);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Order $order, Sale $sale)
    {
        $sale->delete();
        $this->syncPaymentStatus($order);

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
