@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@section('content')

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Admin Dashboard</h1>
        <span class="text-muted">Platform overview</span>
    </div>

    <!-- Stats Row -->
    <div class="row">

        <!-- Total Users -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Users
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">12,450</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Users -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Users
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">9,820</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Providers -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Providers
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">128</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Providers -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Active Providers
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">96</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Second Row -->
    <div class="row">

        <!-- Total Cities -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-dark h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">
                                Total Cities
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">42</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-city fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Routes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-secondary h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                Total Routes
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">318</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-route fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Trips -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-danger h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Trips
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">5,240</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Bookings -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Bookings
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">18,970</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
