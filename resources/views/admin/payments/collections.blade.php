@extends('admin.layouts.app')

@section('title', 'Collections — Payments')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1">💳 Collections</h1>
        <p class="text-muted mb-0">All incoming MTN MoMo & Orange Money payments from clients</p>
    </div>
    <a href="{{ route('admin.payments.disbursements') }}" class="btn btn-outline-secondary">
        View Disbursements →
    </a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fs-4 fw-bold">{{ number_format($stats['total']) }}</div>
            <div class="text-muted small">Total Orders</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 border-success">
            <div class="fs-4 fw-bold text-success">{{ number_format($stats['paid']) }}</div>
            <div class="text-muted small">Paid</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 border-warning">
            <div class="fs-4 fw-bold text-warning">{{ number_format($stats['pending']) }}</div>
            <div class="text-muted small">Pending</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 border-danger">
            <div class="fs-4 fw-bold text-danger">{{ number_format($stats['failed']) }}</div>
            <div class="text-muted small">Failed</div>
        </div>
    </div>
</div>

{{-- Revenue banner --}}
<div class="alert alert-info mb-4">
    <strong>Total Revenue Collected:</strong>
    {{ number_format($stats['revenue']) }} XAF
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Order ref, booking ID, ref ID...">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="bus" @selected(request('type') === 'bus')>Bus</option>
                    <option value="car" @selected(request('type') === 'car')>Car</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Method</label>
                <select name="method" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="mtn_momo" @selected(request('method') === 'mtn_momo')>MTN MoMo</option>
                    <option value="orange_money" @selected(request('method') === 'orange_money')>Orange Money</option>
                    <option value="cash" @selected(request('method') === 'cash')>Cash</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-primary me-2">Filter</button>
                <a href="{{ route('admin.payments.collections') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order Ref</th>
                        <th>Booking</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>MTN Ref ID</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr class="{{ $order->payment_status === 'failed' ? 'table-danger' : ($order->payment_status === 'paid' ? '' : 'table-warning') }}">
                        <td>
                            <span class="font-monospace small">{{ $order->order_reference }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ strtoupper($order->booking_type) }} #{{ $order->booking_id }}</span>
                        </td>
                        <td>
                            @if($order->booking_type === 'bus')
                                <span class="badge bg-info text-dark">🚌 Bus</span>
                            @else
                                <span class="badge bg-purple text-white" style="background:#7a39bb">🚗 Car</span>
                            @endif
                        </td>
                        <td>
                            @if($order->payment_method === 'mtn_momo')
                                <span class="badge" style="background:#FFD700;color:#000">MTN MoMo</span>
                            @elseif($order->payment_method === 'orange_money')
                                <span class="badge" style="background:#FF6600;color:#fff">Orange Money</span>
                            @else
                                <span class="badge bg-secondary">{{ $order->payment_method }}</span>
                            @endif
                        </td>
                        <td class="fw-semibold">{{ number_format($order->total_amount) }} XAF</td>
                        <td>
                            @if($order->payment_status === 'paid')
                                <span class="badge bg-success">✅ Paid</span>
                            @elseif($order->payment_status === 'failed')
                                <span class="badge bg-danger">❌ Failed</span>
                            @else
                                <span class="badge bg-warning text-dark">⏳ Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($order->gateway_transaction_id)
                                <span class="font-monospace small text-muted" title="{{ $order->gateway_transaction_id }}">
                                    {{ substr($order->gateway_transaction_id, 0, 16) }}...
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $order->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.payments.show', $order->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No payment orders found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($orders->hasPages())
    <div class="card-footer">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection