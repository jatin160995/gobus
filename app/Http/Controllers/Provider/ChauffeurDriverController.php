<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChauffeurDriver;

class ChauffeurDriverController extends Controller
{
     private function getProviderId()
    {
        return auth()->user()->providers()->first()->id;
    }

    public function index()
    {
        $drivers = ChauffeurDriver::byProvider($this->getProviderId())
            ->latest()
            ->paginate(15);

        return view('provider.chauffeur.drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('provider.chauffeur.drivers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'license_number' => 'required|string|max:100|unique:chauffeur_drivers,license_number',
            'license_expiry' => 'required|date',
        ]);

        ChauffeurDriver::create([
            'provider_id' => $this->getProviderId(),
            'name' => $request->name,
            'phone' => $request->phone,
            'license_number' => $request->license_number,
            'license_expiry' => $request->license_expiry,
            'rating' => 5.00,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route('provider.chauffeur.drivers.index')
            ->with('success', 'Driver created successfully.');
    }

    public function edit($id)
    {
        $driver = ChauffeurDriver::byProvider($this->getProviderId())
            ->findOrFail($id);

        return view('provider.chauffeur.drivers.edit', compact('driver'));
    }

    public function update(Request $request, $id)
    {
        $driver = ChauffeurDriver::byProvider($this->getProviderId())
            ->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'license_number' => 'required|string|max:100|unique:chauffeur_drivers,license_number,' . $driver->id,
            'license_expiry' => 'required|date',
        ]);

        $driver->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'license_number' => $request->license_number,
            'license_expiry' => $request->license_expiry,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route('provider.chauffeur.drivers.index')
            ->with('success', 'Driver updated successfully.');
    }

    public function destroy($id)
    {
        $driver = ChauffeurDriver::byProvider($this->getProviderId())
            ->findOrFail($id);

        $driver->delete();

        return back()->with('success', 'Driver deleted successfully.');
    }
}