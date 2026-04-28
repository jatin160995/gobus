<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trip;
use App\Models\TripSchedule;
use Carbon\Carbon;
use DB;

class GenerateTripSchedules extends Command
{
    protected $signature = 'trips:generate-schedules {days=60}';
    protected $description = 'Generate trip schedule occurrences for the next N days (default 60)';

    public function handle()
    {
        $days = (int) $this->argument('days');
        $windowStart = Carbon::today();
        $windowEnd = Carbon::today()->addDays($days);

        $this->info("Generating trip schedules from {$windowStart->toDateString()} to {$windowEnd->toDateString()}");

        $trips = Trip::where('status','active')->get();

        foreach ($trips as $trip) {
            $start = $trip->start_date ? Carbon::parse($trip->start_date) : $windowStart;
            $start = $start->greaterThan($windowStart) ? $start : $windowStart;
            $end = $trip->end_date ? Carbon::parse($trip->end_date) : $windowEnd;
            $end = $end->lessThan($windowEnd) ? $end : $windowEnd;

            $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->copy()->addDay());

            foreach ($period as $date) {
                $shouldCreate = false;

                if ($trip->recurrence === 'none') {
                    // Only create if date matches trip start_date or if there's no start_date and equals today
                    if ($trip->start_date && $date->isSameDay($trip->start_date)) $shouldCreate = true;
                } elseif ($trip->recurrence === 'daily') {
                    $shouldCreate = true;
                } elseif ($trip->recurrence === 'weekly') {
                    $weekdays = $trip->weekdays ?? [];
                    if (in_array($date->dayOfWeek, $weekdays)) $shouldCreate = true;
                }

                if ($shouldCreate) {
                    // compute departure_datetime: combine date with time from trip->departure_datetime
                    $timePart = $trip->departure_datetime ? Carbon::parse($trip->departure_datetime)->format('H:i:s') : '00:00:00';
                    $departureDt = Carbon::parse($date->toDateString().' '.$timePart);

                    // create or skip if exists
                    TripSchedule::firstOrCreate(
                        ['trip_id' => $trip->id, 'date' => $date->toDateString()],
                        [
                            'departure_datetime' => $departureDt,
                            'seats_available' => $trip->seats_total ?? 0,
                            'status' => 'active'
                        ]
                    );
                }
            }
        }

        $this->info('Done');
        return 0;
    }
}
