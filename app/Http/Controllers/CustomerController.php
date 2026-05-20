<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('orders');

        if ($request->filled('search')) {
            $q = '%' . $request->search . '%';
            $query->where(function ($q2) use ($q) {
                $q2->where('customer_name', 'like', $q)
                   ->orWhere('contact_person', 'like', $q)
                   ->orWhere('phone', 'like', $q)
                   ->orWhere('email', 'like', $q);
            });
        }

        $customers = $query->orderBy('customer_name')->paginate(20)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'  => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:150',
            'phone'          => ['required', 'string', 'max:50', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'email'          => 'nullable|email|max:150',
            'address'        => 'required|string',
        ]);

        $customer = Customer::create($data);

        ActivityLogger::log('Customers', 'create', "Added customer: {$customer->customer_name}");

        return redirect()->route('customers.index')->with('success', 'Customer added successfully.');
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'customer_name'  => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:150',
            'phone'          => ['required', 'string', 'max:50', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'email'          => 'nullable|email|max:150',
            'address'        => 'required|string',
        ]);

        $customer->update($data);

        ActivityLogger::log('Customers', 'update', "Updated customer: {$customer->customer_name}");

        return redirect()->route('customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->orders()->count() > 0) {
            return redirect()->route('customers.index')
                ->with('error', 'Cannot delete customer with existing orders.');
        }

        $name = $customer->customer_name;
        $customer->delete();

        ActivityLogger::log('Customers', 'delete', "Deleted customer: {$name}");

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }
}
