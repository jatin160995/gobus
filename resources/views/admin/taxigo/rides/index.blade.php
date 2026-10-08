@extends('admin.layouts.app')

@section('title', __('taxigo.rides'))

@php $xaf = fn ($n) => number_format((float) $n, 0, '.', ' '); $tz = config('taxigo.timezone'); @endphp

@section('content')
<div class="go-page-head">
  <div>
    <div class="go-eyebrow mb-1">{{ __('taxigo.menu') }}</div>
    <h1 class="go-page-title">{{ __('taxigo.rides') }}</h1>
    <p class="go-page-sub">{{ __('taxigo.rides_sub') }}</p>
  </div>
</div>

<div class="stat-strip">
  <div class="card"><div class="kpi-label">{{ __('taxigo.in_progress') }}</div><div class="kpi-value" style="color:var(--go-blue)">{{ $active }}</div></div>
  <div class="card"><div class="kpi-label">{{ __('taxigo.statuses.pending_payment') }}</div><div class="kpi-value" style="color:var(--warn)">{{ $counts['pending_payment'] ?? 0 }}</div></div>
  <div class="card"><div class="kpi-label">{{ __('taxigo.statuses.completed') }}</div><div class="kpi-value" style="color:var(--ok)">{{ $counts['completed'] ?? 0 }}</div></div>
  <div class="card"><div class="kpi-label">{{ __('taxigo.statuses.cancelled') }}</div><div class="kpi-value">{{ ($counts['cancelled'] ?? 0) + ($counts['expired'] ?? 0) }}</div></div>
</div>

<div class="card">
  <div class="card-header">
    <form method="GET" class="filter-bar">
      <div class="grow">
        <label for="search">{{ __('taxigo.search') }}</label>
        <input id="search" type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="{{ __('taxigo.search_rides') }}">
      </div>
      <div>
        <label for="status">{{ __('taxigo.status') }}</label>
        <select id="status" name="status" class="form-select form-select-sm">
          <option value="">{{ __('taxigo.all_statuses') }}</option>
          <option value="active" @selected(request('status') === 'active')>{{ __('taxigo.in_progress') }}</option>
          @foreach (\App\Http\Controllers\Admin\TaxiGo\RideController::STATUSES as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ __("taxigo.statuses.$s") }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="hub">{{ __('taxigo.hub') }}</label>
        <select id="hub" name="hub" class="form-select form-select-sm">
          <option value="">{{ __('taxigo.all_hubs') }}</option>
          @foreach ($hubs as $hub)<option value="{{ $hub->id }}" @selected(request('hub') == $hub->id)>{{ $hub->code }} · {{ $hub->city }}</option>@endforeach
        </select>
      </div>
      <div>
        <label for="source">{{ __('taxigo.source') }}</label>
        <select id="source" name="source" class="form-select form-select-sm">
          <option value="">{{ __('taxigo.all') }}</option>
          @foreach (__('taxigo.sources') as $value => $label)<option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>@endforeach
        </select>
      </div>
      <div>
        <label for="date">{{ __('taxigo.pickup_time') }}</label>
        <input id="date" type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm">
      </div>
      <div class="d-flex gap-2" style="min-width:0">
        <button class="btn btn-sm btn-secondary" type="submit">{{ __('taxigo.filter') }}</button>
        @if (request()->hasAny(['search', 'status', 'hub', 'source', 'date']))<a class="btn btn-sm btn-link" href="{{ route('admin.taxigo.rides.index') }}">{{ __('taxigo.clear') }}</a>@endif
      </div>
    </form>
  </div>

  @if ($rides->isEmpty())
    <div class="empty-state">
      <div class="empty-ic"><i class="fas fa-taxi"></i></div>
      <p>{{ request()->hasAny(['search', 'status', 'hub', 'source', 'date']) ? __('taxigo.no_rides_filtered') : __('taxigo.no_rides') }}</p>
    </div>
  @else
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{{ __('taxigo.ride') }}</th>
            <th>{{ __('taxigo.passenger') }}</th>
            <th>{{ __('taxigo.trip') }}</th>
            <th>{{ __('taxigo.pickup_time') }}</th>
            <th>{{ __('taxigo.driver') }}</th>
            <th class="text-right">{{ __('taxigo.fare') }}</th>
            <th>{{ __('taxigo.status') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($rides as $r)
            <tr style="cursor:pointer" onclick="window.location='{{ route('admin.taxigo.rides.show', $r) }}'">
              <td>
                <a href="{{ route('admin.taxigo.rides.show', $r) }}" class="fw-semibold">{{ $r->ref }}</a>
                <div class="person-sub">{{ __('taxigo.sources')[$r->source] }}</div>
              </td>
              <td><div class="person-name">{{ $r->passenger_name }}</div><div class="person-sub">{{ $r->passenger_phone }}</div></td>
              <td>
                <span class="hub-code">{{ $r->hub->code }}</span>
                <span class="small fw-semibold text-gray-800 ml-1">{{ __("taxigo.trip_types.{$r->trip_type}") }}</span>
                <div class="person-sub">{{ $r->neighbourhood?->name ?? $r->interurbanDestination?->name ?? ($r->vip_hours ? __('taxigo.vip_hours', ['count' => $r->vip_hours]) : '') }}{{ $r->zone ? ' · ' . __('taxigo.zone', ['code' => $r->zone->code]) : '' }}</div>
              </td>
              <td>
                <div class="text-gray-800 fw-semibold tabular">{{ $r->pickup_at->copy()->setTimezone($tz)->format('d M · H:i') }}</div>
                <div class="person-sub">{{ $r->is_scheduled ? __('taxigo.scheduled') : __('taxigo.now') }}{{ $r->flight_number ? ' · ' . $r->flight_number : '' }}</div>
              </td>
              <td>{!! $r->driver ? e($r->driver->user->name) : '<span class="text-muted">—</span>' !!}</td>
              <td class="text-right fw-semibold text-gray-800 tabular">{{ $xaf($r->total_fare) }} <span class="small text-muted">XAF</span></td>
              <td><span class="ride-status s-{{ $r->status }}">{{ __("taxigo.statuses.{$r->status}") }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if ($rides->hasPages())<div class="card-footer">{{ $rides->links() }}</div>@endif
  @endif
</div>
@endsection
