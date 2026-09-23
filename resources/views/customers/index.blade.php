@extends('layouts.app')
@section('title','Customers')
@section('page-title','Customers')
@section('page-step','4')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h2>Customer Management</h2>
        <p>{{ $customers->total() }} customer{{ $customers->total() !== 1 ? 's' : '' }} registered</p>
    </div>
    <div class="page-header-right">
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('create-customer').showModal()">
            + Add Customer
        </button>
    </div>
</div>

{{-- Search --}}
<form method="GET" action="{{ route('customers.index') }}">
<div class="filter-bar">
    <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Search name, contact, phone, email…">
    <button type="submit" class="btn-secondary">Search</button>
    @if(request('search'))
        <a href="{{ route('customers.index') }}" class="btn-secondary">Clear</a>
    @endif
</div>
</form>

{{-- Table --}}
<div class="card">
    @if($customers->isEmpty())
        <div class="empty-state-full">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            @if(request('search'))
                <p>No customers match "{{ request('search') }}".</p>
                <a href="{{ route('customers.index') }}" class="btn-secondary">Clear search</a>
            @else
                <p>No customers yet.</p>
                <small>Add the businesses or individuals you sell mushrooms to.</small>
                <button class="btn-primary" onclick="document.getElementById('create-customer').showModal()">+ Add Customer</button>
            @endif
        </div>
    @else
    <table>
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Contact Person</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Address</th>
                <th style="text-align:center;">Orders</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $customer)
            <tr>
                <td class="text-main">{{ $customer->customer_name }}</td>
                <td>{{ $customer->contact_person ?: '—' }}</td>
                <td>{{ $customer->phone }}</td>
                <td style="font-size:.75rem;">{{ $customer->email ?: '—' }}</td>
                <td style="font-size:.75rem;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $customer->address }}</td>
                <td style="text-align:center;">
                    @if($customer->orders_count > 0)
                        <a href="{{ route('orders.index') }}?search={{ urlencode($customer->customer_name) }}"
                           style="color:var(--green-light);font-weight:600;font-size:.8125rem;text-decoration:none;">
                            {{ $customer->orders_count }}
                        </a>
                    @else
                        <span style="color:var(--text-dim);">0</span>
                    @endif
                </td>
                <td style="white-space:nowrap;">
                    <button class="btn-sm btn-sm-blue"
                        onclick="openEdit(
                            {{ $customer->id }},
                            {{ json_encode($customer->customer_name) }},
                            {{ json_encode($customer->contact_person ?? '') }},
                            {{ json_encode($customer->phone) }},
                            {{ json_encode($customer->email ?? '') }},
                            {{ json_encode($customer->address) }}
                        )">Edit</button>
                    @if($customer->orders_count === 0)
                    <form method="POST" action="{{ route('customers.destroy', $customer) }}" style="display:inline"
                          data-confirm="Delete customer &ldquo;{{ $customer->customer_name }}&rdquo;?">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-sm btn-sm-red">Delete</button>
                    </form>
                    @else
                    <span class="btn-sm" style="color:var(--text-dim);cursor:not-allowed;opacity:.5;" title="Cannot delete — has {{ $customer->orders_count }} order(s)">Delete</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:1rem;">{{ $customers->links() }}</div>
    @endif
</div>

{{-- Create Customer Modal --}}
<dialog id="create-customer">
    <div class="modal-title">
        Add Customer
        <button class="modal-close" onclick="this.closest('dialog').close()">×</button>
    </div>
    <form method="POST" action="{{ route('customers.store') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Customer / Business Name <span style="color:var(--danger);">*</span></label>
            <input type="text" name="customer_name" class="form-input" value="{{ old('customer_name') }}" placeholder="e.g. Jollibee - SM Branch" required maxlength="150">
            @error('customer_name') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" class="form-input" value="{{ old('contact_person') }}" placeholder="Full name" maxlength="150">
                @error('contact_person') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Phone <span style="color:var(--danger);">*</span></label>
                <input type="text" name="phone" class="form-input" value="{{ old('phone') }}" placeholder="+63 9xx xxx xxxx" required maxlength="50">
                @error('phone') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="orders@example.com" maxlength="150">
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label class="form-label">Address <span style="color:var(--danger);">*</span></label>
            <textarea name="address" class="form-textarea" placeholder="Street, Barangay, City, Province" required>{{ old('address') }}</textarea>
            @error('address') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Save Customer</button>
        </div>
    </form>
</dialog>

{{-- Edit Customer Modal --}}
<dialog id="edit-customer">
    <div class="modal-title">
        Edit Customer
        <button class="modal-close" onclick="this.closest('dialog').close()">×</button>
    </div>
    <form method="POST" id="edit-form" action="{{ old('_edit_id') ? route('customers.update', old('_edit_id')) : '' }}">
        @csrf @method('PUT')
        <input type="hidden" name="_edit_id" id="edit-id-field" value="{{ old('_edit_id') }}">
        <div class="form-group">
            <label class="form-label">Customer / Business Name <span style="color:var(--danger);">*</span></label>
            <input type="text" name="customer_name" id="edit-name" class="form-input" value="{{ old('customer_name') }}" required maxlength="150">
            @error('customer_name') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" id="edit-contact" class="form-input" value="{{ old('contact_person') }}" maxlength="150">
                @error('contact_person') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Phone <span style="color:var(--danger);">*</span></label>
                <input type="text" name="phone" id="edit-phone" class="form-input" value="{{ old('phone') }}" required maxlength="50">
                @error('phone') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="edit-email" class="form-input" value="{{ old('email') }}" maxlength="150">
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label class="form-label">Address <span style="color:var(--danger);">*</span></label>
            <textarea name="address" id="edit-address" class="form-textarea" required>{{ old('address') }}</textarea>
            @error('address') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Save Changes</button>
        </div>
    </form>
</dialog>

<script>
function openEdit(id, name, contact, phone, email, address) {
    document.getElementById('edit-form').action = '/customers/' + id;
    document.getElementById('edit-id-field').value = id;
    document.getElementById('edit-name').value    = name;
    document.getElementById('edit-contact').value = contact;
    document.getElementById('edit-phone').value   = phone;
    document.getElementById('edit-email').value   = email;
    document.getElementById('edit-address').value = address;
    document.getElementById('edit-customer').showModal();
}
</script>

@if($errors->any())
<script>document.getElementById('{{ old('_edit_id') ? 'edit-customer' : 'create-customer' }}').showModal();</script>
@endif

@endsection
