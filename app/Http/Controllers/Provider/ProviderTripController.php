<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\Vehicle;
use App\Models\TravelRoute as RouteModel;
use App\Models\City;
use App\Models\ActivityLog;
use App\Models\TripSchedule;
use App\Models\TripSeat;
use App\Traits\ProviderHelpers;
use DB;

class ProviderTripController extends Controller
{
    use ProviderHelpers;

    public function index()
    {
        $providerId = $this->providerId();

        $trips = Trip::where('provider_id', $providerId)
            ->with('vehicle','route')
            ->latest()
            ->paginate(15);

        return view('provider.trips.index', compact('trips'));
    }

    public function create()
    {
        $providerId = $this->providerId();

        $vehicles = Vehicle::where('provider_id', $providerId)->get();
        $routes = RouteModel::where('provider_id', $providerId)
            ->with(['departureCity','arrivalCity'])
            ->get();
        $cities = City::orderBy('name')->get();

        return view('provider.trips.create', compact('vehicles','routes','cities'));
    }

    /**
     * Store: create trip master, stops, schedules and seats
     */
    public function store(Request $request)
    {
       // dd($request->round_trip_price);
        $providerId = $this->providerId();

        $request->validate([
            'route_id' => 'required|exists:routes,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'price' => 'required|numeric',
            'round_trip_price' => 'nullable|numeric',
            'departure_time' => 'required', // HH:MM
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'recurrence' => 'required|in:none,daily,weekly',
            'weekdays' => 'nullable|array',
            'seats_total' => 'nullable|integer|min:1',
            'comfort_type' => 'nullable|in:standard,vip',
            'transport_type' => 'nullable|in:bus,train,air,car',
            'stops' => 'nullable|array',
            'child_price' => 'nullable|numeric|min:0'
            
        ]);

        // ensure vehicle belongs to provider
        $vehicle = Vehicle::where('id', $request->vehicle_id)
            ->where('provider_id', $providerId)
            ->first();

        if (!$vehicle) {
            return back()->withErrors(['vehicle_id' => 'Selected vehicle does not belong to your provider account.'])->withInput();
        }

        // seat count
        $seatsTotal = $request->seats_total ? (int)$request->seats_total : (int)$vehicle->capacity;

        $startDate = Carbon::parse($request->start_date);
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : $startDate;

        if ($request->recurrence === 'none') {
            $endDate = $startDate;
        }

        if ($request->recurrence === 'weekly' && empty($request->weekdays)) {
            return back()->withErrors(['weekdays' => 'For weekly recurrence you must select at least one weekday.'])->withInput();
        }

        DB::beginTransaction();
        try {
            // create trip master
            $trip = Trip::create([
                'provider_id' => $providerId,
                'route_id' => $request->route_id,
                'vehicle_id' => $request->vehicle_id,
                'departure_datetime' => Carbon::parse($startDate->toDateString() . ' ' . $request->departure_time),
                'price' => $request->price,
                'round_trip_price' => $request->round_trip_price ?? null,
                'child_price' => $request->child_price ?: null,
                'seats_total' => $seatsTotal,
                'seats_available' => $seatsTotal,
                'comfort_type' => $request->comfort_type ?? 'standard',
                'status' => 'active',
                'transport_type' => $request->transport_type ?? 'bus',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'recurrence' => $request->recurrence,
                'weekdays' => $request->weekdays ? array_values(array_map('intval', $request->weekdays)) : null
                
            ]);

            // stops — simple create
            if ($request->stops && is_array($request->stops)) {
                foreach ($request->stops as $idx => $s) {
                    if (empty($s['city_id'])) continue;
                    TripStop::create([
                        'trip_id' => $trip->id,
                        'city_id' => $s['city_id'],
                        'arrival_time' => $s['arrival_time'] ?? null,
                        'departure_time' => $s['departure_time'] ?? null,
                        'sequence' => $idx
                    ]);
                }
            }

            // generate schedules & seats
            $this->generateSchedulesAndSeatsForTrip($trip);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Created trip #' . $trip->id . ' with schedules and seats.'
            ]);

            DB::commit();

