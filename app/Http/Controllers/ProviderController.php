<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ProviderController extends Controller
{
    // -----------------------------------
    // LIST PROVIDERS
    // -----------------------------------
    public function index()
    {
        $providers = Provider::orderBy('id', 'DESC')->paginate(20);

        return view('admin.providers.index', compact('providers'));
    }

    // -----------------------------------
    // CREATE FORM
    // -----------------------------------
    public function create()
    {
        return view('admin.providers.create');
    }

    // -----------------------------------
    // STORE NEW PROVIDER
    // -----------------------------------
    public function store(Request $req)
    {
        $req->validate([
            'name' => 'required|max:150',
            'contact_person' => 'nullable|max:150',
            'phone' => 'nullable|max:30',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|max:255',
            'commission_rate' => 'required|numeric|min:0|max:99.99',
            'payout_method' => 'required|in:auto,manual',
            'status' => 'required|in:active,inactive',
            'type' => 'required|in:bus,train,air,car',
            'orange_msisdn' => 'nullable|max:100',
            'mtn_msisdn' => 'nullable|max:100',
            'logo' => 'nullable|image|max:2048'
        ]);

        $logo = null;

        if ($req->hasFile('logo')) {
            $logo = $req->file('logo')->store('providers', 'public');
        }

        Provider::create([
            'name' => $req->name,
            'logo' => $logo,
            'contact_person' => $req->contact_person,
            'phone' => $req->phone,
            'email' => $req->email,
            'address' => $req->address,
            'commission_rate' => $req->commission_rate,
            'payout_method' => $req->payout_method,
            'status' => $req->status,
            'type' => $req->type,
            'orange_msisdn' => $req->orange_msisdn,
            'mtn_msisdn' => $req->orange_msisdn,
        ]);

        return redirect()->route('admin.providers.list')
            ->with('success', 'Provider created successfully.');
    }


    // to detail page
    public function show($id)
    {
        // $provider = Provider::findOrFail($id);
        // return view('admin.providers.show', compact('provider'));
        return redirect()->route('admin.providers.users', $id);
    }

    // ProviderController
    public function edit(Provider $provider) {
        return view('admin.providers.edit', compact('provider'));
    }

    public function update(Request $request, Provider $provider) {
        $data = $request->validate([
            'name'            => 'required|string|max:150',
            'address'         => 'nullable|string|max:255',
            'contact_person'  => 'nullable|string|max:150',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:150',
            'orange_msisdn'   => 'nullable|string|max:100',
            'mtn_msisdn'   => 'nullable|string|max:100',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'payout_method'   => 'nullable|in:auto,manual',
            'status'          => 'required|in:active,inactive',
            'type'            => 'required|in:bus,train,air,car',
            'logo'            => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('providers', 'public');
        } else {
            unset($data['logo']);
        }

        $provider->update($data);
        return redirect()->route('admin.providers.show', $provider->id)
            ->with('success', __('provider_edit.saved'));
    }


    public function destroy(Provider $provider)
    {
        DB::transaction(function () use ($provider) {

        // 1️⃣ Delete trips FIRST (they depend on vehicles)
        $provider->trips()->delete();

        // 2️⃣ Delete routes (if routes depend on provider)
        $provider->routes()->delete();

        // 3️⃣ Delete vehicles
        $provider->vehicles()->delete();

        // 4️⃣ Detach provider users (pivot table)
        $provider->users()->detach();

        // 5️⃣ Finally delete provider
        $provider->delete();
    });


        // Activity log (since you already use automatic logging)
        
         ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Provider Deleted '.$provider->name
        ]);

        return redirect()
            ->route('admin.providers.list')
            ->with('success', __('provider_show.deleted_success'));
    }


    public function trips(Provider $provider)
    {
       $trips = Trip::with([
            'route.departureCity',
            'route.arrivalCity',
            'vehicle'
        ])
        ->where('provider_id', $provider->id)
        ->latest()
        ->get();

        return view('admin.providers.tabs.trips', compact('provider', 'trips'));
    }

    public function vehicles(Provider $provider)
    {
        $vehicles = $provider->vehicles()->latest()->get();

        return view('admin.providers.tabs.vehicles', compact('provider', 'vehicles'));
    }

    public function routes(Provider $provider)
    {
        $routes = $provider->routes()->latest()->get();

        return view('admin.providers.tabs.routes', compact('provider', 'routes'));
    }
    public function carVehicles(Provider $provider)
    {
        $vehicles = $provider->chauffeurVehicles()->with('images')->latest()->get();
        return view('admin.providers.tabs.car_vehicles', compact('provider', 'vehicles'));
    }

    public function carRoutes(Provider $provider)
    {
        $routes = $provider->chauffeurRoutes()->with(['fromCity', 'toCity', 'prices'])->latest()->get();
        return view('admin.providers.tabs.car_routes', compact('provider', 'routes'));
    }

    public function carDrivers(Provider $provider)
    {
        $drivers = $provider->chauffeurDrivers()->latest()->get();
        return view('admin.providers.tabs.car_drivers', compact('provider', 'drivers'));
    }

    public function carBookings(Provider $provider)
    {
        $bookings = $provider->chauffeurBookings()
            ->with(['user', 'chauffeurRoute.fromCity', 'chauffeurRoute.toCity'])
            ->latest()
            ->get();
        return view('admin.providers.tabs.car_bookings', compact('provider', 'bookings'));
    }
    


}
