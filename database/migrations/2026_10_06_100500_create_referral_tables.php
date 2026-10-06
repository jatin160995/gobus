<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Partner referral program: partners get a code, and earn a commission
     * for each new user who completes phone-verified sign-up with that code.
     */
    public function up(): void
    {
        Schema::create('referral_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 30)->unique();
            $table->string('code', 20)->unique();
            $table->enum('payout_channel', ['mtn', 'orange'])->default('mtn');
            $table->string('payout_msisdn', 30)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // V1.1 partner login
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_signups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('referral_partners');
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            // One commission per verified phone number, even if the account is later deleted
            $table->string('phone', 30)->unique();
            $table->decimal('commission_amount', 12, 2);       // snapshot of the setting at sign-up
            $table->enum('status', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['partner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_signups');
        Schema::dropIfExists('referral_partners');
    }
};
