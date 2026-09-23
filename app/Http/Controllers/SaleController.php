<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Sale;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $alreadyPaid = round((float) $order->sales()->sum('amount'), 2);
        $maxAllowed = round((float) $order->total_amount - $alreadyPaid, 2);

        $data = $request->validate([
            'sale_date' => 'required|date',
            'quantity_kg' => 'required|numeric|min:0.01',
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$maxAllowed],
            'payment_method' => 'required|in:Cash,GCash,Maya,Bank Transfer,Cheque,Other',
            'remarks' => 'nullable|string',
        ]);

        $data['order_id'] = $order->id;
        $data['customer_id'] = $order->customer_id;

        DB::transaction(function () use ($data, $order) {
            Sale::create($data);
            $this->syncPaymentStatus($order);
        });

        ActivityLogger::log(
            'Sales',
            'create',
            'Recorded ₱'.number_format($data['amount'], 2)." payment via {$data['payment_method']} for order {$order->order_no}"
        );

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Order $order, Sale $sale)
    {
        if ($sale->order_id !== $order->id) {
            abort(403);
        }

        $amount = $sale->amount;
        DB::transaction(function () use ($order, $sale) {
            $sale->delete();
            $this->syncPaymentStatus($order);
        });

        ActivityLogger::log('Sales', 'delete', 'Deleted ₱'.number_format($amount, 2)." payment record from order {$order->order_no}");

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment record deleted.');
    }

    private function syncPaymentStatus(Order $order): void
    {
        $totalPaid = round((float) $order->sales()->sum('amount'), 2);
        $orderTotal = round((float) $order->total_amount, 2);

        if ($totalPaid <= 0) {
            $status = 'unpaid';
        } elseif ($totalPaid >= $orderTotal) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }

        $order->update(['payment_status' => $status]);
    }
}
