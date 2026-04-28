<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CitySearchService;
use Illuminate\Http\Request;

class CitySearchController extends Controller
{
    public function __construct(
        private CitySearchService $citySearchService
    ) {}

   public function search(Request $request)
{
    $request->validate([
        'q' => 'nullable|string'
    ]);

    $q = $request->input('q', '');

    return $this->citySearchService->search($q);
}
}
