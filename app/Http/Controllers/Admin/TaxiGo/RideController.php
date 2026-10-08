<?php

namespace App\Http\Controllers\Admin\TaxiGo;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\TaxiGo\Hub;
use App\Models\TaxiGo\Ride;
use Illuminate\Http\Request;

class RideController extends Controller
{
    public const STATUSES = ['pending_payment', 'searching', 'assigned', 'en_route', 'arrived', 'on_board', 'completed', 'cancelled', 'expired'];

    public function index(Request $request)
    {
        $rides = Ride::with(['hub', 'zone', 'neighbourhood', 'interurbanDestination', 'driver.user'])
            ->when($request->status === 'active', fn ($q) => $q->whereIn('status', Ride::ACTIVE_STATUSES))
            ->when($request->status && $request->status !== 'active', fn ($q) => $q->where('status', $request->status))
            ->when($request->hub, fn ($q, $hub) => $q->where('hub_id', $hub))
            ->when($request->source, fn ($q, $source) => $q->where('source', $source))
            ->when($request->date, fn ($q, $date) => $q->whereDate('pickup_at', $date))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('ref', 'like', "%{$s}%")->orWhere('passenger_name', 'like', "%{$s}%")->orWhere('passenger_phone', 'like', "%{$s}%")))
            ->latest()
            ->paginate(25)->withQueryString();

        $counts = Ride::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.taxigo.rides.index', [
            'rides'  => $rides,
            'counts' => $counts,
            'active' => $counts->only(Ride::ACTIVE_STATUSES)->sum(),
            'hubs'   => Hub::orderBy('sort')->get(),
        ]);
    }

    public function show(Ride $ride)
    {
        $ride->load(['hub', 'zone', 'neighbourhood', 'interurbanDestination', 'driver.user', 'vehicle', 'customer', 'createdBy', 'paymentOrder', 'statusLogs']);

        $payouts = PaymentTransaction::where('booking_type', 'taxigo')->where('booking_id', $ride->id)
            ->orderBy('id')->get();
        $beneficiaries = \App\Models\TaxiGo\SplitBeneficiary::pluck('name', 'id');

        return view('admin.taxigo.rides.show', compact('ride', 'payouts', 'beneficiaries'));
    }
}
