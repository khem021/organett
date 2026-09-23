<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /** Roles a farm admin is allowed to assign. */
    private const ASSIGNABLE_ROLES = ['farm_admin', 'farm_staff'];

    public function index(Request $request)
    {
        $query = $this->farmUsers()
            ->orderByRaw("role = 'farm_admin' DESC")
            ->orderBy('full_name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                    ->orWhere('username', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();

        $userStats = [
            'total' => $this->farmUsers()->count(),
            'active' => $this->farmUsers()->where('status', 'active')->count(),
            'admin' => $this->farmUsers()->where('role', 'farm_admin')->count(),
        ];

        return view('users.index', compact('users', 'userStats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:150',
            'username' => 'required|string|max:80|unique:users|alpha_dash',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => 'required|in:'.implode(',', self::ASSIGNABLE_ROLES),
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = [
            'farm_id' => Auth::user()->farm_id,
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ];

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('avatars', 'public');
        }

        $user = new User($data);
        $user->role = $request->role;
        $user->status = 'active';
        $user->save();

        ActivityLogger::log('Users', 'create', "Created user: {$request->username} ({$request->role})");

        return back()->with('success', "User '{$request->full_name}' created successfully.");
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeSameFarm($user);

        $request->validate([
            'full_name' => 'required|string|max:150',
            'role' => 'required|in:'.implode(',', self::ASSIGNABLE_ROLES),
            'status' => 'required|in:active,inactive',
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($user->id === Auth::id()) {
            if ($request->role !== 'farm_admin') {
                return back()->with('error', 'You cannot change your own role.');
            }
            if ($request->status === 'inactive') {
                return back()->with('error', 'You cannot deactivate your own account.');
            }
        }

        // Prevent demoting / deactivating the last remaining admin of this farm
        if ($user->role === 'farm_admin' && ($request->role !== 'farm_admin' || $request->status === 'inactive')) {
            $adminCount = $this->farmUsers()
                ->where('role', 'farm_admin')
                ->where('status', 'active')
                ->count();

            if ($adminCount <= 1) {
                return back()->with('error', 'Cannot change this user — they are the last active administrator for the farm.');
            }
        }

        $data = ['full_name' => $request->full_name];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('avatars', 'public');
        }

        $user->fill($data);
        $user->role = $request->role;
        $user->status = $request->status;
        $user->save();

        ActivityLogger::log('Users', 'update', "Updated user: {$user->username} — role: {$user->role}, status: {$user->status}");

        return back()->with('success', "User '{$user->full_name}' updated.");
    }

    public function destroy(User $user)
    {
        $this->authorizeSameFarm($user);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->role === 'farm_admin') {
            $adminCount = $this->farmUsers()->where('role', 'farm_admin')->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Cannot delete the last administrator for the farm.');
            }
        }

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $name = $user->full_name;
        $username = $user->username;
        $user->delete();

        ActivityLogger::log('Users', 'delete', "Deleted user: {$username} ({$name})");

        return back()->with('success', "User '{$name}' deleted.");
    }

    /** Base query limited to the acting admin's own farm. */
    private function farmUsers()
    {
        return User::query()->where('farm_id', Auth::user()->farm_id);
    }

    /** Block any attempt to manage a user that belongs to another farm. */
    private function authorizeSameFarm(User $user): void
    {
        abort_unless($user->farm_id === Auth::user()->farm_id, 403);
    }
}
