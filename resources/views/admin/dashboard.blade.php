@extends('admin.layouts.app')

@section('title', __('dashboard.title'))

@php
  $hour = now()->hour;
  $part = $hour < 12 ? __('dashboard.morning') : ($hour < 18 ? __('dashboard.afternoon') : __('dashboard.evening'));
  $firstName = explode(' ', trim(Auth::user()->name))[0];
  $xaf = fn ($n) => number_format((float) $n, 0, '.', ' ');
  $statusBadge = [
      'paid' => 'bg-success', 'success' => 'bg-success',
      'pending' => 'bg-warning', 'processing' => 'bg-info',
      'failed' => 'bg-danger', 'refunded' => 'bg-secondary',
  ];
  $methodLabel = ['mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money', 'card' => 'Card', 'cash' => 'Cash'];
  $serviceLabel = ['bus' => 'GoBus', 'car' => 'GoRent', 'taxigo' => 'TaxiGo'];
@endphp

@section('content')

{{-- Header --}}
<div class="go-page-head">
  <div>
    <div class="go-eyebrow mb-1">{{ __('dashboard.eyebrow') }}</div>
    <h1 class="go-page-title">{{ __('dashboard.heading', ['part' => $part, 'name' => $firstName]) }}</h1>
    <p class="go-page-sub">{{ __('dashboard.sub') }}</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    @foreach ($modules as $module => $on)
      <span class="module-pill {{ $on ? 'is-on' : '' }}">
        <span class="dot"></span>{{ ['taxigo' => 'TaxiGo', 'gobus' => 'GoBus', 'gorent' => 'GoRent'][$module] }}
        <span class="text-muted fw-medium">· {{ $on ? __('dashboard.shown') : __('dashboard.hidden') }}</span>
      </span>
    @endforeach
  </div>
</div>

{{-- Milestones --}}
<div class="card hero-card mb-4">
  <div class="card-body d-flex flex-wrap align-items-center gap-4">
    <div class="mr-auto" style="min-width:220px">
      <h2>TaxiGo</h2>
      <p>Douala · Yaoundé Nsimalen</p>
    </div>
    @foreach ($milestones as $i => $m)
      @if ($i > 0)<div class="hero-divider d-none d-md-block"></div>@endif
      <div>
        <div class="hero-stat-label">{{ $m['label'] }} · {{ $m['date']->translatedFormat('d M') }}</div>
        <div class="hero-stat-value {{ $i === 0 ? 'is-orange' : '' }}">
          {{ $m['days'] >= 0 ? trans_choice('dashboard.days_left', $m['days'], ['count' => $m['days']]) : __('dashboard.days_ago', ['count' => abs($m['days'])]) }}
        </div>
      </div>
    @endforeach
  </div>
</div>

{{-- KPIs --}}
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100"><div class="kpi">
      <div class="kpi-top"><span class="kpi-label">{{ __('dashboard.rides_today') }}</span><span class="kpi-icon is-orange"><i class="fas fa-taxi"></i></span></div>
      <div class="kpi-value">{{ number_format($rides['today']) }}</div>
      <div class="kpi-foot">
        <span><span class="dot" style="background:var(--go-orange)"></span> {{ __('dashboard.active_now', ['count' => $rides['active']]) }}</span>
        <span>· {{ __('dashboard.upcoming', ['count' => $rides['upcoming']]) }}</span>
      </div>
    </div></div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100"><div class="kpi">
      <div class="kpi-top"><span class="kpi-label">{{ __('dashboard.revenue_month') }}</span><span class="kpi-icon is-green"><i class="fas fa-coins"></i></span></div>
      <div class="kpi-value">{{ $xaf($rides['revenue_month']) }}<small>XAF</small></div>
      <div class="kpi-foot">{{ __('dashboard.completed_month', ['count' => $rides['completed_month']]) }}</div>
    </div></div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100"><div class="kpi">
      <div class="kpi-top"><span class="kpi-label">{{ __('dashboard.drivers_online') }}</span><span class="kpi-icon is-blue"><i class="fas fa-id-badge"></i></span></div>
      <div class="kpi-value">{{ $drivers['online'] }}<small>/ {{ $drivers['total'] }}</small></div>
      <div class="kpi-foot">{{ __('dashboard.of_drivers', ['count' => $drivers['total']]) }}@if (\App\Support\Modules::adminPage('vehicles')) · {{ __('dashboard.vehicles', ['count' => $vehicles]) }}@endif</div>
    </div></div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100"><div class="kpi">
      <div class="kpi-top"><span class="kpi-label">{{ __('dashboard.customers') }}</span><span class="kpi-icon is-ink"><i class="fas fa-user-friends"></i></span></div>
      <div class="kpi-value">{{ number_format($customers['total']) }}</div>
      <div class="kpi-foot"><span class="text-success fw-semibold">{{ __('dashboard.new_month', ['count' => $customers['new_month']]) }}</span></div>
    </div></div>
  </div>
</div>

<div class="row g-3 mb-4">
  {{-- Collections chart --}}
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
          <p class="panel-title">{{ __('dashboard.collections') }}</p>
          <p class="panel-sub">{{ __('dashboard.collections_sub') }}</p>
        </div>
        <a href="{{ route('admin.payments.collections') }}" class="btn btn-sm btn-outline-secondary">{{ __('dashboard.view_all') }}</a>
      </div>
      <div class="card-body">
        <div class="row g-3 mb-3">
          <div class="col-6 col-md-3"><div class="kpi-label">{{ __('dashboard.today') }}</div><div class="fs-5 fw-bold text-gray-800 tabular">{{ $xaf($payments['today']) }} <span class="small text-muted">XAF</span></div></div>
          <div class="col-6 col-md-3"><div class="kpi-label">{{ __('dashboard.this_month') }}</div><div class="fs-5 fw-bold text-gray-800 tabular">{{ $xaf($payments['month']) }} <span class="small text-muted">XAF</span></div></div>
          <div class="col-6 col-md-3"><div class="kpi-label">{{ __('dashboard.pending') }}</div><div class="fs-5 fw-bold tabular" style="color:var(--warn)">{{ $payments['pending'] }}</div></div>
          <div class="col-6 col-md-3"><div class="kpi-label">{{ __('dashboard.failed') }}</div><div class="fs-5 fw-bold tabular" style="color:var(--bad)">{{ $payments['failed'] }}</div></div>
        </div>
        <div style="position:relative;height:240px"><canvas id="collectionsChart" aria-label="{{ __('dashboard.collections_sub') }}" role="img"></canvas></div>
      </div>
    </div>
  </div>

  {{-- Payouts --}}
  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <p class="panel-title">{{ __('dashboard.payouts') }}</p>
          <p class="panel-sub">{{ __('dashboard.payouts_sub') }}</p>
        </div>
        <a href="{{ route('admin.payments.disbursements') }}" class="btn btn-sm btn-outline-secondary">{{ __('dashboard.review') }}</a>
      </div>
      <div class="card-body">
        <div class="kpi-label">{{ __('dashboard.sent_month') }}</div>
        <div class="kpi-value mb-4 mt-1">{{ $xaf($payouts['sent_month']) }}<small>XAF</small></div>
        <ul class="check-list">
          <li>
            <span class="check-ic {{ $payouts['failed'] ? 'is-todo' : 'is-done' }}"><i class="fas {{ $payouts['failed'] ? 'fa-redo' : 'fa-check' }}"></i></span>
            <div class="flex-grow-1"><div class="check-title">{{ __('dashboard.failed_payouts') }}</div></div>
            <span class="fw-bold tabular {{ $payouts['failed'] ? 'text-danger' : 'text-gray-800' }}">{{ $payouts['failed'] }}</span>
          </li>
          <li>
            <span class="check-ic is-done" style="background:var(--go-orange-50);color:var(--go-orange-600)"><i class="fas fa-hourglass-half"></i></span>
            <div class="flex-grow-1"><div class="check-title">{{ __('dashboard.held') }}</div><div class="check-note">{{ __('dashboard.held_note') }}</div></div>
            <span class="fw-bold tabular text-gray-800">{{ $payouts['held'] }}</span>
          </li>
          <li>
            <span class="check-ic is-done" style="background:var(--go-blue-50);color:var(--go-blue)"><i class="fas fa-university"></i></span>
            <div class="flex-grow-1"><div class="check-title">{{ __('dashboard.manual') }}</div></div>
            <span class="fw-bold tabular text-gray-800">{{ $payouts['manual'] }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  {{-- Launch checklist --}}
  <div class="col-xl-4 col-lg-6">
    @php $doneCount = collect($checklist)->where('done', true)->count(); @endphp
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <p class="panel-title">{{ __('dashboard.checklist') }}</p>
          <p class="panel-sub">{{ __('dashboard.checklist_sub') }}</p>
        </div>
        <span class="badge {{ $doneCount === count($checklist) ? 'bg-success' : 'bg-primary' }}">{{ $doneCount }}/{{ count($checklist) }}</span>
      </div>
      <div class="card-body">
        <ul class="check-list">
          @foreach ($checklist as $item)
            <li>
              <span class="check-ic {{ $item['done'] ? 'is-done' : 'is-todo' }}"><i class="fas {{ $item['done'] ? 'fa-check' : 'fa-exclamation' }}"></i></span>
              <div><a href="{{ $item['url'] }}" class="check-title d-block">{{ $item['title'] }}</a><div class="check-note">{{ $item['note'] }}</div></div>
            </li>
          @endforeach
        </ul>
      </div>
      <div class="card-footer"><a href="{{ route('settings.index') }}" class="fw-semibold">{{ __('dashboard.open_settings') }} →</a></div>
    </div>
  </div>

  {{-- Revenue split --}}
  <div class="col-xl-4 col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <div class="d-flex align-items-start justify-content-between gap-2">
          <div><p class="panel-title">{{ __('dashboard.split') }}</p><p class="panel-sub">{{ __('dashboard.split_sub') }}</p></div>
          <a href="{{ route('admin.taxigo.split.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('taxigo.edit') }}</a>
        </div>
      </div>
      <div class="card-body pt-2">
        @foreach ($beneficiaries as $b)
          @php
            $hasAccount = $b->is_driver_share || ($b->channel === 'bank' ? filled($b->bank_account_number) : filled($b->msisdn));
          @endphp
          <div class="split-row">
            <div>
              <div class="split-name">
                {{ $b->name }}
                <span class="split-chip is-{{ $b->channel === 'orange' ? 'orange' : ($b->channel === 'bank' ? 'bank' : 'mtn') }}">{{ $b->is_driver_share ? __('dashboard.per_driver') : strtoupper($b->channel) }}</span>
                @unless ($hasAccount)<span class="split-chip is-missing">{{ __('dashboard.missing') }}</span>@endunless
              </div>
              <div class="split-bar"><span class="{{ $b->is_driver_share ? 'is-orange' : '' }}" style="width: {{ min(100, (float) $b->percent) }}%"></span></div>
            </div>
            <div class="split-pct">{{ rtrim(rtrim(number_format($b->percent, 2), '0'), '.') }}%</div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Referrals --}}
  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header">
        <p class="panel-title">{{ __('dashboard.referrals') }}</p>
        <p class="panel-sub">TaxiGo · {{ __('dashboard.check_commission') }}</p>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-6"><div class="kpi-label">{{ __('dashboard.partners') }}</div><div class="kpi-value mt-1">{{ $referrals['partners'] }}</div></div>
          <div class="col-6"><div class="kpi-label">{{ __('dashboard.signups') }}</div><div class="kpi-value mt-1">{{ number_format($referrals['signups']) }}</div></div>
          <div class="col-12"><div class="kpi-label">{{ __('dashboard.unpaid') }}</div><div class="kpi-value mt-1">{{ $xaf($referrals['unpaid']) }}<small>XAF</small></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  @php $showTariff = \App\Support\Modules::adminPage('tariffs'); @endphp
  @if ($showTariff)
  {{-- Tariff --}}
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header">
        <div class="d-flex align-items-start justify-content-between gap-2">
          <div><p class="panel-title">{{ __('dashboard.tariff') }}</p><p class="panel-sub">{{ __('dashboard.tariff_sub') }}</p></div>
          <a href="{{ route('admin.taxigo.tariffs.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('taxigo.edit') }}</a>
        </div>
      </div>
      @foreach ($hubs as $hub)
        <div class="tariff-hub">
          <div class="tariff-hub-head">
            <div class="d-flex align-items-center gap-2"><span class="hub-code">{{ $hub->code }}</span><span class="fw-bold text-gray-800">{{ $hub->name }}</span></div>
          </div>
          <div class="zone-grid">
            @foreach ($hub->zones as $zone)
              <div class="zone" title="{{ $zone->neighbourhoods->pluck('name')->implode(', ') }}">
                <div class="zone-code">{{ __('dashboard.zone', ['code' => $zone->code]) }}</div>
                <div class="zone-fare">{{ $xaf($zone->fare) }}</div>
                <div class="zone-areas">{{ $zone->neighbourhoods->pluck('name')->implode(', ') }}</div>
              </div>
            @endforeach
          </div>
          <div class="hub-meta">
            <span>{{ __('dashboard.night') }} <b>+{{ $xaf($hub->night_surcharge) }}</b> · {{ substr($hub->night_start, 0, 5) }}–{{ substr($hub->night_end, 0, 5) }}</span>
            <span>{{ __('dashboard.vip') }} <b>{{ $xaf($hub->vip_hourly_rate) }}</b>{{ __('dashboard.per_hour') }}</span>
            <span>{{ __('dashboard.interurban') }} <b>{{ __('dashboard.destinations', ['count' => $hub->interurbanDestinations->count()]) }}</b>
              @if ($hub->interurbanDestinations->isNotEmpty()) · {{ __('dashboard.from', ['amount' => $xaf($hub->interurbanDestinations->min('fare'))]) }} @endif
            </span>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  @endif

  {{-- Recent payments --}}
  <div class="{{ $showTariff ? 'col-xl-6' : 'col-12' }}">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <p class="panel-title">{{ __('dashboard.recent') }}</p>
        <a href="{{ route('admin.payments.collections') }}" class="btn btn-sm btn-outline-secondary">{{ __('dashboard.view_all') }}</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>{{ __('dashboard.ref') }}</th>
              <th>{{ __('dashboard.service') }}</th>
              <th class="text-right">{{ __('dashboard.amount') }}</th>
              <th>{{ __('dashboard.status') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($recentPayments as $order)
              <tr style="cursor:pointer" onclick="window.location='{{ route('admin.payments.show', $order->id) }}'">
                <td>
                  <div class="fw-semibold text-gray-800">{{ $order->order_reference }}</div>
                  <div class="small text-muted">{{ $methodLabel[$order->payment_method ?? ""] ?? '—' }} · {{ $order->created_at?->diffForHumans() }}</div>
                </td>
                <td>{{ $serviceLabel[$order->booking_type ?? ""] ?? ucfirst($order->booking_type) }}</td>
                <td class="text-right fw-semibold text-gray-800">{{ $xaf($order->total_amount) }} <span class="small text-muted">XAF</span></td>
                <td><span class="badge {{ $statusBadge[$order->payment_status ?? ""] ?? 'bg-secondary' }}">{{ ucfirst($order->payment_status) }}</span></td>
              </tr>
            @empty
              <tr class="empty-row"><td colspan="4">{{ __('dashboard.no_payments') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('admin/vendor/chart.js/Chart.min.js') }}"></script>
<script>
  (function () {
    var el = document.getElementById('collectionsChart');
    if (!el || typeof Chart === 'undefined') return;
    var data = @json($chart);
    var allZero = data.every(function (d) { return !d.total; });
    var ctx = el.getContext('2d');
    var fill = ctx.createLinearGradient(0, 0, 0, 240);
    fill.addColorStop(0, 'rgba(255, 140, 0, 0.95)');
    fill.addColorStop(1, 'rgba(255, 140, 0, 0.55)');
    Chart.defaults.global.defaultFontFamily = '"Plus Jakarta Sans", system-ui, sans-serif';
    Chart.defaults.global.defaultFontColor = '#6A7a83';
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.map(function (d) { return d.label; }),
        datasets: [{ data: data.map(function (d) { return d.total; }), backgroundColor: fill, hoverBackgroundColor: '#E17C00', borderWidth: 0, barPercentage: 0.6, categoryPercentage: 0.8 }]
      },
      options: {
        maintainAspectRatio: false,
        legend: { display: false },
        tooltips: {
          backgroundColor: '#0F1F27', titleFontStyle: '600', xPadding: 12, yPadding: 10, cornerRadius: 8, displayColors: false,
          callbacks: { label: function (item) { return Number(item.yLabel).toLocaleString('fr-FR') + ' XAF'; } }
        },
        scales: {
          xAxes: [{ gridLines: { display: false, drawBorder: false }, ticks: { fontSize: 11, maxRotation: 0, autoSkipPadding: 12 } }],
          yAxes: [{ gridLines: { color: '#F0F3F5', drawBorder: false, zeroLineColor: '#E6EBEE' },
                    ticks: { beginAtZero: true, fontSize: 11, maxTicksLimit: 5, padding: 8, suggestedMax: allZero ? 50000 : undefined,
                             callback: function (v) { return v >= 1000 ? (v / 1000) + 'k' : v; } } }]
        }
      }
    });
  })();
</script>
@endpush
