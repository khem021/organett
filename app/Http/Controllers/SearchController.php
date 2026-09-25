<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductionBatch;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /** Global quick-search across the main tenant-scoped entities. */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['groups' => []]);
        }

        $like = '%'.$q.'%';

        $batches = ProductionBatch::whereLike('batch_code', $like)
            ->orWhereLike('substrate_type', $like)
            ->orderByDesc('id')->limit(5)->get()
            ->map(fn ($b) => [
                'title' => $b->batch_code,
                'subtitle' => ucfirst($b->status).' · '.$b->substrate_type,
                'url' => route('batches.show', $b),
            ]);

        $orders = Order::with('customer')
            ->where(function ($qq) use ($like) {
                $qq->whereLike('order_no', $like)
                    ->orWhereHas('customer', fn ($c) => $c->whereLike('customer_name', $like));
            })
            ->orderByDesc('id')->limit(5)->get()
            ->map(fn ($o) => [
                'title' => $o->order_no,
                'subtitle' => ($o->customer?->customer_name ?? 'Unknown customer').' · '.ucfirst($o->order_status),
                'url' => route('orders.show', $o),
            ]);

        $customers = Customer::whereLike('customer_name', $like)
            ->orWhereLike('phone', $like)
            ->orWhereLike('email', $like)
            ->orWhereLike('contact_person', $like)
            ->orderBy('customer_name')->limit(5)->get()
            ->map(fn ($c) => [
                'title' => $c->customer_name,
                'subtitle' => $c->phone,
                'url' => route('customers.index', ['search' => $c->customer_name]),
            ]);

        $inventory = Inventory::whereLike('item_name', $like)
            ->orWhereLike('category', $like)
            ->orderBy('item_name')->limit(5)->get()
            ->map(fn ($i) => [
                'title' => $i->item_name,
                'subtitle' => number_format($i->stock_qty, 1).' '.$i->unit.' in stock',
                'url' => route('inventory.show', $i),
            ]);

        $groups = collect([
            ['label' => 'Batches', 'items' => $batches],
            ['label' => 'Orders', 'items' => $orders],
            ['label' => 'Customers', 'items' => $customers],
            ['label' => 'Inventory', 'items' => $inventory],
        ])->filter(fn ($g) => $g['items']->isNotEmpty())->values();

        return response()->json(['groups' => $groups]);
    }
}
