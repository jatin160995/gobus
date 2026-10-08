<?php

namespace App\Http\Requests\TaxiGo;

class StoreRideRequest extends QuoteRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            // The city-side point: the drop-off for trips from the airport,
            // the pickup for trips to the airport and for VIP hire
            'location'          => ['nullable', 'array', 'required_if:trip_type,city_to_airport,airport_to_city,vip_hourly'],
            'location.address'  => ['required_with:location', 'string', 'max:255'],
            'location.lat'      => ['nullable', 'numeric', 'between:-90,90', 'required_with:location.lng'],
            'location.lng'      => ['nullable', 'numeric', 'between:-180,180', 'required_with:location.lat'],
            'flight_number'     => ['nullable', 'string', 'max:20'],
            'passenger_name'    => ['nullable', 'string', 'max:150'],
            'passenger_phone'   => ['nullable', 'string', 'min:9', 'max:20'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ];
    }
}
