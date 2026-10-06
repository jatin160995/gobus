<?php

namespace App\Models\TaxiGo;

use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Ride extends Model
{
    protected $table = 'taxigo_rides';

    public const ACTIVE_STATUSES = ['searching', 'assigned', 'en_route', 'arrived', 'on_board'];

    protected $fillable = [
        'ref', 'source', 'customer_user_id', 'created_by_user_id',
        'passenger_name', 'passenger_phone',
        'hub_id', 'trip_type', 'zone_id', 'neighbourhood_id', 'interurban_destination_id', 'vip_hours',
        'pickup_address', 'pickup_lat', 'pickup_lng',
        'dropoff_address', 'dropoff_lat', 'dropoff_lng',
        'is_scheduled', 'pickup_at', 'flight_number', 'notes',
        'base_fare', 'night_surcharge', 'total_fare', 'currency',
        'payment_method', 'payer_phone', 'payment_order_id', 'payment_status',
        'status', 'driver_id', 'vehicle_id',
        'paid_at', 'assigned_at', 'en_route_at', 'arrived_at', 'on_board_at',
        'completed_at', 'cancelled_at', 'cancelled_by', 'cancel_reason',
        'rating', 'rating_comment',
    ];

    protected $casts = [
        'is_scheduled'    => 'boolean',
        'pickup_at'       => 'datetime',
        'pickup_lat'      => 'decimal:7',
        'pickup_lng'      => 'decimal:7',
        'dropoff_lat'     => 'decimal:7',
        'dropoff_lng'     => 'decimal:7',
        'base_fare'       => 'decimal:2',
        'night_surcharge' => 'decimal:2',
        'total_fare'      => 'decimal:2',
        'paid_at'         => 'datetime',
        'assigned_at'     => 'datetime',
        'en_route_at'     => 'datetime',
        'arrived_at'      => 'datetime',
        'on_board_at'     => 'datetime',
        'completed_at'    => 'datetime',
        'cancelled_at'    => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function hub()
    {
        return $this->belongsTo(Hub::class, 'hub_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function neighbourhood()
    {
        return $this->belongsTo(Neighbourhood::class, 'neighbourhood_id');
    }

    public function interurbanDestination()
    {
        return $this->belongsTo(InterurbanDestination::class, 'interurban_destination_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function paymentOrder()
    {
        return $this->belongsTo(PaymentOrder::class, 'payment_order_id');
    }

    public function offers()
    {
        return $this->hasMany(RideOffer::class, 'ride_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(RideStatusLog::class, 'ride_id')->orderBy('id');
    }
}
