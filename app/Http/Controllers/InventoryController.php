<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventory::latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $query->whereLike('item_name', '%'.$request->search.'%');
        }
        if ($request->filter === 'low') {
            $query->whereColumn('stock_qty', '<=', 'reorder_level');
        }

        $items = $query->paginate(15)->withQueryString();
        $lowCount = Inventory::whereColumn('stock_qty', '<=', 'reorder_level')->count();
        // Store as plain array — Collections don't survive PHP 8.4 file cache deserialization cleanly
        $categories = cache()->remember($this->farmCacheKey('inventory.categories'), 86400, fn () => Inventory::distinct()->pluck('category')->sort()->values()->toArray()
        );
        $recent = InventoryTransaction::with(['inventoryItem', 'creator'])->latest()->take(8)->get();

        return view('inventory.index', compact('items', 'lowCount', 'categories', 'recent'));
    }

    public function show(Inventory $inventory)
    {
        $transactions = $inventory->transactions()
            ->with('creator')
            ->latest('transaction_date')
            ->paginate(20);

        $totalIn = (float) $inventory->transactions()->where('transaction_type', 'in')->sum('quantity');
        $totalOut = (float) $inventory->transactions()->where('transaction_type', 'out')->sum('quantity');

        return view('inventory.show', compact('inventory', 'transactions', 'totalIn', 'totalOut'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_name' => 'required|string|max:150',
            'category' => 'required|string|max:120',
            'unit' => 'required|string|max:50',
            'stock_qty' => 'required|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:150',
        ]);
        $item = Inventory::create($data);
        cache()->forget($this->farmCacheKey('inventory.categories'));

        ActivityLogger::log('Inventory', 'create', "Added inventory item: {$item->item_name} ({$item->stock_qty} {$item->unit})");

        return redirect()->route('inventory.index')->with('success', 'Item added to inventory.');
    }

    public function update(Request $request, Inventory $inventory)
    {
        $data = $request->validate([
            'item_name' => 'required|string|max:150',
            'category' => 'required|string|max:120',
            'unit' => 'required|string|max:50',
            'reorder_level' => 'required|numeric|min:0.01',
            'location' => 'nullable|string|max:150',
        ]);

        $inventory->update($data);
        cache()->forget($this->farmCacheKey('inventory.categories'));

        ActivityLogger::log('Inventory', 'update', "Updated inventory item: {$inventory->item_name}");

        return redirect()->route('inventory.index')->with('success', 'Item updated.');
    }

    public function adjust(Request $request, Inventory $inventory)
    {
        $data = $request->validate([
            'transaction_type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);

        $insufficient = false;
        $updatedQty = 0;

        DB::transaction(function () use ($data, $inventory, &$insufficient, &$updatedQty) {
            $locked = Inventory::lockForUpdate()->find($inventory->id);

            if ($data['transaction_type'] === 'out' && $locked->stock_qty < $data['quantity']) {
                $insufficient = true;

                return;
            }

            InventoryTransaction::create([
                'inventory_id' => $locked->id,
                'transaction_type' => $data['transaction_type'],
                'quantity' => $data['quantity'],
                'transaction_date' => now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $locked->stock_qty = $data['transaction_type'] === 'in'
                ? $locked->stock_qty + $data['quantity']
                : $locked->stock_qty - $data['quantity'];
            $locked->save();

            $updatedQty = $locked->stock_qty;
        });

        if ($insufficient) {
            return back()->with('error', 'Insufficient stock.');
        }

        // Refresh so the activity log sees the updated qty
        $inventory->stock_qty = $updatedQty;

        ActivityLogger::log(
            'Inventory',
            'adjust',
            "Stock {$data['transaction_type']}: {$data['quantity']} {$inventory->unit} of {$inventory->item_name} (new qty: {$inventory->stock_qty})"
        );

        return redirect()->route('inventory.index')->with('success', 'Stock adjusted.');
    }

    public function destroy(Inventory $inventory)
    {
        $name = $inventory->item_name;
        $inventory->delete();
        cache()->forget($this->farmCacheKey('inventory.categories'));

        ActivityLogger::log('Inventory', 'delete', "Removed inventory item: {$name}");

        return redirect()->route('inventory.index')->with('success', 'Item removed.');
    }
}
