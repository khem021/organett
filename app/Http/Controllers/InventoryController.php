<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventory::latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $query->where('item_name', 'like', '%' . $request->search . '%');
        }
        if ($request->filter === 'low') {
            $query->whereColumn('stock_qty', '<=', 'reorder_level');
        }

        $items      = $query->paginate(15)->withQueryString();
        $lowCount   = Inventory::whereColumn('stock_qty', '<=', 'reorder_level')->count();
        $categories = Inventory::distinct()->pluck('category');
        $recent     = InventoryTransaction::with(['inventoryItem', 'creator'])->latest()->take(8)->get();

        return view('inventory.index', compact('items', 'lowCount', 'categories', 'recent'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_name'     => 'required|string|max:150',
            'category'      => 'required|string|max:120',
            'unit'          => 'required|string|max:50',
            'stock_qty'     => 'required|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'location'      => 'nullable|string|max:150',
        ]);
        $item = Inventory::create($data);

        ActivityLogger::log('Inventory', 'create', "Added inventory item: {$item->item_name} ({$item->stock_qty} {$item->unit})");

        return redirect()->route('inventory.index')->with('success', 'Item added to inventory.');
    }

    public function adjust(Request $request, Inventory $inventory)
    {
        $data = $request->validate([
            'transaction_type' => 'required|in:in,out',
            'quantity'         => 'required|numeric|min:0.01',
            'notes'            => 'nullable|string|max:255',
        ]);

        if ($data['transaction_type'] === 'out' && $inventory->stock_qty < $data['quantity']) {
            return back()->with('error', 'Insufficient stock.');
        }

        InventoryTransaction::create([
            'inventory_id'     => $inventory->id,
            'transaction_type' => $data['transaction_type'],
            'quantity'         => $data['quantity'],
            'transaction_date' => now()->toDateString(),
            'notes'            => $data['notes'] ?? null,
            'created_by'       => Auth::id(),
        ]);

        $inventory->stock_qty = $data['transaction_type'] === 'in'
            ? $inventory->stock_qty + $data['quantity']
            : $inventory->stock_qty - $data['quantity'];
        $inventory->save();

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

        ActivityLogger::log('Inventory', 'delete', "Removed inventory item: {$name}");

        return redirect()->route('inventory.index')->with('success', 'Item removed.');
    }
}
