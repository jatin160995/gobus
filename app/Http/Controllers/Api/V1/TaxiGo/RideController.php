<?php

namespace App\Http\Controllers\Api\V1\TaxiGo;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaxiGo\StoreRideRequest;
use App\Http\Resources\TaxiGo\RideResource;
use App\Models\TaxiGo\Ride;
use App\Models\TaxiGo\RideStatusLog;
use App\Services\TaxiGo\RideBookingService;
use App\Services\TaxiGo\RidePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class RideController extends Controller
{
    private const RELATIONS = ['hub', 'zone', 'neighbourhood', 'interurbanDestination', 'paymentOrder', 'driver.user', 'vehicle'];

    /**
     * The customer's rides.
     * GET /api/taxigo/rides?scope=upcoming|past
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['scope' => ['nullable', Rule::in(['upcoming', 'past'])]]);

        $rides = Ride::with(self::RELATIONS)
            ->where('customer_user_id', $request->user()->id)
            ->when($request->scope === 'upcoming', fn ($q) => $q
                ->where(fn ($q) => $q
                    ->whereIn('status', Ride::ACTIVE_STATUSES)
                    ->orWhere(fn ($q) => $q->where('status', 'pending_payment')
                        ->where('created_at', '>=', now()->subMinutes((int) config('taxigo.unpaid_expiry_minutes')))))
                ->orderBy('pickup_at'))
            ->when($request->scope === 'past', fn ($q) => $q
                ->whereIn('status', ['completed', 'cancelled', 'expired'])
                ->orderByDesc('pickup_at'))
            ->when(!$request->scope, fn ($q) => $q->latest())
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data'   => RideResource::collection($rides->items())->resolve(),
            'meta'   => ['current_page' => $rides->currentPage(), 'last_page' => $rides->lastPage(), 'total' => $rides->total()],
        ]);
    }

    /**
     * Book a ride. The fare is calculated here, never taken from the app.
     * POST /api/taxigo/rides
     */
    public function store(StoreRideRequest $request, RideBookingService $booking): JsonResponse
    {
        $user = $request->user();

        $ride = $booking->create(
            input: $request->validated(),
            source: 'app',
            customerUserId: $user->id,
            createdByUserId: null,
            passengerName: $request->passenger_name ?: $user->name,
            passengerPhone: $request->passenger_phone ?: (string) $user->phone,
        );

        return response()->json([
            'status'  => true,
            'message' => 'Ride booked. Pay to confirm it.',
            'data'    => (new RideResource($ride->load(self::RELATIONS)))->resolve(),
        ], 201);
    }

    /**
     * One ride. The app polls this while waiting for payment and during the ride.
     * GET /api/taxigo/rides/{ref}
     */
    public function show(Request $request, string $ref, RidePaymentService $payments): JsonResponse
    {
        $ride = $this->findOwn($request, $ref);
        $payments->syncOrangeStatus($ride);

        return response()->json(['status' => true, 'data' => (new RideResource($ride->fresh(self::RELATIONS)))->resolve()]);
    }

    /**
     * Send the payment request to the payer's phone.
     * POST /api/taxigo/rides/{ref}/pay  {method: mtn_momo|orange_money, phone}
     */
    public function pay(Request $request, string $ref, RidePaymentService $payments): JsonResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(['mtn_momo', 'orange_money'])],
            'phone'  => ['required', 'string', 'min:9', 'max:20'],
        ]);

        $ride = $this->findOwn($request, $ref);

        try {
            $order = $payments->initiate($ride, $data['method'], $data['phone']);
        } catch (RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status'  => true,
            'message' => $data['method'] === 'mtn_momo'
                ? 'Payment request sent. Approve it on your phone with your MTN MoMo PIN.'
                : 'Payment request sent. Approve it on your phone with your Orange Money PIN.',
            'data'    => [
                'order_reference' => $order->order_reference,
                'amount'          => (int) $order->total_amount,
                'currency'        => 'XAF',
                'poll_interval_s' => 5,
                'timeout_s'       => 120,
                'ride'            => (new RideResource($ride->fresh(self::RELATIONS)))->resolve(),
            ],
        ]);
    }

    /**
     * Cancel a ride that has not been paid. Paid cancellations follow rules
     * still to be agreed with the client (V1.1).
     * POST /api/taxigo/rides/{ref}/cancel
     */
    public function cancel(Request $request, string $ref): JsonResponse
    {
        $ride = $this->findOwn($request, $ref);

        if ($ride->status !== 'pending_payment' || $ride->payment_status === 'paid') {
            return response()->json(['status' => false, 'message' => 'This ride is paid. Contact TaxiGo support to cancel it.'], 422);
        }

        $ride->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => 'customer', 'cancel_reason' => $request->input('reason')]);
        RideStatusLog::create(['ride_id' => $ride->id, 'from_status' => 'pending_payment', 'to_status' => 'cancelled', 'actor_type' => 'customer', 'actor_id' => $request->user()->id]);

        return response()->json(['status' => true, 'message' => 'Ride cancelled.', 'data' => (new RideResource($ride->fresh(self::RELATIONS)))->resolve()]);
    }

    private function findOwn(Request $request, string $ref): Ride
    {
        return Ride::with(self::RELATIONS)
            ->where('ref', $ref)
            ->where('customer_user_id', $request->user()->id)
            ->firstOrFail();
    }
}
