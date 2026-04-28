@extends('admin.providers.show')

@section('provider_tab')

<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-calendar-check mr-2" style="color:#FF8C00;"></i> Bookings</h5>
        <span class="badge badge-secondary">{{ $bookings->count() }} total</span>
    </div>

    <div class="card-body p-0">
        @if($bookings->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:0.88rem;">
                    <thead style="background:#f8f9fc;">
                        <tr>
                            <th class="pl-4">Ref</th>
                            <th>Customer</th>
                            <th>Route</th>
                            <th>Trip Type</th>
                            <th>Pickup</th>
                            <th>Return</th>
                            <th>Base Price</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $b)
                            <tr>
                                <td class="pl-4">
                                    <code style="font-size:0.78rem;">{{ $b->booking_reference }}</code>
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $b->user->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $b->user->phone ?? '' }}</small>
                                </td>
                                <td>
                                    {{ $b->chauffeurRoute->fromCity->name ?? '-' }}
                                    <i class="fas fa-arrow-right fa-xs mx-1 text-muted"></i>
                                    {{ $b->chauffeurRoute->toCity->name ?? '-' }}
                                </td>
                                <td>
                                    @if($b->trip_type === 'round_trip')
                                        <span class="badge" style="background:#f0f9fb; color:#00829F; border:1px solid #b2dfdb; border-radius:20px; padding:3px 8px;">
                                            <i class="fas fa-sync-alt fa-xs mr-1"></i> Round Trip
                                        </span>
                                    @else
                                        <span class="badge" style="background:#fff8f0; color:#FF8C00; border:1px solid #FFD699; border-radius:20px; padding:3px 8px;">
                                            <i class="fas fa-arrow-right fa-xs mr-1"></i> One Way
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ \Carbon\Carbon::parse($b->pickup_datetime)->format('d M Y') }}</div>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($b->pickup_datetime)->format('h:i A') }}</small>
                                </td>
                                <td>
                                    @if($b->return_datetime)
                                        <div>{{ \Carbon\Carbon::parse($b->return_datetime)->format('d M Y') }}</div>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($b->return_datetime)->format('h:i A') }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ number_format($b->base_price, 0) }} <small class="text-muted">{{ $b->currency }}</small></td>
                                <td class="font-weight-bold">{{ number_format($b->total_price, 0) }} <small class="text-muted">{{ $b->currency }}</small></td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending'            => 'warning',
                                            'provider_confirmed' => 'info',
                                            'driver_assigned'    => 'primary',
                                            'ongoing'            => 'secondary',
                                            'completed'          => 'success',
                                            'cancelled'          => 'danger',
                                        ];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$b->status] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $b->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $b->payment_status === 'paid' ? 'success' : ($b->payment_status === 'failed' ? 'danger' : ($b->payment_status === 'refunded' ? 'info' : 'warning')) }}">
                                        {{ ucfirst($b->payment_status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="fas fa-calendar-times fa-3x mb-3 d-block" style="color:#e0e0e0;"></i>
                No bookings found for this provider.
            </div>
        @endif
    </div>
</div>

@endsection