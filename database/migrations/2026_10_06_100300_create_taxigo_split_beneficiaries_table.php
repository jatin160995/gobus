<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who receives a share of every TaxiGo fare. Percentages must total 100.
     * The driver row has no account of its own: each ride pays the assigned
     * driver's payout number.
     */
    public function up(): void
    {
        Schema::create('taxigo_split_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();               // bank_financing, driver, alo_fuel, ...
            $table->string('name', 150);
            $table->decimal('percent', 5, 2);
            $table->enum('channel', ['mtn', 'orange', 'bank']);
            $table->string('msisdn', 30)->nullable();
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('bank_account_number', 60)->nullable();
            $table->boolean('is_driver_share')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxigo_split_beneficiaries');
    }
};
