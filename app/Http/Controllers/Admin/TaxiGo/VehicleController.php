<?php

namespace App\Http\Controllers\Admin\TaxiGo;

use App\Http\Controllers\Controller;
use App\Models\TaxiGo\Driver;
use App\Models\TaxiGo\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with('driver.user', 'driver.hub')->orderByDesc('is_active')->orderBy('plate')->paginate(20);

        $stats = [
            'total'      => Vehicle::count(),
            'active'     => Vehicle::where('is_active', true)->count(),
            'unassigned' => Vehicle::where('is_active', true)->whereNull('driver_id')->count(),
        ];

        return view('admin.taxigo.vehicles.index', compact('vehicles', 'stats'));
    }

    public function create()
    {
        return view('admin.taxigo.vehicles.form', ['vehicle' => new Vehicle(['model' => 'BAIC X35', 'seats' => 4, 'is_active' => true]), 'drivers' => $this->availableDrivers()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Vehicle::create($data + [
            'is_active' => $request->boolean('is_active'),
            'photo'     => $request->file('photo')?->store('taxigo/vehicles', 'public'),
        ]);

        return redirect()->route('admin.taxigo.vehicles.index')->with('success', __('taxigo.vehicle_created', ['plate' => $data['plate']]));
    }

    public function edit(Vehicle $vehicle)
    {
        return view('admin.taxigo.vehicles.form', ['vehicle' => $vehicle, 'drivers' => $this->availableDrivers($vehicle)]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $this->validated($request, $vehicle);

        if ($request->hasFile('photo')) {
            $vehicle->photo && Storage::disk('public')->delete($vehicle->photo);
            $data['photo'] = $request->file('photo')->store('taxigo/vehicles', 'public');
        }

        $vehicle->update($data + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.taxigo.vehicles.index')->with('success', __('taxigo.vehicle_saved', ['plate' => $vehicle->plate]));
    }

    /** Active drivers without a car, plus this car's current driver. */
    private function availableDrivers(?Vehicle $vehicle = null)
    {
        return Driver::with('user', 'hub')
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereDoesntHave('vehicle')->when($vehicle?->driver_id, fn ($q, $id) => $q->orWhere('id', $id)))
            ->get()
            ->sortBy(fn ($d) => $d->user->name);
    }

    private function validated(Request $request, ?Vehicle $vehicle = null): array
    {
        $data = $request->validate([
            'model'     => ['required', 'string', 'max:100'],
            'plate'     => ['required', 'string', 'max:20', Rule::unique('taxigo_vehicles', 'plate')->ignore($vehicle?->id)],
            'color'     => ['nullable', 'string', 'max:50'],
            'seats'     => ['required', 'integer', 'min:1', 'max:9'],
            'driver_id' => ['nullable', Rule::exists('taxigo_drivers', 'id'), Rule::unique('taxigo_vehicles', 'driver_id')->ignore($vehicle?->id)],
            'photo'     => ['nullable', 'image', 'max:4096'],
        ], [
            'driver_id.unique' => __('taxigo.driver_has_vehicle'),
        ]);

        $data['plate'] = strtoupper(trim($data['plate']));
        unset($data['photo']);

        return $data;
    }
}
