<?php

namespace App\Http\Controllers\Api\V1\TaxiGo;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaxiGo\HubResource;
use App\Models\TaxiGo\Hub;
use Illuminate\Http\JsonResponse;

class HubController extends Controller
{
    /**
     * Airports with their zones, neighbourhoods and interurban fares.
     * GET /api/taxigo/hubs
     */
    public function index(): JsonResponse
    {
        $hubs = Hub::active()->orderBy('sort')
            ->with([
                'zones' => fn ($q) => $q->where('is_active', true)->orderBy('sort'),
                'zones.neighbourhoods' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
                'interurbanDestinations' => fn ($q) => $q->where('is_active', true)->orderBy('sort'),
            ])
            ->get();

        return response()->json(['status' => true, 'data' => HubResource::collection($hubs)->resolve()]);
    }
}
