@php
    $provider = auth()->user()?->currentProvider();
    $providerType = $provider->type ?? null;
@endphp
<ul class="navbar-nav bg-primary-app sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('provider.dashboard') }}">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas 
                @if($providerType == 'car') fa-car
                @elseif($providerType == 'bus') fa-bus
                @elseif($providerType == 'air') fa-plane
                @elseif($providerType == 'train') fa-train
                @else fa-tachometer-alt
                @endif">
            </i>
        </div>
        <div class="sidebar-brand-text mx-3">
            {{ __('provider/provider_sidebar.brand') }}
        </div>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item active">
        <a class="nav-link" href="{{ route('provider.dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>{{ __('provider/provider_sidebar.dashboard') }}</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        {{ __('provider/provider_sidebar.management') }}
    </div>

    {{-- ================= BUS MODULE ================= --}}
    @if($providerType == 'bus')

        <!-- Trips -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTrips">
                <i class="fas fa-route"></i>
                <span>{{ __('provider/provider_sidebar.trips') }}</span>
            </a>
            <div id="collapseTrips" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{ route('provider.trips.index') }}">
                        {{ __('provider/provider_sidebar.all_trips') }}
                    </a>
                    <a class="collapse-item" href="{{ route('provider.trips.create') }}">
                        {{ __('provider/provider_sidebar.add_trip') }}
                    </a>
                </div>
            </div>
        </li>

        <!-- Routes -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseRoutes">
                <i class="fa fa-road"></i>
                <span>{{ __('provider/provider_sidebar.routes') }}</span>
            </a>
            <div id="collapseRoutes" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{ route('provider.routes.index') }}">
                        {{ __('provider/provider_sidebar.all_routes') }}
                    </a>
                    <a class="collapse-item" href="{{ route('provider.routes.create') }}">
                        {{ __('provider/provider_sidebar.add_routes') }}
                    </a>
                </div>
            </div>
        </li>

        <!-- Vehicles -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseVehicles">
                <i class="fas fa-bus"></i>
                <span>{{ __('provider/provider_sidebar.vehicles') }}</span>
            </a>
            <div id="collapseVehicles" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{ route('provider.vehicles.index') }}">
                        {{ __('provider/provider_sidebar.all_vehicles') }}
                    </a>
                    <a class="collapse-item" href="{{ route('provider.vehicles.create') }}">
                        {{ __('provider/provider_sidebar.add_vehicle') }}
                    </a>
                </div>
            </div>
        </li>

    @endif


    {{-- ================= CAR MODULE ================= --}}
    @if($providerType == 'car')

        <!-- Cars -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseCars">
                <i class="fas fa-car"></i>
                <span>{{ __('provider/provider_sidebar.cars') }}</span>
            </a>
            <div id="collapseCars" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{ route('provider.chauffeur.vehicles.index') }}">
                        {{ __('provider/provider_sidebar.all_cars') }}
                    </a>
                    <a class="collapse-item" href="{{ route('provider.chauffeur.vehicles.create') }}">
                        {{ __('provider/provider_sidebar.add_car') }}
                    </a>
                </div>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link"
            href="{{ route('provider.chauffeur.routes.index') }}">
                <i class="fas fa-route"></i>
                <span>Chauffeur Routes</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
            href="{{ route('provider.chauffeur.drivers.index') }}">
                <i class="fas fa-id-badge"></i>
                <span>Drivers</span>
            </a>
        </li>

    @endif


    {{-- ================= COMMON MODULES ================= --}}
    <!-- Car Bookings -->
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-calendar-check"></i>
                <span>{{ __('provider/provider_sidebar.bookings') }}</span>
            </a>
        </li>
    <!-- Payments -->
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="fas fa-credit-card"></i>
            <span>{{ __('provider/provider_sidebar.payments') }}</span>
        </a>
    </li>

    <!-- Reports -->
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="fas fa-chart-bar"></i>
            <span>{{ __('provider/provider_sidebar.reports') }}</span>
        </a>
    </li>

    <!-- Settings -->
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="fas fa-cogs"></i>
            <span>{{ __('provider/provider_sidebar.settings') }}</span>
        </a>
    </li>

    <!-- Language -->
    <li class="nav-item mt-3">
        <div class="sidebar-heading">
            {{ __('provider/provider_sidebar.language') }}
        </div>
        <div class="d-flex gap-2 px-3 mt-2">
            <a href="{{ route('lang.switch', 'en') }}"
               class="btn btn-sm w-50 {{ app()->getLocale() == 'en' ? 'btn-primary-app' : 'btn-outline-primary-app' }}">
                EN
            </a>
            <a href="{{ route('lang.switch', 'fr') }}"
               class="btn btn-sm w-50 {{ app()->getLocale() == 'fr' ? 'btn-primary-app' : 'btn-outline-primary-app' }}">
                FR
            </a>
        </div>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>