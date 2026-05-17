<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderByRaw("role = 'admin' DESC")->orderBy('full_name')->get();
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name'     => 'required|string|max:150',
            'username'      => 'required|string|max:80|unique:users|alpha_dash',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|string|min:6|confirmed',
            'role'          => 'required|in:admin,staff',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = [
            'full_name' => $request->full_name,
            'username'  => $request->username,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'status'    => 'active',
        ];

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('avatars', 'public');
        }

        User::create($data);

        return back()->with('success', "User '{$request->full_name}' created successfully.");
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'full_name'     => 'required|string|max:150',
            'role'          => 'required|in:admin,staff',
            'status'        => 'required|in:active,inactive',
            'password'      => 'nullable|string|min:6|confirmed',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Prevent admin from demoting or deactivating themselves
        if ($user->id === Auth::id()) {
            if ($request->role !== 'admin') {
                return back()->with('error', 'You cannot change your own role.');
            }
            if ($request->status === 'inactive') {
                return back()->with('error', 'You cannot deactivate your own account.');
            }
        }

        $data = [
            'full_name' => $request->full_name,
            'role'      => $request->role,
            'status'    => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('avatars', 'public');
        }

        $user->update($data);
        return back()->with('success', "User '{$user->full_name}' updated.");
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Delete photo from storage
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $name = $user->full_name;
        $user->delete();
        return back()->with('success', "User '{$name}' deleted.");
    }
}