            return redirect()->route('provider.trips.index')->with('success', 'Trip created and schedules generated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create trip: ' . $e->getMessage()])->withInput();
        }
    }

    public function edit($id)
    {
        $providerId = $this->providerId();

        // Load trip owned by this provider
        $trip = Trip::where('id', $id)
            ->where('provider_id', $providerId)
            ->with('stops')
            ->firstOrFail();

        // Provider’s vehicles only
        $vehicles = Vehicle::where('provider_id', $providerId)->get();

        // Provider’s routes only
        $routes = RouteModel::where('provider_id', $providerId)
            ->with(['departureCity', 'arrivalCity'])
            ->get();

        // All cities for stops
        $cities = City::orderBy('name')->get();

        return view('provider.trips.edit', compact('trip','vehicles','routes','cities'));
    }

    /**
     * Update trip and stops. If dates changed and bookings exist, require confirmation (force_update)
     */
    public function update(Request $request, $id)
    {
        $providerId = $this->providerId();
        $trip = Trip::where('provider_id', $providerId)->findOrFail($id);

        $request->validate([
            'route_id' => 'required|exists:routes,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'price' => 'required|numeric',
            'departure_time' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'recurrence' => 'required|in:none,daily,weekly',
            'weekdays' => 'nullable|array',
            'seats_total' => 'nullable|integer|min:1',
            'comfort_type' => 'nullable|in:standard,vip',
            'transport_type' => 'nullable|in:bus,train,air,car',
            'stops' => 'nullable|array',
            'round_trip_price' => 'nullable|numeric|min:0',
            'child_price' => 'nullable|numeric|min:0'
        ]);

        $oldStart = $trip->start_date;
        $oldEnd = $trip->end_date;

        $newStart = Carbon::parse($request->start_date)->toDateString();
        $newEnd = $request->end_date ? Carbon::parse($request->end_date)->toDateString() : $newStart;

        if ($request->recurrence === 'none') {
            $newEnd = $newStart;
        }

        $datesChanged = ($oldStart != $newStart) || ($oldEnd != $newEnd);

        // If dates changed, check for bookings on existing schedules
        if ($datesChanged) {
            $scheduleIds = TripSchedule::where('trip_id', $trip->id)->pluck('id')->toArray();

            $hasBookings = false;
            if (!empty($scheduleIds)) {
                $hasBookings = DB::table('trip_seats')
                    ->whereIn('schedule_id', $scheduleIds)
                    ->whereIn('status', ['reserved','booked'])
                    ->exists();
            } else {
                $hasBookings = false;
            }

            // if bookings exist and not forced, return back with flag to show modal
            if ($hasBookings && !$request->has('force_update')) {
                // keep old inputs and show modal on edit page
                return back()
                    ->withInput()
                    ->with('booking_conflict', true)
                    ->with('booking_conflict_message', 'Some seats on existing schedules are reserved/booked. Confirm to proceed and regenerate schedules (this will remove existing schedules & seats).');
            }
        }

        DB::beginTransaction();
        try {
            // update trip master
            $seatsTotal = $request->seats_total ? (int)$request->seats_total : (($trip->seats_total) ? (int)$trip->seats_total : null);

            $trip->update([
                'route_id' => $request->route_id,
                'vehicle_id' => $request->vehicle_id,
                'departure_datetime' => Carbon::parse($newStart . ' ' . $request->departure_time),
                'price' => $request->price,
                'seats_total' => $seatsTotal,
                'seats_available' => $seatsTotal,
                'comfort_type' => $request->comfort_type ?? $trip->comfort_type,
                'start_date' => $newStart,
                'end_date' => $newEnd,
                'recurrence' => $request->recurrence,
                'weekdays' => $request->weekdays ? array_values(array_map('intval', $request->weekdays)) : null,
                'transport_type' => $request->transport_type ?? $trip->transport_type,
                'round_trip_price' => $request->round_trip_price ?: null,
                'child_price' => $request->child_price ?: null,
            ]);

            // Replace stops: delete all and recreate (method A)
            $trip->stops()->delete();
            if ($request->stops && is_array($request->stops)) {
                foreach ($request->stops as $idx => $s) {
                    if (empty($s['city_id'])) continue;
                    TripStop::create([
                        'trip_id' => $trip->id,
                        'city_id' => $s['city_id'],
                        'arrival_time' => $s['arrival_time'] ?? null,
                        'departure_time' => $s['departure_time'] ?? null,
                        'sequence' => $idx
                    ]);
                }
            }

            // If dates changed, delete old schedules & seats and regenerate
            if ($datesChanged) {
                // delete schedules (cascade will remove seats if FK configured)
                TripSchedule::where('trip_id', $trip->id)->delete();

                // regenerate
                $this->generateSchedulesAndSeatsForTrip($trip);
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Updated trip #' . $trip->id
            ]);

            DB::commit();

            return redirect()->route('provider.trips.index')->with('success', 'Trip updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update trip: '.$e->getMessage()])->withInput();
        }
    }

    /**
     * Generate schedules & seats for a given Trip instance.
     */
    protected function generateSchedulesAndSeatsForTrip(Trip $trip)
    {
        $seatsTotal = (int) $trip->seats_total;

        $startDate = Carbon::parse($trip->start_date);
        $endDate = $trip->recurrence === 'none'
            ? $startDate
            : Carbon::parse($trip->end_date);

        $baseTime = Carbon::parse($trip->departure_datetime)->format('H:i:s');

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {

            $create = false;

            // Recurrence Logic
            if ($trip->recurrence === 'none') {
                if ($date->isSameDay($startDate)) $create = true;
            } elseif ($trip->recurrence === 'daily') {
                $create = true;
            } elseif ($trip->recurrence === 'weekly') {
                $weekdays = $trip->weekdays ?? [];
                if (in_array($date->dayOfWeek, $weekdays)) {
                    $create = true;
                }
            }

            if (!$create) continue;

            // Build datetime using date + time
            $departureDt = Carbon::parse($date->toDateString() . ' ' . $baseTime);

            // Create schedule
            $schedule = TripSchedule::create([
                'trip_id'             => $trip->id,
                'date'                => $date->toDateString(),
                'departure_datetime'  => $departureDt,
                'seats_available'     => $seatsTotal,
                'status'              => 'active'
            ]);

            // Insert seats in batches
            $batch = [];
            for ($i = 1; $i <= $seatsTotal; $i++) {
                $batch[] = [
                    'schedule_id' => $schedule->id,
                    'seat_number' => (string) $i,
                    'status'      => 'available',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];

                // Batch insert every 200 seats
                if (count($batch) >= 200) {
                    DB::table('trip_seats')->insert($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                DB::table('trip_seats')->insert($batch);
            }
        }
    }

    public function destroy($id)
    {
        $providerId = $this->providerId();

        $trip = Trip::where('id', $id)->where('provider_id', $providerId)->firstOrFail();

        $trip->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Deleted trip #' . $id
        ]);

        return redirect()->route('provider.trips.index')->with('success', 'Trip deleted.');
    }
}
