@extends('admin.providers.show')

@section('provider_tab')

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">{{ __('provider_show.routes_tab') }}</h5>
    </div>

    <div class="card-body">
        @if($routes->count())
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Distance</th>
                        <th>Duration (Mins)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routes as $r)
                        <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->departureCity->name ?? '-' }}</td>
                            <td>{{ $r->arrivalCity->name ?? '-' }}</td>
                            <td>{{ $r->distance_km ?? '-' }} km</td>
                            <td>{{ $r->duration_minutes ?? '-' }} km</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted mb-0">No routes found.</p>
        @endif
    </div>
</div>

@endsection
