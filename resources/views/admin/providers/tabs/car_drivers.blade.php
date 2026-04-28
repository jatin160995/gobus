@extends('admin.providers.show')

@section('provider_tab')

<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-id-card mr-2" style="color:#FF8C00;"></i> Drivers</h5>
        <span class="badge badge-secondary">{{ $drivers->count() }} total</span>
    </div>

    <div class="card-body p-0">
        @if($drivers->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background:#f8f9fc;">
                        <tr>
                            <th class="pl-4">#</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>License No.</th>
                            <th>License Expiry</th>
                            <th>Rating</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drivers as $d)
                            @php
                                $expiry = \Carbon\Carbon::parse($d->license_expiry);
                                $isExpired = $expiry->isPast();
                                $expiringSoon = !$isExpired && $expiry->diffInDays(now()) <= 90;
                            @endphp
                            <tr>
                                <td class="pl-4">{{ $d->id }}</td>
                                <td>
                                    <div class="font-weight-bold">{{ $d->name }}</div>
                                </td>
                                <td>{{ $d->phone }}</td>
                                <td><code>{{ $d->license_number }}</code></td>
                                <td>
                                    <span class="{{ $isExpired ? 'text-danger font-weight-bold' : ($expiringSoon ? 'text-warning font-weight-bold' : 'text-dark') }}">
                                        {{ $expiry->format('d M Y') }}
                                    </span>
                                    @if($isExpired)
                                        <span class="badge badge-danger ml-1">Expired</span>
                                    @elseif($expiringSoon)
                                        <span class="badge badge-warning ml-1">Expiring Soon</span>
                                    @endif
                                </td>
                                <td>
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star fa-sm" style="color:{{ $i <= round($d->rating) ? '#ffc107' : '#e0e0e0' }};"></i>
                                    @endfor
                                    <small class="text-muted ml-1">{{ number_format($d->rating, 1) }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $d->is_active ? 'success' : 'secondary' }}">
                                        {{ $d->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="fas fa-id-card fa-3x mb-3 d-block" style="color:#e0e0e0;"></i>
                No drivers found for this provider.
            </div>
        @endif
    </div>
</div>

@endsection