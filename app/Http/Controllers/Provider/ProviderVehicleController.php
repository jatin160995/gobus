<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class ProviderVehicleController extends Controller
{
    // List all vehicles
    public function index()
    {
        $providerId = Auth::user()->providerUser->provider_id ?? null;

        if (!$providerId) {
            abort(403, "No provider assigned to this user.");
        }

        $vehicles = Vehicle::where('provider_id', $providerId)
            ->with('images')
            ->latest()
            ->paginate(10);

        return view('provider.vehicles.index', compact('vehicles'));
    }

    // Create page
    public function create()
    {
        return view('provider.vehicles.create');
    }

    // Store new vehicle
    public function store(Request $request)
    {
        $request->validate([
            'plate_number' => 'required|string|max:50',
            'model' => 'required|string|max:100',
            'capacity' => 'required|integer|min:1',
            'comfort_type' => 'required|in:standard,vip',
            'photo' => 'nullable|image|max:2048',
            'layout' => 'nullable|image|max:2048', // ✅ added
            'images.*' => 'nullable|image|max:2048'
        ]);

        $providerId = Auth::user()->providerUser->provider_id ?? null;

        if (!$providerId) {
            abort(403, "No provider assigned to this user.");
        }

        $photo = null;
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo')->store('vehicles', 'public');
        }

        $layout = null;
        if ($request->hasFile('layout')) {
            $layout = $request->file('layout')->store('vehicles/layouts', 'public');
        }

        $vehicle = Vehicle::create([
            'provider_id' => $providerId,
            'plate_number' => $request->plate_number,
            'model' => $request->model,
            'capacity' => $request->capacity,
            'comfort_type' => $request->comfort_type,
            'photo' => $photo,
            'layout' => $layout, // ✅ added
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $path = $img->store('vehicles/gallery', 'public');
                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $path
                ]);
            }
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Created vehicle: ' . $vehicle->plate_number
        ]);

        return redirect()->route('provider.vehicles.index')
            ->with('success', 'Vehicle added successfully.');
    }


    // Edit page
    public function edit($id)
    {
        $providerId = Auth::user()->providerUser->provider_id ?? null;

        if (!$providerId) {
            abort(403, "No provider assigned to this user.");
        }

        $vehicle = Vehicle::where('id', $id)
            ->where('provider_id', $providerId)
            ->with('images')
            ->firstOrFail();

        return view('provider.vehicles.edit', compact('vehicle'));
    }

    // Update vehicle
    public function update(Request $request, $id)
    {
        $providerId = Auth::user()->providerUser->provider_id ?? null;

        if (!$providerId) {
            abort(403, "No provider assigned to this user.");
        }

        $vehicle = Vehicle::where('id', $id)
            ->where('provider_id', $providerId)
            ->firstOrFail();

        $request->validate([
            'plate_number' => 'required|string|max:50',
            'model' => 'required|string|max:100',
            'capacity' => 'required|integer|min:1',
            'comfort_type' => 'required|in:standard,vip',
            'photo' => 'nullable|image|max:2048',
            'layout' => 'nullable|image|max:2048', // ✅ added
            'images.*' => 'nullable|image|max:2048'
        ]);

        $photo = $vehicle->photo;
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo')->store('vehicles', 'public');
        }

        $layout = $vehicle->layout;
        if ($request->hasFile('layout')) {
            $layout = $request->file('layout')->store('vehicles/layouts', 'public');
        }

        $vehicle->update([
            'plate_number' => $request->plate_number,
            'model' => $request->model,
            'capacity' => $request->capacity,
            'comfort_type' => $request->comfort_type,
            'photo' => $photo,
            'layout' => $layout, // ✅ added
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $path = $img->store('vehicles/gallery', 'public');
                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $path
                ]);
            }
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Updated vehicle: ' . $vehicle->plate_number
        ]);

        return redirect()
            ->route('provider.vehicles.edit', $vehicle->id)
            ->with('success', 'Vehicle updated successfully.');
    }


    // Delete a single gallery image
    public function deleteImage($imageId)
    {
        $image = VehicleImage::findOrFail($imageId);
        $image->delete();
        return back()->with('success', 'Image removed.');
    }

    // Delete Vehicle
    public function destroy($id)
    {
        $providerId = Auth::user()->providerUser->provider_id ?? null;

        if (!$providerId) {
            abort(403, "No provider assigned to this user.");
        }

        $vehicle = Vehicle::where('id', $id)
            ->where('provider_id', $providerId)
            ->firstOrFail();

        $vehicle->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Deleted vehicle: ' . $vehicle->plate_number
        ]);

        return redirect()->route('provider.vehicles.index')->with('success', 'Vehicle deleted successfully.');
    }
}
