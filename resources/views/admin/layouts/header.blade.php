<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>GoBus Admin - @yield('title', 'Dashboard')</title>

  <link href="{{ asset('admin/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="{{ asset('admin/css/sb-admin-2.min.css') }}" rel="stylesheet">
  <link href="{{ asset('admin/css/style.css') }}" rel="stylesheet">
</head>
<body id="page-top">

<div id="wrapper">
  @include('admin.layouts.sidebar')

  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
      <!-- Topbar -->
      <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
        <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
          <i class="fa fa-bars"></i>
        </button>

        <ul class="navbar-nav ml-auto">
          <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="mr-2 d-none d-lg-inline text-gray-600 small">{{Auth::user()->name;}}</span>
              <img class="img-profile rounded-circle" src="{{ asset('admin/img/undraw_profile.svg') }}">
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                 aria-labelledby="userDropdown">
              <a class="dropdown-item" href="#"><i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>Profile</a>
              <a class="dropdown-item" href="#"><i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>Settings</a>
              <div class="dropdown-divider"></div>
              {{-- <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout
              </a> --}}
              <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
              <a href="#" class="nav-link text-danger"
                onclick="event.preventDefault();
                            if(confirm('Are you sure you want to log out?')) {
                                document.getElementById('logout-form').submit();
                            }">
                <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
          </li>
        </ul>
      </nav>
      <!-- End Topbar -->
