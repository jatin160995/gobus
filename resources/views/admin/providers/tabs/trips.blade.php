@extends('admin.providers.show')

@section('provider_tab')

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">{{ __('provider_show.trips_tab') }}</h5>
    </div>

    <div class="card-body">

        @if($trips->count())

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Route</th>
                        <th>Departure</th>
                        <th>Vehicle</th>
                        <th>Price</th>
                        <th>Seats</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($trips as $trip)
                        <tr>
                            <td>{{ $trip->id }}</td>

                            <td>
                                {{ $trip->route?->departureCity?->name ?? '-' }}
                                →
                                {{ $trip->route?->arrivalCity?->name ?? '-' }}
                            </td>

                            <td>
                                {{ $trip->departure_datetime
                                    ? \Carbon\Carbon::parse($trip->departure_datetime)->format('d M Y, h:i A')
                                    : '-' }}
                            </td>

                            <td>
                                {{ $trip->vehicle?->plate_number ?? '-' }}
                            </td>

                            <td>
                                {{ number_format($trip->price, 2) }}
                            </td>

                            <td>
                                {{ $trip->seats_available }} / {{ $trip->seats_total }}
                            </td>

                            <td>
                                <span class="">
                                    {{ strtoupper($trip->transport_type) }}
                                </span>
                            </td>

                            <td>
                                <span class="badge 
                                    bg-{{ 
                                        $trip->status === 'active' ? 'success' :
                                        ($trip->status === 'cancelled' ? 'danger' : 'secondary')
                                    }}">
                                    {{ ucfirst($trip->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @else
            <p class="text-muted mb-0">No trips found for this provider.</p>
        @endif

    </div>
</div>

@endsection
