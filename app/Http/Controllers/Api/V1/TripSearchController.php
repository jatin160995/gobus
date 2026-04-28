<?php 
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TripSearchService;
use Illuminate\Http\Request;

class TripSearchController extends Controller
{
   public function search(Request $request)
    {
       $validated = $request->validate([
            'from'          => 'required|string',
            'to'            => 'required|string',
            'date'          => 'required|date',
            'passengers'    => 'required|integer|min:1',
            'children'      => 'nullable|integer|min:0',
            'trip_type'     => 'nullable|in:one_way,round_trip',
            'return_date'   => 'required_if:trip_type,round_trip|nullable|date|after:date',
        ]);

        return app(TripSearchService::class)->search($validated);
    }
}
