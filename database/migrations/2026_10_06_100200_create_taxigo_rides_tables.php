<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rides, the offers sent to drivers for each ride, and a log of every
     * status change.
     */
    public function up(): void
    {
        Schema::create('taxigo_rides', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 20)->unique();
            $table->enum('source', ['app', 'desk'])->default('app');
            $table->foreignId('customer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('passenger_name', 150);
            $table->string('passenger_phone', 30);

            // What was booked
            $table->foreignId('hub_id')->constrained('taxigo_hubs');
            $table->enum('trip_type', ['airport_to_city', 'city_to_airport', 'interurban', 'vip_hourly']);
            $table->foreignId('zone_id')->nullable()->constrained('taxigo_zones')->nullOnDelete();
            $table->foreignId('neighbourhood_id')->nullable()->constrained('taxigo_neighbourhoods')->nullOnDelete();
            $table->foreignId('interurban_destination_id')->nullable()
                ->constrained('taxigo_interurban_destinations')->nullOnDelete();
            $table->unsignedTinyInteger('vip_hours')->nullable();

            $table->string('pickup_address')->nullable();
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->string('dropoff_address')->nullable();
            $table->decimal('dropoff_lat', 10, 7)->nullable();
            $table->decimal('dropoff_lng', 10, 7)->nullable();

            $table->boolean('is_scheduled')->default(false);
            $table->dateTime('pickup_at');
            $table->string('flight_number', 20)->nullable();
            $table->text('notes')->nullable();

            // Fare (snapshot at booking time)
            $table->decimal('base_fare', 12, 2);
            $table->decimal('night_surcharge', 12, 2)->default(0);
            $table->decimal('total_fare', 12, 2);
            $table->string('currency', 10)->default('XAF');

            // Payment
            $table->enum('payment_method', ['mtn_momo', 'orange_money'])->nullable();
            $table->string('payer_phone', 30)->nullable();
            $table->foreignId('payment_order_id')->nullable()->constrained('payment_orders')->nullOnDelete();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');

            // Lifecycle
            $table->enum('status', [
                'pending_payment', 'searching', 'assigned', 'en_route',
                'arrived', 'on_board', 'completed', 'cancelled', 'expired',
            ])->default('pending_payment');
            $table->foreignId('driver_id')->nullable()->constrained('taxigo_drivers')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('taxigo_vehicles')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('en_route_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('on_board_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->enum('cancelled_by', ['customer', 'driver', 'admin', 'system'])->nullable();
            $table->string('cancel_reason')->nullable();

            // V1.1
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('rating_comment', 500)->nullable();
            $table->timestamps();

            $table->index(['hub_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index(['customer_user_id', 'status']);
            $table->index('pickup_at');
        });

        Schema::create('taxigo_ride_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained('taxigo_rides')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('taxigo_drivers')->cascadeOnDelete();
            $table->enum('status', ['sent', 'accepted', 'declined', 'expired', 'cancelled'])->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['ride_id', 'driver_id']);
            $table->index(['driver_id', 'status']);
        });

        Schema::create('taxigo_ride_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained('taxigo_rides')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->enum('actor_type', ['customer', 'driver', 'admin', 'desk', 'system']);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxigo_ride_status_logs');
        Schema::dropIfExists('taxigo_ride_offers');
        Schema::dropIfExists('taxigo_rides');
    }
};
