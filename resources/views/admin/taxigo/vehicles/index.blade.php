@extends('admin.layouts.app')

@section('title', __('taxigo.vehicles'))

@section('content')
<div class="go-page-head">
  <div>
    <div class="go-eyebrow mb-1">{{ __('taxigo.menu') }}</div>
    <h1 class="go-page-title">{{ __('taxigo.vehicles') }}</h1>
    <p class="go-page-sub">{{ __('taxigo.vehicles_sub') }}</p>
  </div>
  <a href="{{ route('admin.taxigo.vehicles.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> {{ __('taxigo.add_vehicle') }}</a>
</div>

@include('admin.taxigo._flash')

<div class="stat-strip">
  <div class="card"><div class="kpi-label">{{ __('taxigo.stats_vehicles') }}</div><div class="kpi-value">{{ $stats['total'] }}</div></div>
  <div class="card"><div class="kpi-label">{{ __('taxigo.in_service') }}</div><div class="kpi-value">{{ $stats['active'] }}</div></div>
  <div class="card"><div class="kpi-label">{{ __('taxigo.stats_unassigned') }}</div><div class="kpi-value" style="color:{{ $stats['unassigned'] ? 'var(--warn)' : 'var(--ink)' }}">{{ $stats['unassigned'] }}</div></div>
</div>

<div class="card">
  @if ($vehicles->isEmpty())
    <div class="empty-state">
      <div class="empty-ic"><i class="fas fa-car-side"></i></div>
      <p>{{ __('taxigo.no_vehicles') }}</p>
      <a href="{{ route('admin.taxigo.vehicles.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> {{ __('taxigo.add_vehicle') }}</a>
    </div>
  @else
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{{ __('taxigo.vehicle') }}</th>
            <th>{{ __('taxigo.plate') }}</th>
            <th>{{ __('taxigo.assigned_driver') }}</th>
            <th class="text-right">{{ __('taxigo.seats') }}</th>
            <th>{{ __('taxigo.status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($vehicles as $v)
            <tr>
              <td>
                <div class="person">
                  @if ($v->photo)
                    <img class="person-avatar is-car" src="{{ asset('storage/' . $v->photo) }}" alt="">
                  @else
                    <span class="person-avatar is-car"><i class="fas fa-car-side"></i></span>
                  @endif
                  <div style="min-width:0">
                    <div class="person-name">{{ $v->model }}</div>
                    <div class="person-sub">{{ $v->color ?: '—' }}</div>
                  </div>
                </div>
              </td>
              <td><span class="plate">{{ $v->plate }}</span></td>
              <td>
                @if ($v->driver)
                  <div class="person-name">{{ $v->driver->user->name }}</div>
                  <div class="person-sub">{{ $v->driver->hub->code }} · {{ $v->driver->user->phone }}</div>
                @else
                  <span class="badge bg-warning">{{ __('taxigo.no_driver') }}</span>
                @endif
              </td>
              <td class="text-right">{{ $v->seats }}</td>
              <td><span class="badge {{ $v->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $v->is_active ? __('taxigo.in_service') : __('taxigo.inactive') }}</span></td>
              <td class="text-right"><a href="{{ route('admin.taxigo.vehicles.edit', $v) }}" class="btn btn-sm btn-outline-secondary">{{ __('taxigo.edit') }}</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if ($vehicles->hasPages())<div class="card-footer">{{ $vehicles->links() }}</div>@endif
  @endif
</div>
@endsection
