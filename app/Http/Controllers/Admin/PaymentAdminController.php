<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Models\PayoutAttempt;
use App\Services\Payment\MtnDisbursementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentAdminController extends Controller
{
    public function __construct(
        private MtnDisbursementService $disbursementService
    ) {}

    /**
     * List all payment orders (collections) with filters
     */
    public function collections(Request $request)
    {
        $query = PaymentOrder::with(['transactions'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('booking_type', $request->type);
        }
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_reference', 'like', "%$s%")
                  ->orWhere('gateway_transaction_id', 'like', "%$s%")
                  ->orWhere('booking_id', 'like', "%$s%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        $stats = [
            'total'   => PaymentOrder::count(),
            'paid'    => PaymentOrder::where('payment_status', 'paid')->count(),
            'pending' => PaymentOrder::where('payment_status', 'pending')->count(),
            'failed'  => PaymentOrder::where('payment_status', 'failed')->count(),
            'revenue' => PaymentOrder::where('payment_status', 'paid')->sum('total_amount'),
        ];

        return view('admin.payments.collections', compact('orders', 'stats'));
    }

    /**
     * List all disbursement transactions with filters
     */
    public function disbursements(Request $request)
    {
        $query = PaymentTransaction::with(['payoutAttempts'])
            ->whereIn('transaction_type', ['provider_payout', 'insurance_payout'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('transaction_status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transaction_reference', 'like', "%$s%")
                  ->orWhere('booking_id', 'like', "%$s%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        $stats = [
            'total'    => PaymentTransaction::whereIn('transaction_type', ['provider_payout', 'insurance_payout'])->count(),
            'success'  => PaymentTransaction::whereIn('transaction_type', ['provider_payout', 'insurance_payout'])->where('transaction_status', 'success')->count(),
            'pending'  => PaymentTransaction::whereIn('transaction_type', ['provider_payout', 'insurance_payout'])->where('transaction_status', 'pending')->count(),
            'failed'   => PaymentTransaction::whereIn('transaction_type', ['provider_payout', 'insurance_payout'])->where('transaction_status', 'failed')->count(),
            'disbursed' => PaymentTransaction::whereIn('transaction_type', ['provider_payout', 'insurance_payout'])->where('transaction_status', 'success')->sum('amount'),
        ];

        return view('admin.payments.disbursements', compact('transactions', 'stats'));
    }

    /**
     * Show a single payment order detail
     */
    public function show(int $id)
    {
        $order = PaymentOrder::with(['transactions.payoutAttempts'])->findOrFail($id);
        return view('admin.payments.show', compact('order'));
    }

    /**
     * Retry a failed disbursement transaction
     */
    public function retryDisbursement(int $transactionId)
    {
        $transaction = PaymentTransaction::findOrFail($transactionId);

        if ($transaction->transaction_status === 'success') {
            return back()->with('error', 'This disbursement already succeeded.');
        }

        if (!in_array($transaction->transaction_type, ['provider_payout', 'insurance_payout'])) {
            return back()->with('error', 'Only payout transactions can be retried.');
        }

        // Mark as pending so the job processes it
        $transaction->update(['transaction_status' => 'pending']);

        \App\Jobs\ProcessMtnDisbursement::dispatch($transaction->id)
            ->delay(now()->addSeconds(2));

        Log::info('Admin: Disbursement retry dispatched', [
            'transaction_id' => $transactionId,
            'admin_user'     => auth()->id(),
        ]);

        return back()->with('success', 'Disbursement retry dispatched. Status will update in ~30 seconds.');
    }

    /**
     * Retry all failed disbursements for a payment order
     */
    public function retryAllDisbursements(int $orderId)
    {
        $order = PaymentOrder::findOrFail($orderId);

        if ($order->payment_status !== 'paid') {
            return back()->with('error', 'Cannot retry disbursements — collection payment is not in paid status.');
        }

        $failed = PaymentTransaction::where('payment_order_id', $orderId)
            ->whereIn('transaction_type', ['provider_payout', 'insurance_payout'])
            ->whereIn('transaction_status', ['failed', 'pending'])
            ->get();

        if ($failed->isEmpty()) {
            return back()->with('info', 'No failed disbursements found for this order.');
        }

        $count = 0;
        foreach ($failed as $idx => $txn) {
            $txn->update(['transaction_status' => 'pending']);
            \App\Jobs\ProcessMtnDisbursement::dispatch($txn->id)
                ->delay(now()->addSeconds(3 + ($idx * 5)));
            $count++;
        }

        Log::info('Admin: Bulk disbursement retry dispatched', [
            'order_id'   => $orderId,
            'count'      => $count,
            'admin_user' => auth()->id(),
        ]);

        return back()->with('success', "$count disbursement(s) queued for retry.");
    }

        /**
     * Show the retry collection form (to enter MSISDN if needed)
     */
    public function retryCollectionForm(int $orderId)
    {
        $order = PaymentOrder::with(['transactions'])->findOrFail($orderId);

        if ($order->payment_status === 'paid') {
            return back()->with('error', 'This collection already succeeded — no retry needed.');
        }

        // Try to find user's phone from the user model
        $user = \App\Models\User::find($order->user_id);

        return view('admin.payments.retry-collection', compact('order', 'user'));
    }

    /**
     * POST: Execute collection retry — cancel old order, fire fresh requestToPay
     */
    public function retryCollection(Request $request, int $orderId)
    {
        $request->validate([
            'msisdn' => 'required|string|min:9|max:15',
        ]);

        $order = PaymentOrder::with(['transactions'])->findOrFail($orderId);

        if ($order->payment_status === 'paid') {
            return back()->with('error', 'This collection already succeeded.');
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Cancel old order & its transactions
            $order->update(['payment_status' => 'cancelled']);
            \App\Models\PaymentTransaction::where('payment_order_id', $order->id)
                ->update(['transaction_status' => 'cancelled']);

            \Illuminate\Support\Facades\DB::commit();

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Failed to cancel old order: ' . $e->getMessage());
        }

        // 2. Fire fresh payment initiation using the existing MtnPaymentService
        $paymentService = app(\App\Services\Payment\MtnPaymentService::class);

        if ($order->booking_type === 'bus') {
            $result = $paymentService->initiateForBusBooking($order->booking_id, $request->msisdn);
        } else {
            $result = $paymentService->initiateForCarBooking($order->booking_id, $request->msisdn);
        }

        if (!$result['success']) {
            // Un-cancel the old order so admin can retry again
            $order->update(['payment_status' => 'failed']);
            \App\Models\PaymentTransaction::where('payment_order_id', $order->id)
                ->update(['transaction_status' => 'failed']);

            return back()->with('error', 'MTN request failed: ' . $result['message']);
        }

        // 3. Dispatch polling job (same as app flow)
        \App\Jobs\CheckMtnPaymentStatus::dispatch(
            $result['reference_id'],
            $result['order_id']
        )->delay(now()->addSeconds(5));

        Log::info('Admin: Collection retry dispatched', [
            'old_order_id'   => $orderId,
            'new_order_id'   => $result['order_id'],
            'reference_id'   => $result['reference_id'],
            'msisdn'         => $request->msisdn,
            'admin_user'     => auth()->id(),
        ]);

        return redirect()
            ->route('admin.payments.show', $result['order_id'])
            ->with('success', '✅ New payment prompt sent to ' . $request->msisdn . '. New order #' . $result['order_id'] . ' created. Polling job is running.');
    }
}