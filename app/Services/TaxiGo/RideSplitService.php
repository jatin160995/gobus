<?php

namespace App\Services\TaxiGo;

use App\Jobs\ProcessMtnDisbursement;
use App\Jobs\TaxiGo\ProcessOrangePayout;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Models\TaxiGo\Driver;
use App\Models\TaxiGo\Ride;
use App\Models\TaxiGo\SplitBeneficiary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Turns a paid TaxiGo fare into six payout rows (Agreement §6) and sends them.
 *
 *   MTN / Orange beneficiaries  → paid instantly
 *   Bank beneficiaries          → 'manual_pending', marked paid from the Dashboard
 *   Driver share                → 'held' until the driver taps "Trip complete"
 */
class RideSplitService
{
    private const CHANNEL_METHOD = ['mtn' => 'mtn_momo', 'orange' => 'orange_money', 'bank' => 'bank_transfer'];

    public function __construct(private SplitCalculator $calculator) {}

    /**
     * Create the payout rows for a paid ride. Safe to call twice: the rows
     * are only created once per payment order.
     *
     * @return Collection<int, PaymentTransaction>
     */
    public function createForPaidRide(Ride $ride, PaymentOrder $order): Collection
    {
        $existing = PaymentTransaction::where('payment_order_id', $order->id)->get();
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $beneficiaries = SplitBeneficiary::active()->get();
        $amounts = $this->calculator->split(
            (int) round((float) $order->total_amount),
            $beneficiaries->pluck('percent', 'key')->all()
        );

        return $beneficiaries->map(function (SplitBeneficiary $b) use ($ride, $order, $amounts) {
            $isDriver = $b->is_driver_share;

            return PaymentTransaction::create([
                'transaction_reference' => "TXN-TG-{$ride->id}-{$b->key}",
                'payment_order_id'      => $order->id,
                'booking_id'            => $ride->id,
                'booking_type'          => 'taxigo',
                'transaction_type'      => $isDriver ? 'driver_payout' : 'beneficiary_payout',
                'recipient_type'        => $isDriver ? 'driver' : 'beneficiary',
                'recipient_id'          => $isDriver ? null : $b->id,
                'beneficiary_id'        => $b->id,
                'amount'                => $amounts[$b->key],
                'percent_snapshot'      => $b->percent,
                'currency'              => 'XAF',
                'payment_method'        => $isDriver ? null : self::CHANNEL_METHOD[$b->channel],
                'transaction_status'    => match (true) {
                    $isDriver              => 'held',
                    $b->channel === 'bank' => 'manual_pending',
                    default                => 'pending',
                },
            ]);
        });
    }

    /**
     * Send every pending MTN / Orange payout of an order. Runs after the
     * database transaction commits so the jobs see the rows.
     */
    public function dispatchPending(PaymentOrder $order): void
    {
        PaymentTransaction::where('payment_order_id', $order->id)
            ->where('booking_type', 'taxigo')
            ->where('transaction_status', 'pending')
            ->get()
            ->each(fn (PaymentTransaction $t) => $this->dispatchPayout($t));
    }

    /**
     * Release the held driver share to the driver who completed the ride.
     */
    public function releaseDriverShare(Ride $ride): ?PaymentTransaction
    {
        $driver = $ride->driver;
        $transaction = PaymentTransaction::where('booking_type', 'taxigo')
            ->where('booking_id', $ride->id)
            ->where('transaction_type', 'driver_payout')
            ->where('transaction_status', 'held')
            ->first();

        if (!$driver || !$transaction) {
            Log::warning('TaxiGo: no held driver share to release', ['ride' => $ride->ref]);
            return null;
        }

        $transaction->update([
            'recipient_id'       => $driver->id,
            'payment_method'     => self::CHANNEL_METHOD[$driver->payout_channel],
            'transaction_status' => 'pending',
        ]);

        $this->dispatchPayout($transaction);

        return $transaction;
    }

    public function dispatchPayout(PaymentTransaction $transaction): void
    {
        match ($transaction->payment_method) {
            'mtn_momo'     => ProcessMtnDisbursement::dispatch($transaction->id)->afterCommit(),
            'orange_money' => ProcessOrangePayout::dispatch($transaction->id)->afterCommit(),
            default        => Log::warning('TaxiGo: payout has no automatic channel', ['transaction' => $transaction->id]),
        };
    }

    /**
     * Mobile money number for a TaxiGo payout row (beneficiary or driver).
     */
    public static function recipientMsisdn(PaymentTransaction $transaction): ?string
    {
        return match ($transaction->recipient_type) {
            'beneficiary' => SplitBeneficiary::find($transaction->beneficiary_id)?->msisdn,
            'driver'      => Driver::find($transaction->recipient_id)?->payout_msisdn,
            default       => null,
        };
    }
}
