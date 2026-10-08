@extends('admin.layouts.app')

@section('title', __('taxigo.drivers'))

@section('content')
<div class="go-page-head">
  <div>
    <div class="go-eyebrow mb-1">{{ __('taxigo.menu') }}</div>
    <h1 class="go-page-title">{{ __('taxigo.drivers') }}</h1>
    <p class="go-page-sub">{{ __('taxigo.drivers_sub') }}</p>
  </div>
  <a href="{{ route('admin.taxigo.drivers.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> {{ __('taxigo.add_driver') }}</a>
</div>

@include('admin.taxigo._flash')

<div class="stat-strip">
  <div class="card"><div class="kpi-label">{{ __('taxigo.stats_drivers') }}</div><div class="kpi-value">{{ $stats['total'] }}</div></div>
  <div class="card"><div class="kpi-label">{{ __('taxigo.stats_active') }}</div><div class="kpi-value">{{ $stats['active'] }}</div></div>
  <div class="card"><div class="kpi-label">{{ __('taxigo.stats_online') }}</div><div class="kpi-value" style="color:var(--ok)">{{ $stats['online'] }}</div></div>
</div>

<div class="card">
  <div class="card-header">
    <form method="GET" class="filter-bar">
      <div class="grow">
        <label for="search">{{ __('taxigo.search') }}</label>
        <input id="search" type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="{{ __('taxigo.name') }} / {{ __('taxigo.phone') }}">
      </div>
      <div>
        <label for="hub">{{ __('taxigo.hub') }}</label>
        <select id="hub" name="hub" class="form-select form-select-sm">
          <option value="">{{ __('taxigo.all_hubs') }}</option>
          @foreach ($hubs as $hub)<option value="{{ $hub->id }}" @selected(request('hub') == $hub->id)>{{ $hub->code }} · {{ $hub->city }}</option>@endforeach
        </select>
      </div>
      <div class="d-flex gap-2" style="min-width:0">
        <button class="btn btn-sm btn-secondary" type="submit">{{ __('taxigo.filter') }}</button>
        @if (request()->hasAny(['search', 'hub']))<a class="btn btn-sm btn-link" href="{{ route('admin.taxigo.drivers.index') }}">{{ __('taxigo.clear') }}</a>@endif
      </div>
    </form>
  </div>

  @if ($drivers->isEmpty())
    <div class="empty-state">
      <div class="empty-ic"><i class="fas fa-id-badge"></i></div>
      <p>{{ request()->hasAny(['search', 'hub']) ? __('taxigo.no_rides_filtered') : __('taxigo.no_drivers') }}</p>
      <a href="{{ route('admin.taxigo.drivers.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> {{ __('taxigo.add_driver') }}</a>
    </div>
  @else
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{{ __('taxigo.driver') }}</th>
            <th>{{ __('taxigo.hub') }}</th>
            <th>{{ __('taxigo.vehicle') }}</th>
            <th>{{ __('taxigo.payout') }}</th>
            <th class="text-right">{{ __('taxigo.completed_rides') }}</th>
            <th>{{ __('taxigo.status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($drivers as $d)
            <tr>
              <td>
                <div class="person">
                  @if ($d->photo)
                    <img class="person-avatar" src="{{ asset('storage/' . $d->photo) }}" alt="">
                  @else
                    <span class="person-avatar">{{ collect(explode(' ', $d->user->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}</span>
                  @endif
                  <div style="min-width:0">
                    <div class="person-name">{{ $d->user->name }}</div>
                    <div class="person-sub">{{ $d->user->phone }}</div>
                  </div>
                </div>
              </td>
              <td><span class="hub-code">{{ $d->hub->code }}</span></td>
              <td>
                @if ($d->vehicle)
                  <span class="plate">{{ $d->vehicle->plate }}</span>
                  <div class="person-sub mt-1">{{ $d->vehicle->model }}</div>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td>
                <span class="split-chip {{ $d->payout_channel === 'orange' ? 'is-orange' : 'is-mtn' }}">{{ strtoupper($d->payout_channel) }}</span>
                <div class="person-sub mt-1">{{ $d->payout_msisdn }}</div>
              </td>
              <td class="text-right fw-semibold text-gray-800">{{ $d->completed_rides }}</td>
              <td>
                @if (!$d->is_active)
                  <span class="badge bg-secondary">{{ __('taxigo.inactive') }}</span>
                @else
                  <span class="small fw-semibold"><span class="online-dot {{ $d->is_online ? 'is-on' : '' }}"></span>{{ $d->is_online ? __('taxigo.online') : __('taxigo.offline') }}</span>
                @endif
              </td>
              <td class="text-right" style="white-space:nowrap">
                <a href="{{ route('admin.taxigo.drivers.edit', $d) }}" class="btn btn-sm btn-outline-secondary">{{ __('taxigo.edit') }}</a>
                <form class="d-inline" method="POST" action="{{ route('admin.taxigo.drivers.toggle', $d) }}">
                  @csrf
                  <button type="submit" class="btn btn-sm {{ $d->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $d->is_active ? __('taxigo.deactivate') : __('taxigo.activate') }}</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if ($drivers->hasPages())<div class="card-footer">{{ $drivers->links() }}</div>@endif
  @endif
</div>
@endsection
