<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\TripSchedule;
use App\Models\TripSeat;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Models\Offer;

class BusBookingService
{
    protected PriceCalculatorService $calculator;

    public function __construct(PriceCalculatorService $calculator)
    {
        $this->calculator = $calculator;
    }

    /*
    |--------------------------------------------------------------------------
    | Main entry point — called from BusBookingController
    |--------------------------------------------------------------------------
    */
    public function createBooking(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {

            /*
            |------------------------------------------------------------------
            | 1. Load & Validate Outbound Schedule
            |------------------------------------------------------------------
            */
            $schedule = TripSchedule::with('trip.route.departureCity', 'trip.route.arrivalCity')
                ->where('id', $data['schedule_id'])
                ->where('status', 'active')
                ->firstOrFail();

            $trip = $schedule->trip;

            if ($trip->transport_type !== 'bus') {
                throw new \Exception('Invalid trip type. Only bus trips are allowed.');
            }

            $passengerCount = count($data['passengers']);

            if ($schedule->seats_available < $passengerCount) {
                throw new \Exception('Not enough seats available on this schedule.');
            }

            /*
            |------------------------------------------------------------------
            | 2. Lock Outbound Seats (atomic — race condition safe)
            |------------------------------------------------------------------
            */
            $outboundSeats = $this->lockSeats(
                $schedule->id,
                $data['seat_numbers']
            );

            /*
            |------------------------------------------------------------------
            | 3. Load & Validate Return Schedule (round trip only)
            |------------------------------------------------------------------
            */
            $returnSchedule  = null;
            $returnTrip      = null;
            $returnSeats     = [];

            if ($data['trip_type'] === 'round_trip') {

                $returnSchedule = TripSchedule::with('trip')
                    ->where('id', $data['return_schedule_id'])
                    ->where('status', 'active')
                    ->firstOrFail();

                $returnTrip = $returnSchedule->trip;

                // Return route must be the reverse of the outbound route
                $outboundRoute = $trip->route;
                $returnRoute   = $returnTrip->route;

                if (
                    $returnRoute->departure_city_id !== $outboundRoute->arrival_city_id ||
                    $returnRoute->arrival_city_id   !== $outboundRoute->departure_city_id
                ) {
                    throw new \Exception('Return schedule route does not match the outbound route.');
                }

                if ($returnSchedule->seats_available < $passengerCount) {
                    throw new \Exception('Not enough seats available on the return schedule.');
                }

                $returnSeats = $this->lockSeats(
                    $returnSchedule->id,
                    $data['return_seat_numbers']
                );
            }

            /*
            |------------------------------------------------------------------
            | 4. Apply Offer / Discount (if offer_code provided)
            |------------------------------------------------------------------
            */
            $discount = 0;
            $offerId  = null;

            if (!empty($data['offer_code'])) {
                [$discount, $offerId] = $this->applyOffer(
                    $data['offer_code'],
                    $trip->provider_id
                );
            }

            /*
            |------------------------------------------------------------------
            | 5. Calculate Price
            |------------------------------------------------------------------
            */
            // Per-seat price × passenger count
            $perSeatPrice = $data['trip_type'] === 'round_trip'
                ? $trip->round_trip_price
                : $trip->price;

            $basePrice = $perSeatPrice * $passengerCount;
            $basePrice = max(0, $basePrice - $discount);

            $priceData = $this->calculator->calculateBusPrice($basePrice, $trip->provider_id);

            /*
            |------------------------------------------------------------------
            | 6. Generate Booking Reference & QR Code
            |------------------------------------------------------------------
            */
            $bookingRef = 'BUS-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            $qrCode     = base64_encode($bookingRef); // simple; replace with QR library if needed

            /*
            |------------------------------------------------------------------
            | 7. Create Booking Record
            |------------------------------------------------------------------
            */
            $booking = Booking::create([
                'user_id'              => $userId,
                'trip_id'              => $trip->id,
                'schedule_id'          => $schedule->id,
                'return_trip_id'       => $returnTrip?->id,
                'return_schedule_id'   => $returnSchedule?->id,
                'offer_id'             => $offerId,
                'booking_ref'          => $bookingRef,
                'trip_type'            => $data['trip_type'],
                'passenger_count'      => $passengerCount,
                'base_price'           => $priceData['base_price'],
                'commission_amount'    => $priceData['commission'],
                'platform_commission'  => $priceData['platform_commission'],
                'insurance_amount'     => $priceData['insurance'],
                'vat_amount'           => $priceData['vat_amount'],
                'total_amount'         => $priceData['grand_total'],
                'discount_amount'      => $discount,
                'currency'             => 'XAF',
                'qr_code'              => $qrCode,
                'booking_status'       => 'confirmed',
                'payment_status'       => 'pending',
                'notes'                => $data['notes'] ?? null,
            ]);

            /*
            |------------------------------------------------------------------
            | 8. Create Booking Passengers (seat assignments)
            |------------------------------------------------------------------
            */
            foreach ($data['passengers'] as $index => $passengerData) {

                $outboundSeat = $outboundSeats[$index];
                $returnSeat   = $returnSeats[$index] ?? null;

                BookingPassenger::create([
                    'booking_id'          => $booking->id,
                    'name'                => $passengerData['name'],
                    'phone'               => $passengerData['phone'] ?? null,
                    'gender'              => $passengerData['gender'] ?? 'male',
                    'id_number'           => $passengerData['id_number'] ?? null,
                    'seat_number'         => $outboundSeat->seat_number,
                    'trip_seat_id'        => $outboundSeat->id,
                    'return_seat_number'  => $returnSeat?->seat_number,
                    'return_trip_seat_id' => $returnSeat?->id,
                    'ticket_number'       => 'TKT-' . strtoupper(Str::random(8)),
                ]);
            }

            /*
            |------------------------------------------------------------------
            | 9. Decrement seats_available on schedule(s)
            |------------------------------------------------------------------
            */
            $schedule->decrement('seats_available', $passengerCount);

            if ($returnSchedule) {
                $returnSchedule->decrement('seats_available', $passengerCount);
            }

            /*
            |------------------------------------------------------------------
            | 10. Create Payment Order
            |------------------------------------------------------------------
            */
            $orderReference = 'PAY-' . now()->format('YmdHis') . rand(100, 999);

            $paymentOrder = PaymentOrder::create([
                'order_reference' => $orderReference,
                'booking_id'      => $booking->id,
                'booking_type'    => 'bus',
                'user_id'         => $userId,
                'provider_id'     => $trip->provider_id,
                'total_amount'    => $priceData['grand_total'],
                'currency'        => 'XAF',
                'payment_status'  => 'pending',
            ]);

            /*
            |------------------------------------------------------------------
            | 11. Create Split Payment Transactions
            |------------------------------------------------------------------
            */

            // Provider gets the base price
            PaymentTransaction::create([
                'transaction_reference' => 'TXN-' . uniqid(),
                'payment_order_id'      => $paymentOrder->id,
                'booking_id'            => $booking->id,
                'booking_type'          => 'bus',
                'transaction_type'      => 'provider_payout',
                'recipient_type'        => 'provider',
                'recipient_id'          => $trip->provider_id,
                'amount'                => $priceData['base_price'],
                'currency'              => 'XAF',
                'transaction_status'    => 'pending',
            ]);

            // Insurance gets flat fee
            PaymentTransaction::create([
                'transaction_reference' => 'TXN-' . uniqid(),
                'payment_order_id'      => $paymentOrder->id,
                'booking_id'            => $booking->id,
                'booking_type'          => 'bus',
                'transaction_type'      => 'insurance_payout',
                'recipient_type'        => 'insurance',
                'recipient_id'          => 1, // Activa Insurance (same as car booking)
                'amount'                => $priceData['insurance'],
                'currency'              => 'XAF',
                'transaction_status'    => 'pending',
            ]);

            /*
            |------------------------------------------------------------------
            | 12. Build & Return Response Data
            |------------------------------------------------------------------
            */
            return [
                'booking'       => $booking,
                'price_data'    => $priceData,
                'payment_order' => $paymentOrder,
                'outbound'      => [
                    'schedule_id'        => $schedule->id,
                    'departure_datetime' => $schedule->departure_datetime,
                    'from'               => $trip->route->departureCity->name ?? null,
                    'to'                 => $trip->route->arrivalCity->name ?? null,
                    'seats'              => collect($outboundSeats)->pluck('seat_number'),
                ],
                'return' => $returnSchedule ? [
                    'schedule_id'        => $returnSchedule->id,
                    'departure_datetime' => $returnSchedule->departure_datetime,
                    'from'               => $returnTrip->route->departureCity->name ?? null,
                    'to'                 => $returnTrip->route->arrivalCity->name ?? null,
                    'seats'              => collect($returnSeats)->pluck('seat_number'),
                ] : null,
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Lock seats atomically — throws if any seat is already taken
    |--------------------------------------------------------------------------
    */
    private function lockSeats(int $scheduleId, array $seatNumbers): array
    {
        // Pessimistic lock — prevents race conditions
        $seats = TripSeat::where('schedule_id', $scheduleId)
            ->whereIn('seat_number', $seatNumbers)
            ->where('status', 'available')
            ->lockForUpdate()
            ->get();

        if ($seats->count() !== count($seatNumbers)) {
            $takenSeats = array_diff(
                $seatNumbers,
                $seats->pluck('seat_number')->toArray()
            );
            throw new \Exception(
                'The following seats are no longer available: ' . implode(', ', $takenSeats)
            );
        }

        // Mark all as booked
        TripSeat::whereIn('id', $seats->pluck('id'))
            ->update(['status' => 'booked']);

        return $seats->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Apply offer code — returns [discount_amount, offer_id]
    |--------------------------------------------------------------------------
    */
    private function applyOffer(string $code, int $providerId): array
    {
        $offer = DB::table('offers')
            ->where('code', $code)
            ->where('provider_id', $providerId)
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            })
            ->first();

        if (!$offer) {
            return [0, null];
        }

        // Discount is calculated in the controller after base price is known
        // Here we just return the offer id; actual deduction happens on base price
        return [0, $offer->id]; // extend this if you want flat/percent applied here
    }
}