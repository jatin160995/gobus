@extends('provider.layouts.app')

@section('title', 'Provider Dashboard')

@section('content')

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Provider Dashboard</h1>
        <span class="text-muted">Your business at a glance</span>
    </div>

    <!-- Stats Row -->
    <div class="row">

        <!-- Total Trips -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Trips
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">124</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-route fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Trips -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Trips
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">87</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-play-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Vehicles -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Vehicles
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">36</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trips Today -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Trips Today
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">14</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Second Row -->
    <div class="row">

        <!-- Total Bookings -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-secondary h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                Total Bookings
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">8,420</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today Bookings -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Today’s Bookings
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">126</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Revenue
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">$148,320</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payouts -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-danger h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Pending Payouts
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">$12,450</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
