@extends('admin.providers.show')

@section('provider_tab')

<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-route mr-2" style="color:#FF8C00;"></i> Routes & Pricing</h5>
        <span class="badge badge-secondary">{{ $routes->count() }} routes</span>
    </div>

    <div class="card-body p-0">
        @if($routes->count())

            @foreach($routes as $route)
                <div class="p-3" style="border-bottom:1px solid #f0f0f0;">

                    {{-- Route Header --}}
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center">
                            <span class="font-weight-bold" style="font-size:1rem;">
                                <i class="fas fa-map-marker-alt mr-1" style="color:#00829F;"></i>
                                {{ $route->fromCity->name ?? '-' }}
                            </span>
                            <span class="mx-2 text-muted">
                                <i class="fas fa-long-arrow-alt-right"></i>
                            </span>
                            <span class="font-weight-bold" style="font-size:1rem;">
                                <i class="fas fa-flag mr-1" style="color:#FF8C00;"></i>
                                {{ $route->toCity->name ?? '-' }}
                            </span>
                        </div>
                        <div class="text-muted" style="font-size:0.82rem;">
                            @if($route->distance_km)
                                <i class="fas fa-road mr-1"></i>{{ $route->distance_km }} km
                            @endif
                            @if($route->estimated_duration_minutes)
                                &nbsp;·&nbsp;<i class="fas fa-clock mr-1"></i>{{ $route->estimated_duration_minutes }} min
                            @endif
                            &nbsp;·&nbsp;
                            <span class="badge badge-{{ $route->is_active ? 'success' : 'secondary' }}">
                                {{ $route->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>

                    {{-- Pricing Table --}}
                    @if($route->prices->count())
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" style="font-size:0.85rem;">
                                <thead style="background:#f8f9fc;">
                                    <tr>
                                        <th>Category</th>
                                        <th>One Way</th>
                                        <th>Round Trip</th>
                                        <th>Per Day</th>
                                        <th>Currency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($route->prices as $price)
                                        <tr>
                                            <td>
                                                <span class="badge"
                                                    style="background:#fff8f0; color:#FF8C00; border:1px solid #FFD699; border-radius:20px; padding:3px 8px;">
                                                    {{ ucfirst($price->vehicle_category) }}
                                                </span>
                                            </td>
                                            <td class="font-weight-bold">{{ number_format($price->one_way_price, 0) }}</td>
                                            <td class="font-weight-bold">{{ number_format($price->round_trip_price, 0) }}</td>
                                            <td class="font-weight-bold">{{ number_format($price->per_day_price, 0) }}</td>
                                            <td><small class="text-muted">{{ $price->currency }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0" style="font-size:0.85rem;">No pricing set for this route.</p>
                    @endif

                </div>
            @endforeach

        @else
            <div class="text-center py-5 text-muted">
                <i class="fas fa-route fa-3x mb-3 d-block" style="color:#e0e0e0;"></i>
                No routes found for this provider.
            </div>
        @endif
    </div>
</div>

@endsection