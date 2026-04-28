<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\TravelRoute;
use App\Models\City;
use App\Models\ProviderUser;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProviderRouteController extends Controller
{
    // private function providerId()
    // {
    //     $providerUser = ProviderUser::where('user_id', Auth::id())->first();
    //     return $providerUser?->provider_id;
    // }
    private function providerId()
    {
        $providerUser = \App\Models\ProviderUser::where('user_id', Auth::id())->first();

        if (!$providerUser) {
            abort(403, "Provider mapping missing. Add this user to a provider in admin panel.");
        }

        return $providerUser->provider_id;
    }


    public function index()
    {
        $providerId = $this->providerId();

        $routes = TravelRoute::where('provider_id', $providerId)
            ->with(['departureCity', 'arrivalCity'])
            ->orderByDesc('id')
            ->paginate(15);

        return view('provider.routes.index', compact('routes'));
    }

    public function create()
    {
        $cities = City::orderBy('name')->get();
        return view('provider.routes.create', compact('cities'));
    }

    public function store(Request $request)
    {
        $providerId = $this->providerId();

        $request->validate([
            'departure_city_id' => 'required|exists:cities,id',
            'arrival_city_id' => 'required|exists:cities,id|different:departure_city_id',
            'distance_km' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:1',
            'transport_type' => 'required|in:bus,train,air,car',
        ]);

        $route = TravelRoute::create([
            'provider_id'        => $providerId,
            'departure_city_id'  => $request->departure_city_id,
            'arrival_city_id'    => $request->arrival_city_id,
            'distance_km'        => $request->distance_km,
            'duration_minutes'   => $request->duration_minutes,
            'transport_type'     => $request->transport_type,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Provider created route #' . $route->id
        ]);

        return redirect()->route('provider.routes.index')
            ->with('success', __('routes.messages.created'));
    }

    public function edit($id)
    {
        $providerId = $this->providerId();

        $route = TravelRoute::where('provider_id', $providerId)->findOrFail($id);

        $cities = City::orderBy('name')->get();

        return view('provider.routes.edit', compact('route', 'cities'));
    }

    public function update(Request $request, $id)
    {
        $providerId = $this->providerId();

        $request->validate([
            'departure_city_id' => 'required|exists:cities,id',
            'arrival_city_id' => 'required|exists:cities,id|different:departure_city_id',
            'distance_km' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:1',
            'transport_type' => 'required|in:bus,train,air,car',
        ]);

        $route = TravelRoute::where('provider_id', $providerId)->findOrFail($id);

        $route->update($request->all());

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Provider updated route #' . $route->id
        ]);

        return redirect()->route('provider.routes.index')
            ->with('success', __('routes.messages.updated'));
    }

    public function destroy($id)
    {
        $providerId = $this->providerId();

        $route = TravelRoute::where('provider_id', $providerId)->findOrFail($id);
        $route->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Provider deleted route #' . $id
        ]);

        return redirect()->route('provider.routes.index')
            ->with('success', __('routes.messages.deleted'));
    }

    
}
