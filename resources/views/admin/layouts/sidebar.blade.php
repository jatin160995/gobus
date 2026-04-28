<ul class="navbar-nav bg-secondary-app sidebar sidebar-dark accordion" id="accordionSidebar">

  <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('admin.dashboard') }}">
      <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-bus"></i></div>
      <div class="sidebar-brand-text mx-3">{{ __('sidebar.brand') }}</div>
  </a>

  <hr class="sidebar-divider my-0">

  <li class="nav-item active">
      <a class="nav-link" href="{{ route('admin.dashboard') }}">
          <i class="fas fa-fw fa-tachometer-alt"></i>
          <span>{{ __('sidebar.dashboard') }}</span>
      </a>
  </li>

  <hr class="sidebar-divider">
  <div class="sidebar-heading">{{ __('sidebar.management') }}</div>

  <!-- Users -->
  <li class="nav-item">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUsers">
          <i class="fas fa-user"></i><span>{{ __('sidebar.users') }}</span>
      </a>
      <div id="collapseUsers" class="collapse" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
              <a class="collapse-item" href="{{ route('admin.users.list') }}">{{ __('sidebar.all_users') }}</a>
          </div>
      </div>
  </li>

  <!-- Providers -->
  <li class="nav-item">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAgencies">
          <i class="fas fa-building"></i><span>{{ __('sidebar.providers') }}</span>
      </a>
      <div id="collapseAgencies" class="collapse" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
              <a class="collapse-item" href="{{ route('admin.providers.list') }}">{{ __('sidebar.all_providers') }}</a>
              <a class="collapse-item" href="{{ route('admin.providers.create') }}">{{ __('sidebar.add_provider') }}</a>
          </div>
      </div>
  </li>

  <!-- City -->
  <li class="nav-item">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseCities">
          <i class="fa-solid fa-city"></i><span>{{ __('sidebar.cities') }}</span>
      </a>
      <div id="collapseCities" class="collapse" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
              <a class="collapse-item" href="{{ route('admin.cities.index') }}">{{ __('sidebar.all_cities') }}</a>
              <a class="collapse-item" href="{{ route('admin.cities.create') }}">{{ __('sidebar.add_cities') }}</a>
          </div>
      </div>
  </li>
  <!-- Trips -->
  <li class="nav-item">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTrips">
          <i class="fas fa-bus"></i><span>{{ __('sidebar.trips') }}</span>
      </a>
      <div id="collapseTrips" class="collapse" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
              <a class="collapse-item" href="#">{{ __('sidebar.all_trips') }}</a>
              <a class="collapse-item" href="#">{{ __('sidebar.add_trip') }}</a>
          </div>
      </div>
  </li>

  <!-- Bookings -->
  <li class="nav-item">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBookings">
          <i class="fas fa-ticket-alt"></i><span>{{ __('sidebar.bookings') }}</span>
      </a>
      <div id="collapseBookings" class="collapse" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
              <a class="collapse-item" href="#">{{ __('sidebar.all_bookings') }}</a>
              <a class="collapse-item" href="#">{{ __('sidebar.cancelled_bookings') }}</a>
          </div>
      </div>
  </li>

  <!-- Payments -->
  <li class="nav-item">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePayments">
          <i class="fas fa-credit-card"></i><span>{{ __('sidebar.payments') }}</span>
      </a>
      <div id="collapsePayments" class="collapse" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
              <a class="collapse-item" href="#">{{ __('sidebar.transactions') }}</a>
              <a class="collapse-item" href="#">{{ __('sidebar.refunds') }}</a>
          </div>
      </div>
  </li>

  <!-- Reports -->
  <li class="nav-item">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReports">
          <i class="fas fa-chart-bar"></i><span>{{ __('sidebar.reports') }}</span>
      </a>
      <div id="collapseReports" class="collapse" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
              <a class="collapse-item" href="#">{{ __('sidebar.sales') }}</a>
              <a class="collapse-item" href="#">{{ __('sidebar.performance') }}</a>
          </div>
      </div>
  </li>

  <!-- Settings -->
  <li class="nav-item">
      <a class="nav-link" href="{{ route('settings.index') }}">
          <i class="fas fa-cogs"></i>
          <span>{{ __('sidebar.settings') }}</span>
      </a>
  </li>

  <!-- Language Switcher -->
  <li class="nav-item mt-3">
      <div class="sidebar-heading">{{ __('sidebar.language') }}</div>
      <div class="d-flex gap-2 px-3 mt-2">
          <a href="{{ route('lang.switch', 'en') }}"
             class="btn btn-sm w-50 {{ app()->getLocale() == 'en' ? 'btn-secondary-app' : 'btn-outline-secondary-app' }}">
              EN
          </a>
          <a href="{{ route('lang.switch', 'fr') }}"
             class="btn btn-sm w-50 {{ app()->getLocale() == 'fr' ? 'btn-secondary-app' : 'btn-outline-secondary-app' }}">
              FR
          </a>
      </div>
  </li>

  <hr class="sidebar-divider d-none d-md-block">
  <div class="text-center d-none d-md-inline">
      <button class="rounded-circle border-0" id="sidebarToggle"></button>
  </div>
</ul>
