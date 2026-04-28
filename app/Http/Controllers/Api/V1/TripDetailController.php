<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TripDetailService;
use Illuminate\Http\Request;

class TripDetailController extends Controller
{
    public function __construct(
        private TripDetailService $tripDetailService
    ) {}

    public function show(Request $request, $scheduleId)
    {
        $request->validate([
            'trip_type'          => 'nullable|in:one_way,round_trip',
            'return_schedule_id' => 'required_if:trip_type,round_trip|nullable|integer|exists:trip_schedules,id',
            'adult'    => 'required|integer|min:1',
            'children'      => 'nullable|integer|min:0',
        ]);

        return $this->tripDetailService->getDetails(
            scheduleId: (int) $scheduleId,
            tripType: $request->input('trip_type', 'one_way'),
            returnScheduleId: $request->input('return_schedule_id'),
            adult: $request->input('adult'),
            children: $request->input('children'),
        );
    }
}