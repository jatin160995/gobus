@extends('admin.layouts.app')

@section('content')

<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-{{ $provider->type === 'car' ? 'car' : 'bus' }} mr-2" style="color:#FF8C00;"></i>
            {{ $provider->name }}
        </h1>
        <div>
            <a href="{{ route('admin.providers.edit', $provider->id) }}" class="btn btn-sm btn-primary-app shadow-sm mr-2">
                <i class="fas fa-edit fa-sm mr-1"></i> Edit Provider
            </a>
            <a href="{{ route('admin.providers.list') }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm mr-1"></i> All Providers
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Provider Info Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap">

                <div class="d-flex align-items-center">
                    <img src="{{ $provider->logo ? asset('storage/' . $provider->logo) : 'https://via.placeholder.com/90' }}"
                        width="90" height="90"
                        class="rounded shadow-sm"
                        style="object-fit:cover; border: 3px solid #e3e6f0; margin-right:20px;">

                    <div>
                        <h4 class="mb-1 font-weight-bold">{{ $provider->name }}</h4>

                        <div class="mb-2">
                            <span class="badge mr-1"
                                style="background-color:#FF8C00; color:#fff; font-size:0.75rem; padding:4px 10px; border-radius:20px;">
                                {{ strtoupper($provider->type) }}
                            </span>
                            <span class="badge badge-{{ $provider->status === 'active' ? 'success' : 'danger' }}"
                                style="font-size:0.75rem; padding:4px 10px; border-radius:20px;">
                                {{ ucfirst($provider->status) }}
                            </span>
                        </div>

                        <div class="text-gray-600" style="font-size:0.88rem; line-height:1.8;">
                            @if($provider->contact_person)
                                <span class="mr-3"><i class="fas fa-user fa-sm mr-1 text-muted"></i>{{ $provider->contact_person }}</span>
                            @endif
                            @if($provider->phone)
                                <span class="mr-3"><i class="fas fa-phone fa-sm mr-1 text-muted"></i>{{ $provider->phone }}</span>
                            @endif
                            @if($provider->email)
                                <span class="mr-3"><i class="fas fa-envelope fa-sm mr-1 text-muted"></i>{{ $provider->email }}</span>
                            @endif
                            @if($provider->address)
                                <span class="mr-3"><i class="fas fa-map-marker-alt fa-sm mr-1 text-muted"></i>{{ $provider->address }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Stats Row --}}
                <div class="d-flex mt-3 mt-md-0" style="gap:12px; flex-wrap:wrap;">
                    @if($provider->type === 'car')
                        <div class="text-center px-3 py-2 rounded" style="background:#fff8f0; border:1px solid #ffe0b2; min-width:90px;">
                            <div class="font-weight-bold" style="font-size:1.3rem; color:#FF8C00;">
                                {{ $provider->chauffeurVehicles()->count() }}
                            </div>
                            <div class="text-muted" style="font-size:0.75rem;">Vehicles</div>
                        </div>
                        <div class="text-center px-3 py-2 rounded" style="background:#f0f9fb; border:1px solid #b2dfdb; min-width:90px;">
                            <div class="font-weight-bold" style="font-size:1.3rem; color:#00829F;">
                                {{ $provider->chauffeurRoutes()->count() }}
                            </div>
                            <div class="text-muted" style="font-size:0.75rem;">Routes</div>
                        </div>
                        <div class="text-center px-3 py-2 rounded" style="background:#f5f0ff; border:1px solid #d1c4e9; min-width:90px;">
                            <div class="font-weight-bold" style="font-size:1.3rem; color:#6f42c1;">
                                {{ $provider->chauffeurDrivers()->count() }}
                            </div>
                            <div class="text-muted" style="font-size:0.75rem;">Drivers</div>
                        </div>
                        <div class="text-center px-3 py-2 rounded" style="background:#f0fff4; border:1px solid #b2dfdb; min-width:90px;">
                            <div class="font-weight-bold" style="font-size:1.3rem; color:#28a745;">
                                {{ $provider->chauffeurBookings()->count() }}
                            </div>
                            <div class="text-muted" style="font-size:0.75rem;">Bookings</div>
                        </div>
                    @else
                        <div class="text-center px-3 py-2 rounded" style="background:#fff8f0; border:1px solid #ffe0b2; min-width:90px;">
                            <div class="font-weight-bold" style="font-size:1.3rem; color:#FF8C00;">
                                {{ $provider->trips()->count() }}
                            </div>
                            <div class="text-muted" style="font-size:0.75rem;">Trips</div>
                        </div>
                        <div class="text-center px-3 py-2 rounded" style="background:#f0f9fb; border:1px solid #b2dfdb; min-width:90px;">
                            <div class="font-weight-bold" style="font-size:1.3rem; color:#00829F;">
                                {{ $provider->routes()->count() }}
                            </div>
                            <div class="text-muted" style="font-size:0.75rem;">Routes</div>
                        </div>
                        <div class="text-center px-3 py-2 rounded" style="background:#f5f5f5; border:1px solid #e0e0e0; min-width:90px;">
                            <div class="font-weight-bold" style="font-size:1.3rem; color:#555;">
                                {{ $provider->vehicles()->count() }}
                            </div>
                            <div class="text-muted" style="font-size:0.75rem;">Vehicles</div>
                        </div>
                    @endif

                    {{-- Commission always shown --}}
                    <div class="text-center px-3 py-2 rounded" style="background:#fffde7; border:1px solid #fff176; min-width:90px;">
                        <div class="font-weight-bold" style="font-size:1.3rem; color:#f9a825;">
                            {{ $provider->commission_rate }}%
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">Commission</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Orange MSISDN notice for car providers --}}
    @if($provider->type === 'car' && $provider->orange_msisdn)
        <div class="alert mb-4 shadow-sm" style="background:#fff3e0; border-left:4px solid #ff6600; border-radius:6px;">
            <i class="fas fa-mobile-alt mr-2" style="color:#ff6600;"></i>
            <strong>Orange Payout MSISDN:</strong> {{ $provider->orange_msisdn }}
        </div>
    @endif

    {{-- TABS --}}
    <ul class="nav nav-tabs mb-0" style="border-bottom: 2px solid #dee2e6;">

        {{-- Users tab — always shown --}}
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.providers.users') ? 'active font-weight-bold' : '' }}"
                href="{{ route('admin.providers.users', $provider->id) }}"
                style="{{ request()->routeIs('admin.providers.users') ? 'color:#FF8C00; border-bottom:2px solid #FF8C00;' : '' }}">
                <i class="fas fa-users fa-sm mr-1"></i> {{ __('provider_show.users_tab') }}
            </a>
        </li>

        @if($provider->type === 'car')

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.providers.car-vehicles') ? 'active font-weight-bold' : '' }}"
                    href="{{ route('admin.providers.car-vehicles', $provider->id) }}"
                    style="{{ request()->routeIs('admin.providers.car-vehicles') ? 'color:#FF8C00; border-bottom:2px solid #FF8C00;' : '' }}">
                    <i class="fas fa-car fa-sm mr-1"></i> Vehicles
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.providers.car-routes') ? 'active font-weight-bold' : '' }}"
                    href="{{ route('admin.providers.car-routes', $provider->id) }}"
                    style="{{ request()->routeIs('admin.providers.car-routes') ? 'color:#FF8C00; border-bottom:2px solid #FF8C00;' : '' }}">
                    <i class="fas fa-route fa-sm mr-1"></i> Routes & Pricing
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.providers.car-drivers') ? 'active font-weight-bold' : '' }}"
                    href="{{ route('admin.providers.car-drivers', $provider->id) }}"
                    style="{{ request()->routeIs('admin.providers.car-drivers') ? 'color:#FF8C00; border-bottom:2px solid #FF8C00;' : '' }}">
                    <i class="fas fa-id-card fa-sm mr-1"></i> Drivers
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.providers.car-bookings') ? 'active font-weight-bold' : '' }}"
                    href="{{ route('admin.providers.car-bookings', $provider->id) }}"
                    style="{{ request()->routeIs('admin.providers.car-bookings') ? 'color:#FF8C00; border-bottom:2px solid #FF8C00;' : '' }}">
                    <i class="fas fa-calendar-check fa-sm mr-1"></i> Bookings
                </a>
            </li>

        @else

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.providers.trips') ? 'active font-weight-bold' : '' }}"
                    href="{{ route('admin.providers.trips', $provider->id) }}"
                    style="{{ request()->routeIs('admin.providers.trips') ? 'color:#FF8C00; border-bottom:2px solid #FF8C00;' : '' }}">
                    <i class="fas fa-ticket-alt fa-sm mr-1"></i> {{ __('provider_show.trips_tab') }}
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.providers.vehicles') ? 'active font-weight-bold' : '' }}"
                    href="{{ route('admin.providers.vehicles', $provider->id) }}"
                    style="{{ request()->routeIs('admin.providers.vehicles') ? 'color:#FF8C00; border-bottom:2px solid #FF8C00;' : '' }}">
                    <i class="fas fa-bus fa-sm mr-1"></i> {{ __('provider_show.vehicles_tab') }}
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.providers.routes') ? 'active font-weight-bold' : '' }}"
                    href="{{ route('admin.providers.routes', $provider->id) }}"
                    style="{{ request()->routeIs('admin.providers.routes') ? 'color:#FF8C00; border-bottom:2px solid #FF8C00;' : '' }}">
                    <i class="fas fa-map-signs fa-sm mr-1"></i> {{ __('provider_show.routes_tab') }}
                </a>
            </li>

        @endif

    </ul>

    <div class="mt-4">
        @yield('provider_tab')
    </div>

    {{-- Delete --}}
    <div class="mt-4 text-right">
        <form action="{{ route('admin.providers.destroy', $provider->id) }}"
            method="POST"
            onsubmit="return confirm('{{ __('provider_show.delete_confirm') }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-trash mr-1"></i> {{ __('provider_show.delete_provider') }}
            </button>
        </form>
    </div>

</div>

@endsection