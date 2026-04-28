<?php

namespace App\Services;

use App\Models\City;

class CitySearchService
{
    public function search(string $query)
    {
        $query = trim($query);

        if (strlen($query) < 1) {
            $cities = City::query()
        ->orderBy('name')
        ->get(['id', 'name', 'city_code', 'country']);

    return response()->json([
        'status' => true,
        'data'   => $cities->map(function ($city) {
            return [
                'id'        => $city->id,
                'name'      => $city->name,
                'city_code' => $city->city_code,
                'country'   => $city->country,
                'label'     => "{$city->name} ({$city->city_code})"
            ];
        })
    ]);
        }

        $cities = City::query()
            ->where(function ($q) use ($query) {
                $q->where('city_code', 'LIKE', $query . '%')
                  ->orWhere('name', 'LIKE', $query . '%');
            })
            ->orderByRaw("
                CASE 
                    WHEN city_code LIKE ? THEN 1
                    WHEN name LIKE ? THEN 2
                    ELSE 3
                END
            ", [$query . '%', $query . '%'])
            ->limit(10)
            ->get(['id', 'name', 'city_code', 'country']);

        return response()->json([
            'status' => true,
            'data'   => $cities->map(function ($city) {
                return [
                    'id'        => $city->id,
                    'name'      => $city->name,
                    'city_code' => $city->city_code,
                    'country'   => $city->country,
                    'label'     => "{$city->name} ({$city->city_code})"
                ];
            })
        ]);
    }
}
