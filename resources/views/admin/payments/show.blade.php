@extends('admin.layouts.app')

@section('title', 'Payment Order — ' . $order->order_reference)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.payments.collections') }}" class="btn btn-sm btn-outline-secondary">← Back to Collections</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Order Header --}}
<div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Payment Order: <span class="font-monospace">{{ $order->order_reference }}</span></h5>
        <div class="d-flex align-items-center gap-2">
            @if($order->payment_status === 'paid')
                <span class="badge bg-success fs-6">✅ PAID</span>
            @elseif($order->payment_status === 'failed')
                <span class="badge bg-danger fs-6">❌ FAILED</span>
                <a href="{{ route('admin.payments.retry-collection-form', $order->id) }}"
                   class="btn btn-warning btn-sm fw-bold">
                    🔄 Retry Collection
                </a>
            @elseif($order->payment_status === 'pending')
                <span class="badge bg-warning text-dark fs-6">⏳ PENDING</span>
                <a href="{{ route('admin.payments.retry-collection-form', $order->id) }}"
                   class="btn btn-outline-warning btn-sm fw-bold">
                    🔄 Re-send Prompt
                </a>
            @elseif($order->payment_status === 'cancelled')
                <span class="badge bg-secondary fs-6">🚫 CANCELLED</span>
            @else
                <span class="badge bg-secondary fs-6">{{ $order->payment_status }}</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="small text-muted">Booking</div>
                <div class="fw-semibold">{{ strtoupper($order->booking_type) }} #{{ $order->booking_id }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Payment Method</div>
                <div class="fw-semibold">
                    @if($order->payment_method === 'mtn_momo')
                        <span style="color:#FFD700">●</span> MTN MoMo
                    @elseif($order->payment_method === 'orange_money')
                        <span style="color:#FF6600">●</span> Orange Money
                    @else
                        {{ $order->payment_method }}
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Total Amount</div>
                <div class="fw-bold fs-5">{{ number_format($order->total_amount) }} XAF</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Created</div>
                <div>{{ $order->created_at->format('d M Y H:i:s') }}</div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted">MTN Gateway Reference ID</div>
                <div class="font-monospace small">{{ $order->gateway_transaction_id ?? '—' }}</div>
            </div>
            @if($order->paid_at)
            <div class="col-md-3">
                <div class="small text-muted">Paid At</div>
                <div class="text-success">{{ $order->paid_at->format('d M Y H:i:s') }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Transactions / Disbursements --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">💸 Split Transactions</h6>
        @php
            $hasFailedDisbursements = $order->transactions
                ->whereIn('transaction_type', ['provider_payout', 'insurance_payout'])
                ->whereIn('transaction_status', ['failed', 'pending'])
                ->isNotEmpty();
        @endphp
        @if($hasFailedDisbursements && $order->payment_status === 'paid')
            <form method="POST" action="{{ route('admin.payments.retry-all-disbursements', $order->id) }}"
                  onsubmit="return confirm('Retry ALL failed/pending disbursements for this order?')">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm fw-semibold">
                    🔄 Retry All Failed Disbursements
                </button>
            </form>
        @endif
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>Recipient</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payout Attempts</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->transactions as $txn)
                <tr>
                    <td>
                        @if($txn->transaction_type === 'provider_payout')
                            <span class="badge bg-primary">🏢 Provider Payout</span>
                        @elseif($txn->transaction_type === 'insurance_payout')
                            <span class="badge bg-info text-dark">🛡️ Insurance Payout</span>
                        @elseif($txn->transaction_type === 'platform_commission')
                            <span class="badge bg-secondary">🏦 Platform Commission</span>
                        @else
                            <span class="badge bg-light text-dark">{{ $txn->transaction_type }}</span>
                        @endif
                    </td>
                    <td class="small">{{ ucfirst($txn->recipient_type) }} #{{ $txn->recipient_id ?? 'Platform' }}</td>
                    <td class="fw-semibold">{{ number_format($txn->amount) }} XAF</td>
                    <td>
                        @if($txn->transaction_status === 'success')
                            <span class="badge bg-success">✅ Success</span>
                        @elseif($txn->transaction_status === 'failed')
                            <span class="badge bg-danger">❌ Failed</span>
                        @else
                            <span class="badge bg-warning text-dark">⏳ Pending</span>
                        @endif
                    </td>
                    <td>
                        @if($txn->payoutAttempts->count() > 0)
                        <div class="small">
                            @foreach($txn->payoutAttempts->sortByDesc('attempted_at')->take(3) as $attempt)
                            <div class="d-flex align-items-center gap-1 mb-1">
                                @if($attempt->status === 'success')
                                    <span class="badge bg-success">✅</span>
                                @elseif($attempt->status === 'failed')
                                    <span class="badge bg-danger">❌</span>
                                @else
                                    <span class="badge bg-warning text-dark">⏳</span>
                                @endif
                                <span class="text-muted">#{{ $attempt->attempt_number }} — {{ $attempt->attempted_at?->format('d M H:i') }}</span>
                                @if($attempt->failure_reason)
                                    <span class="text-danger" title="{{ $attempt->failure_reason }}">⚠️</span>
                                @endif
                            </div>
                            @endforeach
                            @if($txn->payoutAttempts->count() > 3)
                                <span class="text-muted small">+{{ $txn->payoutAttempts->count() - 3 }} more</span>
                            @endif
                        </div>
                        @else
                            <span class="text-muted small">No attempts yet</span>
                        @endif
                    </td>
                                           <td class="d-flex gap-1">
                            <a href="{{ route('admin.payments.show', $order->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                            @if(in_array($order->payment_status, ['failed', 'pending']))
                                <a href="{{ route('admin.payments.retry-collection-form', $order->id) }}"
                                   class="btn btn-sm btn-warning fw-semibold" title="Send fresh payment prompt to client">
                                    🔄 Retry
                                </a>
                            @endif
                        </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection