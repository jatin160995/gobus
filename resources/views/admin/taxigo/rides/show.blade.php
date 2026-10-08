@extends('admin.layouts.app')

@section('title', $ride->ref)

@php
  $xaf = fn ($n) => number_format((float) $n, 0, '.', ' ');
  $tz = config('taxigo.timezone');
  $payoutBadge = ['success' => 'bg-success', 'failed' => 'bg-danger', 'held' => 'bg-primary', 'manual_pending' => 'bg-info', 'pending' => 'bg-warning', 'processing' => 'bg-warning'];
@endphp

@section('content')
<div class="go-page-head">
  <div>
    <a href="{{ route('admin.taxigo.rides.index') }}" class="small fw-semibold"><i class="fas fa-arrow-left mr-1"></i>{{ __('taxigo.rides') }}</a>
    <h1 class="go-page-title mt-2 d-flex align-items-center flex-wrap gap-2">{{ $ride->ref }} <span class="ride-status s-{{ $ride->status }}" style="font-size:13px">{{ __("taxigo.statuses.{$ride->status}") }}</span></h1>
    <p class="go-page-sub">{{ __('taxigo.sources')[$ride->source] }} · {{ $ride->created_at->copy()->setTimezone($tz)->format('d M Y, H:i') }}</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="card mb-3">
      <div class="card-header"><p class="panel-title">{{ __('taxigo.trip') }}</p></div>
      <div class="card-body">
        <div class="route-line mb-4">
          <span class="dot"></span>
          <div><div class="kpi-label">{{ __('taxigo.pickup') }}</div><div class="fw-semibold text-gray-800">{{ $ride->pickup_address ?: '—' }}</div></div>
          <span class="bar"></span><span></span>
          <span class="dot is-end"></span>
          <div><div class="kpi-label">{{ __('taxigo.dropoff') }}</div><div class="fw-semibold text-gray-800">{{ $ride->dropoff_address ?: '—' }}</div></div>
        </div>
        <dl class="detail-list">
          <dt>{{ __('taxigo.hub') }}</dt><dd><span class="hub-code">{{ $ride->hub->code }}</span> {{ $ride->hub->name }}</dd>
          <dt>{{ __('taxigo.trip') }}</dt><dd>{{ __("taxigo.trip_types.{$ride->trip_type}") }}
            @if ($ride->zone) · {{ __('taxigo.zone', ['code' => $ride->zone->code]) }} @endif
            @if ($ride->neighbourhood) · {{ $ride->neighbourhood->name }} @endif
            @if ($ride->interurbanDestination) · {{ $ride->interurbanDestination->name }} @endif
            @if ($ride->vip_hours) · {{ __('taxigo.vip_hours', ['count' => $ride->vip_hours]) }} @endif
          </dd>
          <dt>{{ __('taxigo.pickup_time') }}</dt><dd>{{ $ride->pickup_at->copy()->setTimezone($tz)->format('l d M Y, H:i') }} · {{ $ride->is_scheduled ? __('taxigo.scheduled') : __('taxigo.now') }}</dd>
          @if ($ride->flight_number)<dt>{{ __('taxigo.flight') }}</dt><dd>{{ $ride->flight_number }}</dd>@endif
          <dt>{{ __('taxigo.passenger') }}</dt><dd>{{ $ride->passenger_name }} · {{ $ride->passenger_phone }}</dd>
          @if ($ride->customer)<dt>{{ __('taxigo.customer') }}</dt><dd>{{ $ride->customer->name }} · {{ $ride->customer->email ?? $ride->customer->phone }}</dd>@endif
          @if ($ride->createdBy)<dt>{{ __('taxigo.booked_by') }}</dt><dd>{{ $ride->createdBy->name }}</dd>@endif
          @if ($ride->notes)<dt>{{ __('taxigo.notes') }}</dt><dd>{{ $ride->notes }}</dd>@endif
        </dl>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><p class="panel-title">{{ __('taxigo.payouts') }}</p></div>
      @if ($payouts->isEmpty())
        <div class="card-body text-muted">{{ __('taxigo.no_payouts') }}</div>
      @else
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>{{ __('taxigo.beneficiary') }}</th><th class="text-right">{{ __('taxigo.share') }}</th><th class="text-right">{{ __('taxigo.fare') }}</th><th>{{ __('taxigo.status') }}</th></tr></thead>
            <tbody>
              @foreach ($payouts as $p)
                <tr>
                  <td class="fw-semibold text-gray-800">{{ $beneficiaries[$p->beneficiary_id] ?? ucfirst($p->recipient_type) }}
                    @if ($p->failure_reason)<div class="small text-danger fw-normal">{{ $p->failure_reason }}</div>@endif
                  </td>
                  <td class="text-right tabular">{{ rtrim(rtrim(number_format((float) $p->percent_snapshot, 2), '0'), '.') }}%</td>
                  <td class="text-right fw-semibold text-gray-800 tabular">{{ $xaf($p->amount) }} <span class="small text-muted">XAF</span></td>
                  <td><span class="badge {{ $payoutBadge[$p->transaction_status] ?? 'bg-secondary' }}">{{ __("taxigo.payout_statuses.{$p->transaction_status}") }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card mb-3">
      <div class="card-header"><p class="panel-title">{{ __('taxigo.fare_breakdown') }}</p></div>
      <div class="card-body">
        <dl class="detail-list" style="grid-template-columns:minmax(0,1fr) auto">
          <dt>{{ __('taxigo.base_fare') }}</dt><dd class="text-right tabular">{{ $xaf($ride->base_fare) }} XAF</dd>
          <dt>{{ __('taxigo.night') }}</dt><dd class="text-right tabular">{{ $xaf($ride->night_surcharge) }} XAF</dd>
          <dt class="fw-bold text-gray-800">{{ __('taxigo.total') }}</dt><dd class="text-right tabular fw-bold" style="font-size:16px">{{ $xaf($ride->total_fare) }} XAF</dd>
          <dt>{{ __('taxigo.payment') }}</dt>
          <dd class="text-right"><span class="badge {{ ['paid' => 'bg-success', 'failed' => 'bg-danger', 'refunded' => 'bg-secondary'][$ride->payment_status] ?? 'bg-warning' }}">{{ __("taxigo.payment_statuses.{$ride->payment_status}") }}</span>
            @if ($ride->payment_method)<div class="small text-muted mt-1">{{ $ride->payment_method === 'mtn_momo' ? 'MTN MoMo' : 'Orange Money' }} · {{ $ride->payer_phone }}</div>@endif
          </dd>
          @if ($ride->paymentOrder)<dt>{{ __('taxigo.order') }}</dt><dd class="text-right"><a href="{{ route('admin.payments.show', $ride->paymentOrder->id) }}">{{ $ride->paymentOrder->order_reference }}</a></dd>@endif
        </dl>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><p class="panel-title">{{ __('taxigo.driver') }}</p></div>
      <div class="card-body">
        @if ($ride->driver)
          <div class="person mb-2">
            @if ($ride->driver->photo)<img class="person-avatar" src="{{ asset('storage/' . $ride->driver->photo) }}" alt="">@else<span class="person-avatar"><i class="fas fa-user"></i></span>@endif
            <div><div class="person-name">{{ $ride->driver->user->name }}</div><div class="person-sub">{{ $ride->driver->user->phone }}</div></div>
          </div>
          @if ($ride->vehicle)<span class="plate">{{ $ride->vehicle->plate }}</span> <span class="small text-muted">{{ $ride->vehicle->model }}</span>@endif
        @else
          <span class="text-muted">{{ __('taxigo.no_driver_yet') }}</span>
        @endif
      </div>
    </div>

    <div class="card">
      <div class="card-header"><p class="panel-title">{{ __('taxigo.timeline') }}</p></div>
      <div class="card-body">
        <ul class="timeline-list">
          @foreach ($ride->statusLogs as $log)
            <li>
              <div class="t-title">{{ __("taxigo.statuses.{$log->to_status}") }}</div>
              <div class="t-meta">{{ $log->created_at->copy()->setTimezone($tz)->format('d M, H:i') }} · {{ ucfirst($log->actor_type) }}{{ $log->note ? ' · ' . $log->note : '' }}</div>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
