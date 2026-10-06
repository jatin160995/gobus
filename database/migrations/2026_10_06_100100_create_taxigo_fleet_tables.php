<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TaxiGo drivers (employees, login via users with role = driver) and the
     * company-owned vehicles they drive.
     */
    public function up(): void
    {
        Schema::create('taxigo_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('hub_id')->constrained('taxigo_hubs');
            $table->string('photo')->nullable();
            $table->string('licence_number', 50)->nullable();
            $table->enum('payout_channel', ['mtn', 'orange'])->default('mtn');
            $table->string('payout_msisdn', 30)->nullable();
            $table->boolean('is_active')->default(true);

            // Live state, updated by the driver app while online
            $table->boolean('is_online')->default(false);
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lng', 10, 7)->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->timestamp('went_online_at')->nullable();
            $table->timestamps();

            $table->index(['hub_id', 'is_active', 'is_online']);
        });

        Schema::create('taxigo_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('model', 100);                      // BAIC X35
            $table->string('plate', 20)->unique();
            $table->string('color', 50)->nullable();
            $table->unsignedTinyInteger('seats')->default(4);
            $table->string('photo')->nullable();
            $table->foreignId('driver_id')->nullable()->unique()
                ->constrained('taxigo_drivers')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxigo_vehicles');
        Schema::dropIfExists('taxigo_drivers');
    }
};
