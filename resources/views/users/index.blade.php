@extends('layouts.app')
@section('title','User Management')
@section('page-title','User Management')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h2>User Accounts</h2>
        <p>{{ $users->count() }} total · {{ $users->where('status','active')->count() }} active · {{ $users->where('role','admin')->count() }} admin</p>
    </div>
    <div class="page-header-right">
        <button class="btn-primary" style="padding:.5rem 1rem;font-size:.875rem;" onclick="document.getElementById('create-user').showModal()">+ New User</button>
    </div>
</div>

@if(session('success'))
    <div class="flash flash-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="flash flash-error">{{ session('error') }}</div>
@endif

<style>
.avatar-circle {
    width: 2rem; height: 2rem; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
    border: 1px solid var(--card-border);
}
.avatar-initials {
    width: 2rem; height: 2rem; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .6875rem; font-weight: 700; color: #fff; flex-shrink: 0;
}

/* Photo upload area */
.photo-upload-wrap {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: .875rem;
}
.photo-preview {
    width: 3.5rem; height: 3.5rem; border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--card-border);
    background: var(--card-bg);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem; color: var(--text-muted);
    overflow: hidden; flex-shrink: 0;
}
.photo-preview img { width: 100%; height: 100%; object-fit: cover; }
.photo-upload-btn {
    flex: 1;
}
.photo-upload-label {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .4375rem .875rem;
    font-size: .8125rem; font-weight: 500; font-family: inherit;
    color: var(--text-muted);
    background: transparent;
    border: 1px dashed var(--card-border);
    border-radius: .5rem;
    cursor: pointer;
    transition: all .15s;
    width: 100%;
    justify-content: center;
}
.photo-upload-label:hover { border-color: var(--green-accent); color: var(--text); }
.photo-upload-label input { display: none; }
.photo-upload-hint { font-size: .7rem; color: var(--text-muted); margin-top: .25rem; text-align: center; }
</style>

<div class="card">
    @if($users->isEmpty())
        <div class="empty-state">No users found.</div>
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
                            <div class="avatar-initials" style="background:linear-gradient(135deg,{{ $u->role==='admin' ? '#14532d,#16a34a' : '#0c4a6e,#38bdf8' }});">
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
                    @if($u->role === 'admin')
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
                    <form method="POST" action="{{ route('users.destroy', $u) }}" style="display:inline" onsubmit="return true">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-sm btn-sm-red">Del</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
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
                <input type="text" name="full_name" class="form-input" placeholder="Juan dela Cruz" required>
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" placeholder="juandc" required>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" placeholder="juan@organett.local" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Min. 6 characters" required>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-input" placeholder="Repeat password" required>
            </div>
        </div>
        @if($errors->any())
            @foreach($errors->all() as $e)
                <div class="flash flash-error" style="margin-bottom:.5rem;padding:.5rem .75rem;">{{ $e }}</div>
            @endforeach
        @endif
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.25rem;font-size:.875rem;">Create User</button>
        </div>
    </form>
</dialog>

{{-- Edit User Modal --}}
<dialog id="edit-user">
    <div class="modal-title">Edit User <button class="modal-close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" id="edit-user-form" enctype="multipart/form-data">
        @csrf @method('PUT')

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
            <input type="text" name="full_name" id="edit-full-name" class="form-input" required>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" id="edit-role" class="form-select" required>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="edit-status" class="form-select" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div style="background:#0a1a0e;border:1px solid var(--card-border);border-radius:.5rem;padding:.75rem 1rem;margin-bottom:.875rem;font-size:.75rem;color:var(--text-muted);">
            Leave password blank to keep the current password.
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-input" placeholder="Leave blank to keep">
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
<script>document.getElementById('create-user').showModal();</script>
@endif

@endsection
