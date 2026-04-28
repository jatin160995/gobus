<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ChauffeurVehicle;
use App\Models\ChauffeurVehicleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChauffeurVehicleController extends Controller
{
    private function getProviderId()
    {
        return auth()->user()->providers()->first()->id;
    }

    public function index()
    {
        $vehicles = ChauffeurVehicle::where('provider_id', $this->getProviderId())
            ->with('images')
            ->latest()
            ->get();

        return view('provider.chauffeur.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        return view('provider.chauffeur.vehicles.create');
    }

   public function store(Request $request)
    {
        $request->validate([
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'category' => 'required|in:standard,executive,premium,suv',
            'seats' => 'required|integer|min:1|max:50',
            'plate_number' => 'required|string|max:50',
            'color' => 'nullable|string|max:50',
            'transmission' => 'nullable|in:automatic,manual',

            'images' => 'required|array|min:1',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_types' => 'required|array|min:1',
            'image_types.*' => 'required|in:front,rear,interior,left_side,right_side,other',
        ]);

        $vehicle = ChauffeurVehicle::create([
            'provider_id' => $this->getProviderId(),
            'brand' => $request->brand,
            'model' => $request->model,
            'year' => $request->year,
            'category' => $request->category,
            'seats' => $request->seats,
            'plate_number' => $request->plate_number,
            'color' => $request->color,
            'transmission' => $request->transmission ?? 'automatic',
            'active' => $request->has('is_active') ? 1 : 0,
        ]);

        // Store Images
        foreach ($request->images as $index => $image) {

            $type = $request->image_types[$index] ?? 'other';

            $this->storeImage(
                $image,
                $vehicle->id,
                $type
            );
        }

        return redirect()
            ->route('provider.chauffeur.vehicles.index')
            ->with('success', 'Vehicle created successfully.');
    }

    public function edit($id)
    {
        $vehicle = ChauffeurVehicle::where('provider_id', $this->getProviderId())
            ->with('images')
            ->findOrFail($id);

        return view('provider.chauffeur.vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, $id)
    {
        $vehicle = ChauffeurVehicle::where('provider_id', $this->getProviderId())
            ->findOrFail($id);

        $request->validate([
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'category' => 'required|in:standard,executive,premium,suv',
            'seats' => 'required|integer|min:1|max:50',
            'plate_number' => 'required|string|max:50',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_types.*' => 'nullable|in:front,rear,interior,left_side,right_side,other',
        ]);

        $vehicle->update([
            'brand' => $request->brand,
            'model' => $request->model,
            'year' => $request->year,
            'category' => $request->category,
            'seats' => $request->seats,
            'plate_number' => $request->plate_number,
            'color' => $request->color,
            'transmission' => $request->transmission ?? 'automatic',
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        // Delete selected images
        if ($request->has('delete_images')) {
            foreach ($request->delete_images as $imageId) {
                $image = $vehicle->images()->find($imageId);
                if ($image) {
                    @unlink(public_path($image->image_path));
                    $image->delete();
                }
            }
        }

        // Add new images
        if ($request->hasFile('images')) {
            foreach ($request->images as $index => $image) {

                if ($image) {
                    $type = $request->image_types[$index] ?? 'other';

                    $this->storeImage(
                        $image,
                        $vehicle->id,
                        $type
                    );
                }
            }
        }

        return redirect()
            ->route('provider.chauffeur.vehicles.index')
            ->with('success', 'Vehicle updated successfully.');
    }
    public function destroy($id)
    {
        $vehicle = ChauffeurVehicle::where('provider_id', $this->getProviderId())
            ->findOrFail($id);

        foreach ($vehicle->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $vehicle->delete();

        return redirect()->back()->with('success', 'Vehicle deleted successfully');
    }

    private function storeImage($file, $vehicleId, $type)
    {
        $path = $file->store('chauffeur/vehicles', 'public');

        ChauffeurVehicleImage::create([
            'chauffeur_vehicle_id' => $vehicleId,
            'image_type' => $type,
            'image_path' => $path
        ]);
    }

    private function replaceImage($file, $vehicleId, $type)
    {
        $old = ChauffeurVehicleImage::where([
            'chauffeur_vehicle_id' => $vehicleId,
            'image_type' => $type
        ])->first();

        if ($old) {
            Storage::disk('public')->delete($old->image_path);
            $old->delete();
        }

        $this->storeImage($file, $vehicleId, $type);
    }
}