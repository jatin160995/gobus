@extends('admin.layouts.app')
@section('title', 'Retry Collection — ' . $order->order_reference)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.payments.show', $order->id) }}" class="btn btn-sm btn-outline-secondary">← Back to Order</a>
</div>

{{-- Warning banner --}}
<div class="alert alert-warning d-flex align-items-start gap-3 mb-4">
    <span class="fs-4">⚠️</span>
    <div>
        <strong>What this does:</strong><br>
        <ol class="mb-0 mt-1">
            <li>Marks the <strong>current failed order</strong> as <code>cancelled</code></li>
            <li>Creates a <strong>fresh PaymentOrder</strong> + split transactions for the same booking</li>
            <li>Sends a <strong>new MTN MoMo payment prompt</strong> to the client's phone</li>
            <li>Starts the polling job — status auto-updates when client approves</li>
        </ol>
        <div class="mt-2 text-danger"><strong>The client must approve on their phone within 2 minutes.</strong></div>
    </div>
</div>

{{-- Order Summary --}}
<div class="card mb-4">
    <div class="card-header"><strong>Order Being Retried</strong></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="small text-muted">Order Reference</div>
                <div class="font-monospace fw-semibold">{{ $order->order_reference }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Booking</div>
                <div class="fw-semibold">{{ strtoupper($order->booking_type) }} #{{ $order->booking_id }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Amount</div>
                <div class="fw-bold fs-5">{{ number_format($order->total_amount) }} XAF</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Current Status</div>
                @if($order->payment_status === 'failed')
                    <span class="badge bg-danger fs-6">❌ Failed</span>
                @elseif($order->payment_status === 'pending')
                    <span class="badge bg-warning text-dark fs-6">⏳ Pending / Timed Out</span>
                @else
                    <span class="badge bg-secondary fs-6">{{ $order->payment_status }}</span>
                @endif
            </div>
            @if($order->gateway_transaction_id)
            <div class="col-12">
                <div class="small text-muted">Old MTN Reference ID (being abandoned)</div>
                <div class="font-monospace small text-muted">{{ $order->gateway_transaction_id }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- MSISDN Form --}}
<div class="card" style="max-width: 520px;">
    <div class="card-header"><strong>📱 Enter Client MTN Number</strong></div>
    <div class="card-body">

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.payments.retry-collection', $order->id) }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">MTN MoMo Number <span class="text-danger">*</span></label>

                {{-- Pre-fill hint from user profile --}}
                @php
                    $prefillMsisdn = '';
                    
                @endphp

                <input
                    type="text"
                    name="msisdn"
                    value="{{ old('msisdn', $prefillMsisdn) }}"
                    class="form-control form-control-lg @error('msisdn') is-invalid @enderror"
                    placeholder="e.g. 237671234567 or 671234567"
                    autofocus
                    required
                >

                @error('msisdn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <div class="form-text">
                    Accepts: <code>237XXXXXXXXX</code> (full), <code>6XXXXXXXX</code> (9-digit), or <code>06XXXXXXXX</code> (with leading 0).
                    The system will auto-format to the correct MTN Cameroon format.
                </div>

                @if($user && $user->phone)
                    <div class="mt-2">
                        <small class="text-success">✅ Pre-filled from user profile ({{ $user->name }}). You can change it if needed.</small>
                    </div>
                @else
                    <div class="mt-2">
                        <small class="text-warning">⚠️ No phone found on user profile. Enter manually.</small>
                    </div>
                @endif
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-warning btn-lg fw-bold px-5"
                        onclick="return confirm('Send a fresh MTN MoMo payment prompt to this number?\n\nAmount: {{ number_format($order->total_amount) }} XAF\nNumber: ' + document.querySelector('[name=msisdn]').value)">
                    🔄 Send Payment Prompt
                </button>
                <a href="{{ route('admin.payments.show', $order->id) }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection