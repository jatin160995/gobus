@php
  use App\Support\Modules;

  // Payouts that need a retry, shown as a count next to the menu item
  $failedPayouts = \App\Models\PaymentTransaction::where('transaction_status', 'failed')->count();
@endphp

<aside class="go-sidebar" id="accordionSidebar">
  <a class="go-brand" href="{{ route('admin.dashboard') }}">
    <img src="{{ asset('admin/img/go-logo.png') }}" alt="GO">
    <span>
      <span class="go-brand-name d-block">GO Admin</span>
      <span class="go-brand-sub d-block">TaxiGo · ALO Technologies</span>
    </span>
  </a>

  <nav class="go-nav">
    <div class="go-nav-label">{{ __('sidebar.overview') }}</div>
    <a class="go-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
      <i class="fas fa-th-large"></i><span>{{ __('sidebar.dashboard') }}</span>
    </a>

    <div class="go-nav-label">{{ __('sidebar.operations') }}</div>
    <a class="go-nav-link {{ request()->routeIs('admin.users.*', 'admin.user.*') ? 'active' : '' }}" href="{{ route('admin.users.list') }}">
      <i class="fas fa-user-friends"></i><span>{{ __('sidebar.customers') }}</span>
    </a>

    @if (Modules::anyLegacy())
      <a class="go-nav-link {{ request()->routeIs('admin.providers.*', 'admin.provider-users.*') ? 'active' : '' }}" href="{{ route('admin.providers.list') }}">
        <i class="fas fa-building"></i><span>{{ __('sidebar.providers') }}</span>
      </a>
      <a class="go-nav-link {{ request()->routeIs('admin.cities.*') ? 'active' : '' }}" href="{{ route('admin.cities.index') }}">
        <i class="fas fa-city"></i><span>{{ __('sidebar.cities') }}</span>
      </a>
    @endif

    <div class="go-nav-label">{{ __('sidebar.finance') }}</div>
    <a class="go-nav-link {{ request()->routeIs('admin.payments.collections') ? 'active' : '' }}" href="{{ route('admin.payments.collections') }}">
      <i class="fas fa-arrow-down"></i><span>{{ __('sidebar.collections') }}</span>
    </a>
    <a class="go-nav-link {{ request()->routeIs('admin.payments.disbursements') ? 'active' : '' }}" href="{{ route('admin.payments.disbursements') }}">
      <i class="fas fa-arrow-up"></i><span>{{ __('sidebar.payouts') }}</span>
      @if ($failedPayouts > 0)
        <span class="go-nav-tag is-alert" title="{{ __('sidebar.failed_payouts') }}">{{ $failedPayouts }}</span>
      @endif
    </a>

    <div class="go-nav-label">{{ __('sidebar.system') }}</div>
    <a class="go-nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
      <i class="fas fa-sliders-h"></i><span>{{ __('sidebar.settings') }}</span>
    </a>
  </nav>

  <div class="go-sidebar-foot">
    <div class="go-lang" role="group" aria-label="{{ __('sidebar.language') }}">
      <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() == 'en' ? 'active' : '' }}">EN</a>
      <a href="{{ route('lang.switch', 'fr') }}" class="{{ app()->getLocale() == 'fr' ? 'active' : '' }}">FR</a>
    </div>
  </div>
</aside>
