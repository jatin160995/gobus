<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Models\ReferralPartner;
use App\Models\ReferralSignup;
use App\Models\Setting;
use App\Models\TaxiGo\Driver;
use App\Models\TaxiGo\Hub;
use App\Models\TaxiGo\Ride;
use App\Models\TaxiGo\SplitBeneficiary;
use App\Models\TaxiGo\Vehicle;
use App\Models\User;
use App\Models\UserDevice;
use App\Support\Modules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // TaxiGo milestones from the signed agreement (shown on the dashboard)
    private const STORE_SUBMISSION = '2026-11-05';
    private const PUBLIC_LAUNCH    = '2026-11-30';

    public function dashboard()
    {
        $now        = now();
        $monthStart = $now->copy()->startOfMonth();

        // Rides
        $rides = [
            'today'           => Ride::whereDate('created_at', $now->toDateString())->count(),
            'active'          => Ride::whereIn('status', Ride::ACTIVE_STATUSES)->count(),
            'upcoming'        => Ride::where('is_scheduled', true)->where('pickup_at', '>', $now)
                ->whereIn('status', ['searching', 'assigned'])->count(),
            'completed_month' => Ride::where('status', 'completed')->where('completed_at', '>=', $monthStart)->count(),
            'revenue_month'   => (float) Ride::where('payment_status', 'paid')->where('paid_at', '>=', $monthStart)->sum('total_fare'),
        ];

        // Fleet and customers
        $drivers = [
            'total'  => Driver::where('is_active', true)->count(),
            'online' => Driver::where('is_active', true)->where('is_online', true)->count(),
        ];
        $vehicles  = Vehicle::where('is_active', true)->count();
        $customers = [
            'total'     => User::where('role', 'user')->count(),
            'new_month' => User::where('role', 'user')->where('created_at', '>=', $monthStart)->count(),
        ];

        // Money in (all GO services) and money out
        $paid = PaymentOrder::where('payment_status', 'paid');
        $payments = [
            'today'   => (float) (clone $paid)->whereDate('paid_at', $now->toDateString())->sum('total_amount'),
            'month'   => (float) (clone $paid)->where('paid_at', '>=', $monthStart)->sum('total_amount'),
            'pending' => PaymentOrder::where('payment_status', 'pending')->count(),
            'failed'  => PaymentOrder::where('payment_status', 'failed')->count(),
        ];
        $payoutTypes = ['provider_payout', 'insurance_payout', 'beneficiary_payout', 'driver_payout'];
        $payouts = [
            'sent_month' => (float) PaymentTransaction::whereIn('transaction_type', $payoutTypes)
                ->where('transaction_status', 'success')->where('processed_at', '>=', $monthStart)->sum('amount'),
            'failed'     => PaymentTransaction::whereIn('transaction_type', $payoutTypes)->where('transaction_status', 'failed')->count(),
            'held'       => PaymentTransaction::where('transaction_status', 'held')->count(),
            'manual'     => PaymentTransaction::where('transaction_status', 'manual_pending')->count(),
        ];

        // Collections over the last 14 days
        $from  = $now->copy()->subDays(13)->startOfDay();
        $daily = PaymentOrder::where('payment_status', 'paid')->where('paid_at', '>=', $from)
            ->select(DB::raw('DATE(paid_at) as day'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('day')->pluck('total', 'day');
        $chart = collect(range(13, 0))->map(function ($daysAgo) use ($now, $daily) {
            $day = $now->copy()->subDays($daysAgo);
            return ['label' => $day->format('d M'), 'total' => (float) ($daily[$day->toDateString()] ?? 0)];
        });

        // Configuration
        $hubs          = Hub::with(['zones.neighbourhoods', 'interurbanDestinations'])->orderBy('sort')->get();
        $beneficiaries = SplitBeneficiary::orderBy('sort')->get();
        $referrals = [
            'partners' => ReferralPartner::where('is_active', true)->count(),
            'signups'  => ReferralSignup::count(),
            'unpaid'   => (float) ReferralSignup::where('status', 'unpaid')->sum('commission_amount'),
        ];
        $recentPayments = PaymentOrder::latest()->take(6)->get();

        $checklist = $this->launchChecklist($beneficiaries, $drivers['total'], $vehicles);

        $milestones = collect([
            ['label' => __('dashboard.store_submission'), 'date' => Carbon::parse(self::STORE_SUBMISSION)],
            ['label' => __('dashboard.public_launch'),    'date' => Carbon::parse(self::PUBLIC_LAUNCH)],
        ])->map(fn ($m) => $m + ['days' => (int) $now->copy()->startOfDay()->diffInDays($m['date'], false)]);

        return view('admin.dashboard', compact(
            'rides', 'drivers', 'vehicles', 'customers', 'payments', 'payouts', 'chart',
            'hubs', 'beneficiaries', 'referrals', 'recentPayments', 'checklist', 'milestones'
        ) + ['modules' => Modules::toArray()]);
    }

    /**
     * What still has to be set up before TaxiGo can take real rides.
     */
    private function launchChecklist($beneficiaries, int $driverCount, int $vehicleCount): array
    {
        $payoutRows = $beneficiaries->where('is_driver_share', false)->where('is_active', true);
        $configured = $payoutRows->filter(fn ($b) => $b->channel === 'bank' ? filled($b->bank_account_number) : filled($b->msisdn));
        $splitTotal = (float) $beneficiaries->where('is_active', true)->sum('percent');
        $commission = (float) Setting::getValue('taxigo_referral_commission_amount', 0);

        return [
            ['done' => abs($splitTotal - 100) < 0.01, 'title' => __('dashboard.check_split'), 'url' => route('admin.taxigo.split.index'),
             'note' => __('dashboard.check_split_note', ['total' => rtrim(rtrim(number_format($splitTotal, 2), '0'), '.')])],
            ['done' => $configured->count() === $payoutRows->count() && $payoutRows->isNotEmpty(), 'title' => __('dashboard.check_accounts'), 'url' => route('admin.taxigo.split.index'),
             'note' => __('dashboard.check_accounts_note', ['done' => $configured->count(), 'total' => $payoutRows->count()])],
            ['done' => $driverCount > 0, 'title' => __('dashboard.check_drivers'), 'url' => route('admin.taxigo.drivers.index'),
             'note' => trans_choice('dashboard.check_drivers_note', $driverCount, ['count' => $driverCount])],
            ['done' => $vehicleCount > 0, 'title' => __('dashboard.check_vehicles'), 'url' => route('admin.taxigo.vehicles.index'),
             'note' => trans_choice('dashboard.check_vehicles_note', $vehicleCount, ['count' => $vehicleCount])],
            ['done' => $commission > 0, 'title' => __('dashboard.check_commission'), 'url' => route('settings.index'),
             'note' => $commission > 0 ? number_format($commission) . ' XAF' : __('dashboard.not_set')],
            ['done' => filled(Setting::getValue('taxigo_support_phone')), 'title' => __('dashboard.check_support'), 'url' => route('settings.index'),
             'note' => Setting::getValue('taxigo_support_phone') ?: __('dashboard.not_set')],
        ];
    }
    public function usersList()
{
    $users = User::where('role', 'user')
        ->orderBy('id', 'DESC')
        ->paginate(20); // or get()

    return view('admin.users.index', compact('users'));
}
public function viewUser($id)
{
    $user = User::findOrFail($id);
    $devices = UserDevice::where('user_id',$id)->get();
    return view('admin.users.view', compact('user', 'devices'));
}

}
