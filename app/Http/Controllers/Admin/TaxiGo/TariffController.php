<?php

namespace App\Http\Controllers\Admin\TaxiGo;

use App\Http\Controllers\Controller;
use App\Models\TaxiGo\Hub;
use App\Models\TaxiGo\InterurbanDestination;
use App\Models\TaxiGo\Neighbourhood;
use App\Models\TaxiGo\Ride;
use App\Models\TaxiGo\Zone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Fares are copied onto each ride when it is booked, so changes here
 * only affect new bookings.
 */
class TariffController extends Controller
{
    public function index()
    {
        $hubs = Hub::orderBy('sort')->with([
            'zones' => fn ($q) => $q->orderBy('sort'),
            'zones.neighbourhoods' => fn ($q) => $q->orderBy('name'),
            'interurbanDestinations' => fn ($q) => $q->orderBy('sort'),
        ])->get();

        return view('admin.taxigo.tariffs.index', compact('hubs'));
    }

    public function updateHub(Request $request, Hub $hub)
    {
        $data = $request->validate([
            'night_start'     => ['required', 'date_format:H:i'],
            'night_end'       => ['required', 'date_format:H:i'],
            'night_surcharge' => ['required', 'integer', 'min:0', 'max:1000000'],
            'vip_hourly_rate' => ['required', 'integer', 'min:0', 'max:10000000'],
            'vip_min_hours'   => ['required', 'integer', 'min:1', 'max:24'],
        ]);

        $hub->update($data);

        return back()->with('success', __('taxigo.saved_hub', ['hub' => $hub->name]));
    }

    public function updateZones(Request $request, Hub $hub)
    {
        $data = $request->validate([
            'zones'          => ['required', 'array'],
            'zones.*.fare'   => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);

        foreach ($hub->zones as $zone) {
            if (isset($data['zones'][$zone->id])) {
                $zone->update([
                    'fare'      => $data['zones'][$zone->id]['fare'],
                    'is_active' => $request->boolean("zones.{$zone->id}.is_active"),
                ]);
            }
        }

        return back()->with('success', __('taxigo.saved_zones', ['hub' => $hub->name]));
    }

    public function storeNeighbourhood(Request $request, Zone $zone)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150',
                Rule::unique('taxigo_neighbourhoods', 'name')->where('zone_id', $zone->id)],
        ]);

        $zone->neighbourhoods()->create(['name' => trim($data['name'])]);

        return back()->with('success', __('taxigo.added_neighbourhood', ['name' => $data['name'], 'zone' => $zone->code]));
    }

    public function destroyNeighbourhood(Neighbourhood $neighbourhood)
    {
        // Keep it if rides already used it, so their history stays readable
        if (Ride::where('neighbourhood_id', $neighbourhood->id)->exists()) {
            $neighbourhood->update(['is_active' => false]);
        } else {
            $neighbourhood->delete();
        }

        return back()->with('success', __('taxigo.removed', ['name' => $neighbourhood->name]));
    }

    public function storeInterurban(Request $request, Hub $hub)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150',
                Rule::unique('taxigo_interurban_destinations', 'name')->where('hub_id', $hub->id)],
            'fare' => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);

        $hub->interurbanDestinations()->create($data + [
            'sort' => (int) $hub->interurbanDestinations()->max('sort') + 1,
        ]);

        return back()->with('success', __('taxigo.added_destination', ['name' => $data['name']]));
    }

    public function updateInterurban(Request $request, InterurbanDestination $destination)
    {
        $data = $request->validate([
            'fare' => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);

        $destination->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', __('taxigo.saved_destination', ['name' => $destination->name]));
    }

    public function destroyInterurban(InterurbanDestination $destination)
    {
        if (Ride::where('interurban_destination_id', $destination->id)->exists()) {
            $destination->update(['is_active' => false]);
        } else {
            $destination->delete();
        }

        return back()->with('success', __('taxigo.removed', ['name' => $destination->name]));
    }
}
