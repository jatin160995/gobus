@extends('admin.layouts.app')

@section('title', 'Disbursements — Payments')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1">📤 Disbursements</h1>
        <p class="text-muted mb-0">Provider & insurance payouts via MTN MoMo</p>
    </div>
    <a href="{{ route('admin.payments.collections') }}" class="btn btn-outline-secondary">
        ← View Collections
    </a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fs-4 fw-bold">{{ number_format($stats['total']) }}</div>
            <div class="text-muted small">Total Payouts</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 border-success">
            <div class="fs-4 fw-bold text-success">{{ number_format($stats['success']) }}</div>
            <div class="text-muted small">Successful</div>
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

<div class="alert alert-info mb-4">
    <strong>Total Disbursed:</strong> {{ number_format($stats['disbursed']) }} XAF
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
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Transaction ref, booking ID...">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="success" @selected(request('status') === 'success')>Success</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="provider_payout" @selected(request('type') === 'provider_payout')>Provider</option>
                    <option value="insurance_payout" @selected(request('type') === 'insurance_payout')>Insurance</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-primary me-2">Filter</button>
                <a href="{{ route('admin.payments.disbursements') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
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
                        <th>Txn Ref</th>
                        <th>Booking</th>
                        <th>Type</th>
                        <th>Recipient</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                    <tr class="{{ $txn->transaction_status === 'failed' ? 'table-danger' : ($txn->transaction_status === 'success' ? '' : 'table-warning') }}">
                        <td><span class="font-monospace small">{{ Str::limit($txn->transaction_reference, 20) }}</span></td>
                        <td>
                            <span class="badge bg-secondary">{{ strtoupper($txn->booking_type ?? '') }} #{{ $txn->booking_id }}</span>
                        </td>
                        <td>
                            @if($txn->transaction_type === 'provider_payout')
                                <span class="badge bg-primary">🏢 Provider</span>
                            @else
                                <span class="badge bg-info text-dark">🛡️ Insurance</span>
                            @endif
                        </td>
                        <td class="small text-muted">
                            {{ ucfirst($txn->recipient_type) }} #{{ $txn->recipient_id }}
                        </td>
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
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $txn->payoutAttempts->count() }}</span>
                        </td>
                        <td class="small text-muted">{{ $txn->created_at->format('d M Y H:i') }}</td>
                        <td>
                            @if(in_array($txn->transaction_status, ['failed', 'pending']))
                                <form method="POST" action="{{ route('admin.payments.retry-disbursement', $txn->id) }}"
                                      onsubmit="return confirm('Retry this disbursement now?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning fw-semibold">
                                        🔄 Retry
                                    </button>
                                </form>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No disbursement transactions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer">
        {{ $transactions->links() }}
    </div>
    @endif
</div>
@endsection