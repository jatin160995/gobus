<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New roles: driver (driver app), desk (airport hostess), referral_partner (V1.1).
     * taxigo_hub_id scopes a desk user to one airport.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `users`
            MODIFY `role` ENUM('user','provider','admin','driver','desk','referral_partner') DEFAULT 'provider'");

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('taxigo_hub_id')->nullable()->after('role')
                ->constrained('taxigo_hubs')->nullOnDelete();
            $table->foreignId('referral_partner_id')->nullable()->after('taxigo_hub_id')
                ->constrained('referral_partners')->nullOnDelete();
        });
    }

    /**
     * Fails if users already have one of the new roles; change them first.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referral_partner_id');
            $table->dropConstrainedForeignId('taxigo_hub_id');
        });

        DB::statement("ALTER TABLE `users`
            MODIFY `role` ENUM('user','provider','admin') DEFAULT 'provider'");
    }
};
