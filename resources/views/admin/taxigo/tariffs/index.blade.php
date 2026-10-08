@extends('admin.layouts.app')

@section('title', __('taxigo.tariffs'))

@section('content')
<div class="go-page-head">
  <div>
    <div class="go-eyebrow mb-1">{{ __('taxigo.menu') }}</div>
    <h1 class="go-page-title">{{ __('taxigo.tariffs') }}</h1>
    <p class="go-page-sub">{{ __('taxigo.tariffs_sub') }}</p>
  </div>
</div>

@include('admin.taxigo._flash')

<div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>{{ __('taxigo.applies_to_new') }}</div>

@foreach ($hubs as $hub)
  <div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
      <span class="hub-code">{{ $hub->code }}</span>
      <span class="panel-title">{{ $hub->name }}</span>
    </div>

    {{-- Zone fares and neighbourhoods --}}
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <p class="panel-title">{{ __('taxigo.zone_fares') }}</p>
      </div>

      <form id="zones-{{ $hub->id }}" method="POST" action="{{ route('admin.taxigo.tariffs.zones', $hub) }}">
        @csrf @method('PUT')
      </form>

      <div class="row g-3">
        @foreach ($hub->zones as $zone)
          <div class="col-md-6 col-xl-3">
            <div class="zone-edit h-100">
              <div class="zone-edit-head">
                <span class="fw-bold text-gray-800">{{ __('taxigo.zone', ['code' => $zone->code]) }}</span>
                <label class="mb-0 small text-muted d-inline-flex align-items-center gap-1" title="{{ __('taxigo.shown_in_app') }}">
                  <input form="zones-{{ $hub->id }}" type="checkbox" name="zones[{{ $zone->id }}][is_active]" value="1" @checked($zone->is_active)>
                  {{ __('taxigo.active') }}
                </label>
              </div>
              <div class="input-suffix">
                <input form="zones-{{ $hub->id }}" type="number" min="0" step="500" class="form-control fw-bold"
                       name="zones[{{ $zone->id }}][fare]" value="{{ old("zones.{$zone->id}.fare", (int) $zone->fare) }}"
                       aria-label="{{ __('taxigo.zone', ['code' => $zone->code]) }} {{ __('taxigo.fare') }}">
                <span>XAF</span>
              </div>
              <div>
                <div class="kpi-label mb-2">{{ __('taxigo.neighbourhoods') }}</div>
                <div class="nbh-chips mb-2">
                  @foreach ($zone->neighbourhoods as $n)
                    <span class="nbh-chip {{ $n->is_active ? '' : 'is-off' }}">
                      {{ $n->name }}
                      @if ($n->is_active)
                        <form method="POST" action="{{ route('admin.taxigo.tariffs.neighbourhoods.destroy', $n) }}">
                          @csrf @method('DELETE')
                          <button type="submit" title="{{ __('taxigo.remove') }} {{ $n->name }}" aria-label="{{ __('taxigo.remove') }} {{ $n->name }}"><i class="fas fa-times"></i></button>
                        </form>
                      @endif
                    </span>
                  @endforeach
                </div>
                <form class="nbh-add" method="POST" action="{{ route('admin.taxigo.tariffs.neighbourhoods.store', $zone) }}">
                  @csrf
                  <input type="text" name="name" class="form-control" placeholder="{{ __('taxigo.add_neighbourhood') }}" required maxlength="150" aria-label="{{ __('taxigo.add_neighbourhood') }}">
                  <button class="btn btn-sm btn-outline-secondary" type="submit" aria-label="{{ __('taxigo.add') }}"><i class="fas fa-plus"></i></button>
                </form>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="d-flex justify-content-end mt-3">
        <button form="zones-{{ $hub->id }}" type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i> {{ __('taxigo.save') }}</button>
      </div>
    </div>

    <div class="row g-0" style="border-top:1px solid var(--line)">
      {{-- Night and VIP --}}
      <div class="col-lg-5" style="border-right:1px solid var(--line)">
        <form class="card-body" method="POST" action="{{ route('admin.taxigo.tariffs.hub', $hub) }}">
          @csrf @method('PUT')
          <p class="panel-title mb-3">{{ __('taxigo.night_and_vip') }}</p>
          <div class="row g-2">
            <div class="col-6">
              <label for="ns-{{ $hub->id }}">{{ __('taxigo.night_start') }}</label>
              <input id="ns-{{ $hub->id }}" type="time" name="night_start" class="form-control" value="{{ substr($hub->night_start, 0, 5) }}" required>
            </div>
            <div class="col-6">
              <label for="ne-{{ $hub->id }}">{{ __('taxigo.night_end') }}</label>
              <input id="ne-{{ $hub->id }}" type="time" name="night_end" class="form-control" value="{{ substr($hub->night_end, 0, 5) }}" required>
            </div>
            <div class="col-12">
              <label for="nsur-{{ $hub->id }}">{{ __('taxigo.night_surcharge') }}</label>
              <div class="input-suffix"><input id="nsur-{{ $hub->id }}" type="number" min="0" step="500" name="night_surcharge" class="form-control" value="{{ (int) $hub->night_surcharge }}" required><span>XAF</span></div>
            </div>
            <div class="col-7">
              <label for="vip-{{ $hub->id }}">{{ __('taxigo.vip_rate') }}</label>
              <div class="input-suffix"><input id="vip-{{ $hub->id }}" type="number" min="0" step="500" name="vip_hourly_rate" class="form-control" value="{{ (int) $hub->vip_hourly_rate }}" required><span>/h</span></div>
            </div>
            <div class="col-5">
              <label for="vipm-{{ $hub->id }}">{{ __('taxigo.vip_min') }}</label>
              <input id="vipm-{{ $hub->id }}" type="number" min="1" max="24" name="vip_min_hours" class="form-control" value="{{ (int) $hub->vip_min_hours }}" required>
            </div>
          </div>
          <button type="submit" class="btn btn-outline-secondary mt-3">{{ __('taxigo.save') }}</button>
        </form>
      </div>

      {{-- Interurban --}}
      <div class="col-lg-7">
        <div class="card-body">
          <p class="panel-title mb-3">{{ __('taxigo.interurban') }}</p>
          <div class="table-responsive" style="border:1px solid var(--line);border-radius:12px">
            <table class="table">
              <thead><tr><th>{{ __('taxigo.destination') }}</th><th>{{ __('taxigo.fare') }}</th><th>{{ __('taxigo.active') }}</th><th></th></tr></thead>
              <tbody>
                @foreach ($hub->interurbanDestinations as $d)
                  <tr>
                    <td class="fw-semibold text-gray-800 {{ $d->is_active ? '' : 'text-muted' }}">{{ $d->name }}</td>
                    <td style="min-width:150px">
                      <form id="dest-{{ $d->id }}" method="POST" action="{{ route('admin.taxigo.tariffs.interurban.update', $d) }}">@csrf @method('PUT')</form>
                      <div class="input-suffix"><input form="dest-{{ $d->id }}" type="number" min="0" step="1000" name="fare" value="{{ (int) $d->fare }}" class="form-control form-control-sm" aria-label="{{ $d->name }} {{ __('taxigo.fare') }}"><span>XAF</span></div>
                    </td>
                    <td><input form="dest-{{ $d->id }}" type="checkbox" name="is_active" value="1" @checked($d->is_active) aria-label="{{ __('taxigo.shown_in_app') }}"></td>
                    <td class="text-right" style="white-space:nowrap">
                      <button form="dest-{{ $d->id }}" type="submit" class="btn btn-sm btn-outline-secondary" title="{{ __('taxigo.save') }}" aria-label="{{ __('taxigo.save') }} {{ $d->name }}"><i class="fas fa-check"></i></button>
                      <form class="d-inline" method="POST" action="{{ route('admin.taxigo.tariffs.interurban.destroy', $d) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="{{ __('taxigo.remove') }} {{ $d->name }}"><i class="fas fa-trash-alt"></i></button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <form class="d-flex flex-wrap gap-2 mt-3" method="POST" action="{{ route('admin.taxigo.tariffs.interurban.store', $hub) }}">
            @csrf
            <input type="text" name="name" class="form-control" style="flex:1 1 180px" placeholder="{{ __('taxigo.destination') }}" required maxlength="150" aria-label="{{ __('taxigo.destination') }}">
            <div class="input-suffix" style="flex:0 1 170px"><input type="number" min="0" step="1000" name="fare" class="form-control" placeholder="{{ __('taxigo.fare') }}" required aria-label="{{ __('taxigo.fare') }}"><span>XAF</span></div>
            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-plus mr-1"></i>{{ __('taxigo.add_destination') }}</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endforeach
@endsection
