<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TaxiGo tariff: hubs (airports), fare zones, the neighbourhoods in each
     * zone, and flat interurban fares. Every value is editable from the Dashboard.
     */
    public function up(): void
    {
        Schema::create('taxigo_hubs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 10)->unique();             // DLA, NSI
            $table->string('city', 100);
            $table->decimal('lat', 10, 7)->nullable();         // airport pickup point
            $table->decimal('lng', 10, 7)->nullable();
            $table->time('night_start')->default('22:00:00');
            $table->time('night_end')->default('05:00:00');
            $table->decimal('night_surcharge', 12, 2)->default(0);
            $table->decimal('vip_hourly_rate', 12, 2)->default(0);
            $table->unsignedTinyInteger('vip_min_hours')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('taxigo_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->constrained('taxigo_hubs')->cascadeOnDelete();
            $table->string('code', 10);                        // A, B, C, D
            $table->string('name', 100);
            $table->decimal('fare', 12, 2);
            $table->json('polygon')->nullable();               // V1.1: auto zone from map pin
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['hub_id', 'code']);
        });

        Schema::create('taxigo_neighbourhoods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('taxigo_zones')->cascadeOnDelete();
            $table->string('name', 150);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['zone_id', 'is_active']);
        });

        Schema::create('taxigo_interurban_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->constrained('taxigo_hubs')->cascadeOnDelete();
            $table->string('name', 150);
            $table->decimal('fare', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxigo_interurban_destinations');
        Schema::dropIfExists('taxigo_neighbourhoods');
        Schema::dropIfExists('taxigo_zones');
        Schema::dropIfExists('taxigo_hubs');
    }
};
