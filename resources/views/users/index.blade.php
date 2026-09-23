@extends('layouts.app')
@section('title','User Management')
@section('page-title','User Management')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h2>User Accounts</h2>
        <p>{{ $userStats['total'] }} total · {{ $userStats['active'] }} active · {{ $userStats['admin'] }} admin</p>
    </div>
    <div class="page-header-right">
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('create-user').showModal()">+ New User</button>
    </div>
</div>

{{-- Search --}}
<form method="GET" action="{{ route('users.index') }}">
<div class="filter-bar">
    <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Search name, username, email…">
    <button type="submit" class="btn-secondary">Search</button>
    @if(request('search'))
        <a href="{{ route('users.index') }}" class="btn-secondary">Clear</a>
    @endif
</div>
</form>

<div class="card">
    @if($users->isEmpty())
        <div class="empty-state-full">
            <div class="empty-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            @if(request('search'))
                <p>No users match "{{ request('search') }}".</p>
                <a href="{{ route('users.index') }}" class="btn-secondary">Clear search</a>
            @else
                <p>No users yet.</p>
                <small>Add staff and admin accounts for your farm.</small>
                <button class="btn-primary" onclick="document.getElementById('create-user').showModal()">+ New User</button>
            @endif
        </div>
    @else
    <table>
        <thead>
            <tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @foreach($users as $u)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:.625rem;">
                        @if($u->profile_photo)
                            <img src="{{ Storage::url($u->profile_photo) }}" alt="{{ $u->full_name }}" class="avatar-circle">
                        @else
                            <div class="avatar-initials" style="background:linear-gradient(135deg,{{ $u->role==='farm_admin' ? '#14532d,#16a34a' : '#0c4a6e,#38bdf8' }});">
                                {{ strtoupper(substr($u->full_name,0,1)) }}
                            </div>
                        @endif
                        <span class="text-main">{{ $u->full_name }}</span>
                        @if($u->id === Auth::id())
                            <span style="font-size:.625rem;background:#14532d33;color:var(--green-light);padding:.1rem .4rem;border-radius:999px;font-weight:600;">You</span>
                        @endif
                    </div>
                </td>
                <td style="font-size:.75rem;color:var(--text-muted);">{{ $u->username }}</td>
                <td style="font-size:.75rem;">{{ $u->email }}</td>
                <td>
                    @if($u->role === 'farm_admin')
                        <span class="badge badge-green"><span class="badge-dot"></span>Admin</span>
                    @else
                        <span class="badge badge-blue"><span class="badge-dot"></span>Staff</span>
                    @endif
                </td>
                <td>
                    @if($u->status === 'active')
                        <span class="badge badge-green"><span class="badge-dot"></span>Active</span>
                    @else
                        <span class="badge badge-red"><span class="badge-dot"></span>Inactive</span>
                    @endif
                </td>
                <td style="white-space:nowrap;">
                    <button class="btn-sm btn-sm-blue"
                        onclick="openEdit({{ $u->id }},'{{ addslashes($u->full_name) }}','{{ $u->role }}','{{ $u->status }}','{{ $u->profile_photo ? Storage::url($u->profile_photo) : '' }}')">
                        Edit
                    </button>
                    @if($u->id !== Auth::id())
                    <form method="POST" action="{{ route('users.destroy', $u) }}" style="display:inline"
                          data-confirm="Delete user &ldquo;{{ $u->full_name }}&rdquo;? This cannot be undone.">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-sm btn-sm-red">Del</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:1rem;">{{ $users->links() }}</div>
    @endif
</div>

{{-- Create User Modal --}}
<dialog id="create-user">
    <div class="modal-title">New User <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- Photo Upload --}}
        <div class="photo-upload-wrap">
            <div class="photo-preview" id="create-preview">
                <span>👤</span>
            </div>
            <div class="photo-upload-btn">
                <label class="photo-upload-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Choose Photo
                    <input type="file" name="profile_photo" accept="image/*" onchange="previewPhoto(this,'create-preview')">
                </label>
                <div class="photo-upload-hint">JPG, PNG or WebP · Max 2MB</div>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-input" value="{{ old('full_name') }}" placeholder="Juan dela Cruz" required>
                @error('full_name') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" value="{{ old('username') }}" placeholder="juandc" required>
                @error('username') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="farm_staff" @selected(old('role', 'farm_staff')==='farm_staff')>Staff</option>
                    <option value="farm_admin" @selected(old('role')==='farm_admin')>Admin</option>
                </select>
                @error('role') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="juan@organett.local" required>
                @error('email') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Min. 8 chars, letters + numbers" required>
                @error('password') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-input" placeholder="Repeat password" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Create User</button>
        </div>
    </form>
</dialog>

{{-- Edit User Modal --}}
<dialog id="edit-user">
    <div class="modal-title">Edit User <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" id="edit-user-form" enctype="multipart/form-data"
          action="{{ old('_edit_id') ? route('users.update', old('_edit_id')) : '' }}">
        @csrf @method('PUT')
        <input type="hidden" name="_edit_id" id="edit-user-id-field" value="{{ old('_edit_id') }}">

        {{-- Photo Upload --}}
        <div class="photo-upload-wrap">
            <div class="photo-preview" id="edit-preview">
                <span id="edit-preview-initials">👤</span>
            </div>
            <div class="photo-upload-btn">
                <label class="photo-upload-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Change Photo
                    <input type="file" name="profile_photo" accept="image/*" onchange="previewPhoto(this,'edit-preview')">
                </label>
                <div class="photo-upload-hint">Leave blank to keep current photo</div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" id="edit-full-name" class="form-input" value="{{ old('full_name') }}" required>
            @error('full_name') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" id="edit-role" class="form-select" required>
                    <option value="farm_staff" @selected(old('role')==='farm_staff')>Staff</option>
                    <option value="farm_admin" @selected(old('role')==='farm_admin')>Admin</option>
                </select>
                @error('role') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="edit-status" class="form-select" required>
                    <option value="active" @selected(old('status')==='active')>Active</option>
                    <option value="inactive" @selected(old('status')==='inactive')>Inactive</option>
                </select>
                @error('status') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div style="background:#0a1a0e;border:1px solid var(--card-border);border-radius:.5rem;padding:.75rem 1rem;margin-bottom:.875rem;font-size:.75rem;color:var(--text-muted);">
            Leave password blank to keep the current password.
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-input" placeholder="Leave blank to keep">
                @error('password') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-input" placeholder="Repeat new password">
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Save Changes</button>
        </div>
    </form>
</dialog>

<script>
function previewPhoto(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openEdit(id, fullName, role, status, photoUrl) {
    document.getElementById('edit-user-form').action = '/users/' + id;
    document.getElementById('edit-user-id-field').value = id;
    document.getElementById('edit-full-name').value = fullName;
    document.getElementById('edit-role').value = role;
    document.getElementById('edit-status').value = status;

    const preview = document.getElementById('edit-preview');
    if (photoUrl) {
        preview.innerHTML = `<img src="${photoUrl}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
    } else {
        preview.innerHTML = `<span>👤</span>`;
    }

    document.getElementById('edit-user').showModal();
}
</script>

@if($errors->any())
<script>document.getElementById('{{ old('_edit_id') ? 'edit-user' : 'create-user' }}').showModal();</script>
@endif

@endsection
