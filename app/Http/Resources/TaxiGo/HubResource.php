<?php

namespace App\Http\Resources\TaxiGo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HubResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'code'    => $this->code,
            'name'    => $this->name,
            'city'    => $this->city,
            'airport' => ['lat' => $this->lat ? (float) $this->lat : null, 'lng' => $this->lng ? (float) $this->lng : null],
            'night'   => [
                'start'     => substr($this->night_start, 0, 5),
                'end'       => substr($this->night_end, 0, 5),
                'surcharge' => (int) $this->night_surcharge,
            ],
            'vip' => ['hourly_rate' => (int) $this->vip_hourly_rate, 'min_hours' => (int) $this->vip_min_hours],
            'zones' => $this->zones->map(fn ($zone) => [
                'id'             => $zone->id,
                'code'           => $zone->code,
                'name'           => $zone->name,
                'fare'           => (int) $zone->fare,
                'neighbourhoods' => $zone->neighbourhoods->map(fn ($n) => [
                    'id' => $n->id, 'name' => $n->name,
                    'lat' => $n->lat ? (float) $n->lat : null, 'lng' => $n->lng ? (float) $n->lng : null,
                ])->values(),
            ])->values(),
            'interurban' => $this->interurbanDestinations->map(fn ($d) => [
                'id' => $d->id, 'name' => $d->name, 'fare' => (int) $d->fare,
            ])->values(),
        ];
    }
}
