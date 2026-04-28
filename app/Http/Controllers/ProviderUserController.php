<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ProviderUserController extends Controller
{
    // -------------------------------
    // Show Users Tab
    // -------------------------------
    public function index(Provider $provider)
    {
        // $assignedUsers = $provider->users()->get();

        // $availableUsers = User::where('role', 'provider')
        //     ->whereNotIn('id', $assignedUsers->pluck('id'))
        //     ->get();

        // return view('admin.providers.tabs.users', compact('provider', 'assignedUsers', 'availableUsers'));
        
        // Users already assigned to THIS provider
        $assignedUsers = $provider->users()->get();

        // IDs of users assigned to ANY provider
        $usedUserIds = \DB::table('provider_users')
            ->pluck('user_id');

        // Only free provider users
        $availableUsers = User::where('role', 'provider')
            ->whereNotIn('id', $usedUserIds)
            ->get();

        return view(
            'admin.providers.tabs.users',
            compact('provider', 'assignedUsers', 'availableUsers')
        );
    }

    // -------------------------------
    // Create Provider-Role User
    // -------------------------------
    public function create(Request $request)
    {
          // Accept optional provider_id so we can redirect back after creation
        $provider = null;
        if ($request->has('provider_id')) {
            $provider = \App\Models\Provider::find($request->provider_id);
        }

        return view('admin.providers.users_create', compact('provider'));
    }

    public function store(Request $req)
    {
        $req->validate([
            'name'        => 'required|max:150',
            'email'       => 'required|email|unique:users,email',
            'phone'       => 'required|unique:users,phone',
            'password'    => 'required|min:6|confirmed',
            'provider_id' => 'nullable|exists:providers,id',
        ], [
            'email.unique'    => 'This email address is already registered.',
            'phone.unique'    => 'This phone number is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min'    => 'Password must be at least 6 characters.',
        ]);

        User::create([
            'name'     => $req->name,
            'email'    => $req->email,
            'phone'    => $req->phone,
            'password' => Hash::make($req->password),
            'role'     => 'provider',
            'status'   => 'active',
        ]);

        // Redirect back to provider users tab if we came from a provider
        if ($req->provider_id) {
            return redirect()
                ->route('admin.providers.users', $req->provider_id)
                ->with('success', 'Provider user created successfully. You can now assign them below.');
        }

        return redirect()
            ->route('admin.provider-users.create')
            ->with('success', 'Provider user created successfully.');
    }

    // -------------------------------
    // Assign User To Provider
    // -------------------------------
    public function assignUser(Request $req, Provider $provider)
    {
        $req->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:manager,staff'
        ]);

        DB::table('provider_users')->insert([
            'provider_id' => $provider->id,
            'user_id' => $req->user_id,
            'role' => $req->role,
        ]);

        return back()->with('success', 'User assigned successfully.');
    }

    // -------------------------------
    // Remove User from Provider
    // -------------------------------
    public function removeUser(Provider $provider, User $user)
    {
        DB::table('provider_users')
            ->where('provider_id', $provider->id)
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('success', 'User removed successfully.');
    }
}
