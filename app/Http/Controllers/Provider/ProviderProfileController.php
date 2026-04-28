<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use App\Models\ActivityLog;

class ProviderProfileController extends Controller
{
    /**
     * Show the profile edit form.
     */
    public function edit()
    {
        $user         = Auth::user();
        $provider     = $user->currentProvider();
        $providerRole = optional($user->provideruser()->first())->role ?? 'manager';

        return view('provider.profile.edit', compact('user', 'provider', 'providerRole'));
    }

    /**
     * Update personal info (name, email, phone).
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone,' . $user->id],
        ]);

        $user->update($request->only('name', 'email', 'phone'));

        ActivityLog::create([
            'user_id'    => $user->id,
            'action'     => 'Updated personal profile information',
            'module'     => 'profile',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('provider.profile.edit')
            ->with('success', 'Personal information updated successfully.');
    }

    /**
     * Update agency / provider information.
     * Only managers are allowed.
     */
    public function updateAgency(Request $request)
    {
        $user         = Auth::user();
        $provider     = $user->currentProvider();
        $providerRole = optional($user->provideruser()->first())->role ?? 'staff';

        if (!$provider) {
            return redirect()->route('provider.profile.edit')
                ->with('error', 'No provider linked to your account.');
        }

        if ($providerRole !== 'manager') {
            return redirect()->route('provider.profile.edit')
                ->with('error', 'Only managers can edit agency information.');
        }

        $request->validate([
            'agency_name'    => ['required', 'string', 'max:150'],
            'agency_email'   => ['nullable', 'email', 'max:150'],
            'agency_phone'   => ['nullable', 'string', 'max:30'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'address'        => ['nullable', 'string', 'max:255'],
            'orange_msisdn'  => ['nullable', 'string', 'max:100'],
            'logo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data = [
            'name'           => $request->agency_name,
            'email'          => $request->agency_email,
            'phone'          => $request->agency_phone,
            'contact_person' => $request->contact_person,
            'address'        => $request->address,
            'orange_msisdn'  => $request->orange_msisdn,
        ];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($provider->logo && Storage::disk('public')->exists($provider->logo)) {
                Storage::disk('public')->delete($provider->logo);
            }
            $data['logo'] = $request->file('logo')->store('providers', 'public');
        }

        $provider->update($data);

        ActivityLog::create([
            'user_id'    => $user->id,
            'action'     => 'Updated agency information for: ' . $provider->name,
            'module'     => 'profile',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('provider.profile.edit')
            ->with('success', 'Agency information updated successfully.');
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'The current password you entered is incorrect.'])
                ->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        ActivityLog::create([
            'user_id'    => $user->id,
            'action'     => 'Changed account password',
            'module'     => 'profile',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('provider.profile.edit')
            ->with('success', 'Password updated successfully.');
    }

    /**
     * Show activity log.
     */
    public function activity()
    {
        $user = Auth::user();

        $logs = ActivityLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('provider.profile.activity', compact('logs'));
    }
}