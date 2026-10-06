<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Modules;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    /**
     * Read by the GO app at start-up to decide which services to show.
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'data'    => [
                'modules'       => Modules::toArray(),
                'support_phone' => Setting::getValue('taxigo_support_phone', ''),
                'currency'      => 'XAF',
            ],
        ]);
    }
}
