<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let the existing GO payment ledger record TaxiGo rides and the
     * six-way split. Existing enum values are kept unchanged.
     *
     * 'held'           driver share, released when the driver taps "Trip complete"
     * 'manual_pending' bank payout waiting to be marked paid from the Dashboard
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `payment_orders`
            MODIFY `booking_type` ENUM('bus','car','taxigo') NOT NULL DEFAULT 'bus',
            MODIFY `provider_id` BIGINT UNSIGNED NULL");

        DB::statement("ALTER TABLE `payment_transactions`
            MODIFY `booking_type` ENUM('bus','car','taxigo') NOT NULL DEFAULT 'bus',
            MODIFY `transaction_type` ENUM('user_payment','provider_payout','insurance_payout',
                'platform_commission','vat','beneficiary_payout','driver_payout') NOT NULL,
            MODIFY `recipient_type` ENUM('platform','provider','insurance','beneficiary','driver') NOT NULL,
            MODIFY `transaction_status` ENUM('pending','processing','success','failed',
                'held','manual_pending') DEFAULT 'pending'");

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('beneficiary_id')->nullable()->after('recipient_id');
            $table->decimal('percent_snapshot', 5, 2)->nullable()->after('amount');
            $table->index('beneficiary_id');
        });

        DB::statement("ALTER TABLE `payout_attempts`
            MODIFY `payout_type` ENUM('provider_payout','insurance_payout',
                'beneficiary_payout','driver_payout') NOT NULL");
    }

    /**
     * Fails if TaxiGo rows already use the new values; remove those first.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `payout_attempts`
            MODIFY `payout_type` ENUM('provider_payout','insurance_payout') NOT NULL");

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex(['beneficiary_id']);
            $table->dropColumn(['beneficiary_id', 'percent_snapshot']);
        });

        DB::statement("ALTER TABLE `payment_transactions`
            MODIFY `booking_type` ENUM('bus','car') NOT NULL DEFAULT 'bus',
            MODIFY `transaction_type` ENUM('user_payment','provider_payout','insurance_payout',
                'platform_commission','vat') NOT NULL,
            MODIFY `recipient_type` ENUM('platform','provider','insurance') NOT NULL,
            MODIFY `transaction_status` ENUM('pending','processing','success','failed') DEFAULT 'pending'");

        DB::statement("ALTER TABLE `payment_orders`
            MODIFY `booking_type` ENUM('bus','car') NOT NULL DEFAULT 'bus',
            MODIFY `provider_id` BIGINT UNSIGNED NOT NULL");
    }
};
