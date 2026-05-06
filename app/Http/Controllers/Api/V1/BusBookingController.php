<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusBookingRequest;
use App\Services\BusBookingService;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BusBookingController extends Controller
{
    protected BusBookingService $bookingService;

    public function __construct(BusBookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /*
    |--------------------------------------------------------------------------
    | POST /bus/bookings
    | Create a new bus booking
    |--------------------------------------------------------------------------
    */
    public function store(BusBookingRequest $request): JsonResponse
    {
        try {

            $result = $this->bookingService->createBooking(
                $request->validated(),
                auth()->id()
            );

            $booking    = $result['booking'];
            $priceData  = $result['price_data'];
            $order      = $result['payment_order'];

            return response()->json([
                'status'  => true,
                'message' => 'Booking created successfully',
                'data'    => [

                    // Booking Info
                    'booking_id'        => $booking->id,
                    'booking_ref'       => $booking->booking_ref,
                    'booking_status'    => $booking->booking_status,
                    'payment_status'    => $booking->payment_status,
                    'trip_type'         => $booking->trip_type,
                    'passenger_count'   => $booking->passenger_count,
                    'qr_code'           => $booking->qr_code,
                    'currency'          => 'XAF',

                    // Price Breakdown
                    'price_breakdown'   => [
                        'base_price'         => round($priceData['base_price'], 2),
                        'commission'         => round($priceData['commission'], 2),
                        'insurance'          => round($priceData['insurance'], 2),
                        'platform_commission'=> round($priceData['platform_commission'], 2),
                        'vat_percent'        => $priceData['vat_percent'],
                        'vat_amount'         => round($priceData['vat_amount'], 2),
                        'grand_total'        => round($priceData['grand_total'], 2),
                    ],

                    // Payment Order (needed to initiate payment next)
                    'payment_order'     => [
                        'order_reference'  => $order->order_reference,
                        'total_amount'     => round($order->total_amount, 2),
                        'payment_status'   => $order->payment_status,
                    ],

                    // Outbound Trip
                    'outbound'          => $result['outbound'],

                    // Return Trip (null for one_way)
                    'return'            => $result['return'],
                ],
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /bus/bookings
    | List all bus bookings for the authenticated user
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with([
                'trip.route.departureCity',
                'trip.route.arrivalCity',
                'bookingPassengers',
            ])
            ->where('user_id', auth()->id())
            ->whereHas('trip', fn($q) => $q->where('transport_type', 'bus'))
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data'   => $bookings,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /bus/bookings/{booking_ref}
    | Get full detail of a single booking
    |--------------------------------------------------------------------------
    */
    public function show(string $bookingRef): JsonResponse
    {
        $booking = Booking::with([
                'trip.route.departureCity',
                'trip.route.arrivalCity',
                'returnTrip.route.departureCity',
                'returnTrip.route.arrivalCity',
                'bookingPassengers',
                'paymentOrder',
            ])
            ->where('booking_ref', $bookingRef)
            ->where('user_id', auth()->id())
            ->first();

        if (!$booking) {
            return response()->json([
                'status'  => false,
                'message' => 'Booking not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $booking,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /bus/bookings/{booking_ref}/cancel
    | Cancel a booking and release seats
    |--------------------------------------------------------------------------
    */
    public function cancel(string $bookingRef): JsonResponse
    {
        $booking = Booking::with('bookingPassengers')
            ->where('booking_ref', $bookingRef)
            ->where('user_id', auth()->id())
            ->first();

        if (!$booking) {
            return response()->json([
                'status'  => false,
                'message' => 'Booking not found.',
            ], 404);
        }

        if ($booking->booking_status === 'cancelled') {
            return response()->json([
                'status'  => false,
                'message' => 'Booking is already cancelled.',
            ], 422);
        }

        if ($booking->booking_status === 'completed') {
            return response()->json([
                'status'  => false,
                'message' => 'Completed bookings cannot be cancelled.',
            ], 422);
        }

        try {

            \DB::transaction(function () use ($booking) {

                $passengerCount = $booking->bookingPassengers->count();

                /*
                |--------------------------------------------------------------
                | Release outbound seats
                |--------------------------------------------------------------
                */
                $outboundSeatIds = $booking->bookingPassengers
                    ->pluck('trip_seat_id')
                    ->filter()
                    ->toArray();

                if (!empty($outboundSeatIds)) {
                    \App\Models\TripSeat::whereIn('id', $outboundSeatIds)
                        ->update(['status' => 'available']);
                }

                /*
                |--------------------------------------------------------------
                | Release return seats (if round trip)
                |--------------------------------------------------------------
                */
                $returnSeatIds = $booking->bookingPassengers
                    ->pluck('return_trip_seat_id')
                    ->filter()
                    ->toArray();

                if (!empty($returnSeatIds)) {
                    \App\Models\TripSeat::whereIn('id', $returnSeatIds)
                        ->update(['status' => 'available']);
                }

                /*
                |--------------------------------------------------------------
                | Restore seats_available on schedule(s)
                |--------------------------------------------------------------
                */
                \App\Models\TripSchedule::where('id', $booking->schedule_id)
                    ->increment('seats_available', $passengerCount);

                if ($booking->return_schedule_id) {
                    \App\Models\TripSchedule::where('id', $booking->return_schedule_id)
                        ->increment('seats_available', $passengerCount);
                }

                /*
                |--------------------------------------------------------------
                | Update booking status
                |--------------------------------------------------------------
                */
                $booking->update([
                    'booking_status' => 'cancelled',
                    'payment_status' => $booking->payment_status === 'paid'
                        ? 'refunded'
                        : 'cancelled',
                ]);

                /*
                |--------------------------------------------------------------
                | Update payment order status
                |--------------------------------------------------------------
                */
                \App\Models\PaymentOrder::where('booking_id', $booking->id)
                    ->where('booking_type', 'bus')
                    ->update(['payment_status' => 'refunded']);

            });

            return response()->json([
                'status'  => true,
                'message' => 'Booking cancelled successfully.',
                'data'    => [
                    'booking_ref'    => $booking->booking_ref,
                    'booking_status' => 'cancelled',
                ],
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}