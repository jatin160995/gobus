<?php

return [
    // Fares, night hours and pickup times follow Cameroon time,
    // while the app itself stores timestamps in UTC.
    'timezone' => 'Africa/Douala',

    // Scheduled pickups must be at least this far ahead, and no further than max days
    'min_schedule_minutes' => 30,
    'max_schedule_days'    => 30,

    // A new payment request is refused while the previous one is younger than this
    'payment_retry_after_seconds' => 150,

    // Unpaid rides are expired after this many minutes
    'unpaid_expiry_minutes' => 60,
];
