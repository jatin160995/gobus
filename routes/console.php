<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// TaxiGo: rides never paid are expired after an hour (needs the scheduler cron on the server)
Artisan::command('taxigo:expire-unpaid', function () {
    $cutoff = now()->subMinutes((int) config('taxigo.unpaid_expiry_minutes', 60));

    $rides = \App\Models\TaxiGo\Ride::where('status', 'pending_payment')
        ->where('payment_status', '!=', 'paid')
        ->where('created_at', '<', $cutoff)
        ->get();

    foreach ($rides as $ride) {
        $ride->update(['status' => 'expired', 'cancelled_by' => 'system', 'cancel_reason' => 'Not paid in time']);
        \App\Models\TaxiGo\RideStatusLog::create(['ride_id' => $ride->id, 'from_status' => 'pending_payment', 'to_status' => 'expired', 'actor_type' => 'system']);
    }

    $this->info("Expired {$rides->count()} unpaid ride(s).");
})->purpose('Expire TaxiGo rides that were never paid');

\Illuminate\Support\Facades\Schedule::command('taxigo:expire-unpaid')->everyTenMinutes()->withoutOverlapping();
