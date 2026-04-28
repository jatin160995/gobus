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
  @include('provider.layouts.sidebar')

  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
      <!-- Topbar -->
      <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
        <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
          <i class="fa fa-bars"></i>
        </button>

        <ul class="navbar-nav ml-auto">

          @php $provider = auth()->user()?->currentProvider(); @endphp

          <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                {{ Auth::user()->name }}
                @if($provider)
                  <span class="text-muted" style="font-size:0.75rem;">({{ $provider->name }})</span>
                @endif
              </span>
              @if($provider && $provider->logo)
                <img class="img-profile rounded-circle"
                     src="{{ asset('storage/' . $provider->logo) }}"
                     style="width:32px;height:32px;object-fit:cover;">
              @else
                <div class="rounded-circle bg-primary-app d-inline-flex align-items-center justify-content-center"
                     style="width:32px;height:32px;font-size:0.85rem;color:#fff;font-weight:700;vertical-align:middle;">
                  {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
              @endif
            </a>

            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                 aria-labelledby="userDropdown" style="min-width:240px;">

              <!-- Mini profile summary -->
              <div class="px-4 py-3 border-bottom">
                <div class="font-weight-bold text-gray-800" style="font-size:0.9rem;">
                  {{ Auth::user()->name }}
                </div>
                <div class="small text-muted">
                  {{ Auth::user()->email ?? Auth::user()->phone }}
                </div>
                @if($provider)
                  <div class="small text-muted mt-1">
                    <i class="fas fa-building mr-1"></i>{{ $provider->name }}
                    <span class="badge badge-info ml-1" style="font-size:0.65rem;">
                      {{ ucfirst($provider->type) }}
                    </span>
                  </div>
                @endif
              </div>

              <a class="dropdown-item py-2" href="{{ route('provider.profile.edit') }}">
                <i class="fas fa-user-edit fa-sm fa-fw mr-2 text-gray-400"></i>
                Edit Profile &amp; Agency
              </a>

              <a class="dropdown-item py-2" href="{{ route('provider.profile.edit') }}#change-password">
                <i class="fas fa-lock fa-sm fa-fw mr-2 text-gray-400"></i>
                Change Password
              </a>

              <div class="dropdown-divider"></div>

              <a class="dropdown-item py-2" href="{{ route('provider.profile.activity') }}">
                <i class="fas fa-history fa-sm fa-fw mr-2 text-gray-400"></i>
                Activity Log
              </a>

              <div class="dropdown-divider"></div>

              <a class="dropdown-item py-2 text-danger" href="#"
                 onclick="event.preventDefault();
                          if(confirm('Are you sure you want to log out?')) {
                              document.getElementById('logout-form').submit();
                          }">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i>
                Logout
              </a>

            </div>
          </li>
        </ul>
      </nav>
      <!-- End Topbar -->

      <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
      </form>