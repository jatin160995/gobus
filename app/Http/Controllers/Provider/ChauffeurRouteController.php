<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChauffeurRoute;
use App\Models\ChauffeurRoutePrice;
use App\Models\City;

class ChauffeurRouteController extends Controller
{
    private function getProviderId()
    {
        return auth()->user()->providers()->first()->id;
    }

    public function index()
    {
        $routes = ChauffeurRoute::where('provider_id', $this->getProviderId())
            ->with(['fromCity', 'toCity'])
            ->latest()
            ->paginate(15);

        return view('provider.chauffeur.routes.index', compact('routes'));
    }

    public function create()
    {
        $cities = City::orderBy('name')->get();
        return view('provider.chauffeur.routes.create', compact('cities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_city_id' => 'required|exists:cities,id',
            'to_city_id' => 'required|different:from_city_id|exists:cities,id',
            'distance_km' => 'nullable|numeric',
            'estimated_duration_minutes' => 'nullable|integer',
        ]);

        ChauffeurRoute::create([
            'provider_id' => $this->getProviderId(),
            'from_city_id' => $request->from_city_id,
            'to_city_id' => $request->to_city_id,
            'distance_km' => $request->distance_km,
            'estimated_duration_minutes' => $request->estimated_duration_minutes,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route('provider.chauffeur.routes.index')
            ->with('success', 'Route created successfully.');
    }

    public function edit($id)
    {
        $route = ChauffeurRoute::where('provider_id', $this->getProviderId())
            ->with('prices')
            ->findOrFail($id);

        $cities = City::orderBy('name')->get();

        return view('provider.chauffeur.routes.edit', compact('route', 'cities'));
    }

    public function update(Request $request, $id)
    {
        $route = ChauffeurRoute::where('provider_id', $this->getProviderId())
            ->findOrFail($id);

        $request->validate([
            'from_city_id' => 'required|exists:cities,id',
            'to_city_id' => 'required|different:from_city_id|exists:cities,id',
        ]);

        $route->update([
            'from_city_id' => $request->from_city_id,
            'to_city_id' => $request->to_city_id,
            'distance_km' => $request->distance_km,
            'estimated_duration_minutes' => $request->estimated_duration_minutes,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        if ($request->has('prices')) {

            foreach ($request->prices as $category => $priceData) {

                // Skip empty rows
                if (
                    empty($priceData['one_way_price']) &&
                    empty($priceData['round_trip_price']) &&
                    empty($priceData['per_day_price'])
                ) {
                    continue;
                }

                ChauffeurRoutePrice::updateOrCreate(
                    [
                        'chauffeur_route_id' => $route->id,
                        'vehicle_category' => $category
                    ],
                    [
                        'one_way_price' => $priceData['one_way_price'] ?? 0,
                        'round_trip_price' => $priceData['round_trip_price'] ?? 0,
                        'per_day_price' => $priceData['per_day_price'] ?? 0,
                        'currency' => $priceData['currency'] ?? 'XAF',
                        'is_active' => isset($priceData['is_active']) ? 1 : 0,
                    ]
                );
            }
        }

        return redirect()->route('provider.chauffeur.routes.index')
            ->with('success', 'Route updated successfully.');
    }

    public function destroy($id)
    {
        $route = ChauffeurRoute::where('provider_id', $this->getProviderId())
            ->findOrFail($id);

        // Delete related prices
        $route->prices()->delete();

        // Delete route
        $route->delete();

        return redirect()->route('provider.chauffeur.routes.index')
            ->with('success', 'Route deleted successfully.');
    }
}