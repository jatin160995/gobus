@extends('provider.layouts.app')

@section('content')
<h3>{{ __('trips.form.title_create') }}</h3>

<form method="POST" action="{{ route('provider.trips.store') }}">
  @csrf
@if ($errors->any())
    <div class="alert alert-danger">
        <strong>{{ $errors->first() }}</strong>
    </div>
@endif

  <div class="mb-3">
    <label>{{ __('trips.form.route') }}</label>
    <select name="route_id" class="form-control" required>
      <option value="">{{ __('trips.form.select_route') }}</option>
      @foreach($routes as $r)
        <option value="{{ $r->id }}">{{ optional($r->departureCity)->name }} → {{ optional($r->arrivalCity)->name }}</option>
      @endforeach
    </select>
  </div>

  <div class="mb-3">
    <label>{{ __('trips.form.vehicle') }}</label>
    <select name="vehicle_id" class="form-control" required>
      <option value="">{{ __('trips.form.select_vehicle') }}</option>
      @foreach($vehicles as $v)
        <option value="{{ $v->id }}">{{ $v->plate_number }} — {{ $v->model }}</option>
      @endforeach
    </select>
  </div>

  <div class="row">
    <div class="col-md-3 mb-3">
      <label>{{ __('trips.form.price') }}</label>
      <input type="text" name="price" class="form-control" value="{{ old('price') }}" required>
    </div>

    <div class="col-md-3 mb-3">
      <label>{{ __('trips.form.round_trip_price') }}</label>
      <input type="text" 
            name="round_trip_price" 
            class="form-control" 
            value="{{ old('round_trip_price') }}"
            placeholder="{{ __('trips.form.round_trip_price_placeholder') }}">
      <small class="text-muted">{{ __('trips.form.round_trip_price_hint') }}</small>
    </div>

    <div class="col-md-3 mb-3">
      <label>{{ __('trips.form.child_price') }}</label>
      <input type="text"
            name="child_price"
            class="form-control"
            value="{{ old('child_price', $trip->child_price ?? '') }}"
            placeholder="{{ __('trips.form.child_price_placeholder') }}">
      <small class="text-muted">{{ __('trips.form.child_price_hint') }}</small>
    </div>

    <div class="col-md-3 mb-3">
      <label>{{ __('trips.form.seats_total') }}</label>
      <input type="number" name="seats_total" class="form-control" value="{{ old('seats_total',50) }}">
    </div>

    <div class="col-md-3 mb-3">
      <label>{{ __('trips.form.comfort_type') }}</label>
      <select name="comfort_type" class="form-control">
        <option value="standard">Standard</option>
        <option value="vip">VIP</option>
      </select>
    </div>

    <div class="col-md-3 mb-3">
      <label>{{ __('trips.form.departure_time') }}</label>
      <input type="time" name="departure_time" class="form-control" value="{{ old('departure_time') }}" required>
    </div>
  </div>

  <div class="row">
    <div class="col mb-3">
      <label>{{ __('trips.form.start_date') }}</label>
      <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}">
    </div>
    <div class="col mb-3">
      <label>{{ __('trips.form.end_date') }}</label>
      <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <label>{{ __('trips.form.transport_type') }}</label>
    <select name="transport_type" class="form-control" required>
        <option value="bus">Bus</option>
        <option value="train">Train</option>
        <option value="air">Air</option>
        <option value="car">Car</option>
    </select>
</div>


  <div class="mb-3">
    <label>{{ __('trips.form.recurrence') }}</label>
    <select id="recurrence" name="recurrence" class="form-control">
      <option value="none">{{ __('trips.form.once') }}</option>
      <option value="daily">{{ __('trips.form.daily') }}</option>
      <option value="weekly">{{ __('trips.form.weekly') }}</option>
    </select>
  </div>

  <div id="weekdays_wrapper" style="display:none;" class="mb-3">
    <label>{{ __('trips.form.weekdays') }}</label>
    <div class="d-flex gap-2 flex-wrap">
      @php $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; @endphp
      @foreach($days as $i=>$d)
        <label class="form-check form-check-inline">
          <input type="checkbox" name="weekdays[]" value="{{ $i }}" class="form-check-input"> {{ $d }}
        </label>
      @endforeach
    </div>
  </div>

  <div class="mb-3">
    <label>{{ __('trips.form.stops') }}</label>
    <div id="stops_container"></div>
    <button type="button" id="add_stop" class="btn btn-secondary mt-2">{{ __('trips.form.add_stop') }}</button>
  </div>

  <button class="btn btn-danger">{{ __('trips.form.save') }}</button>
</form>

<script>
document.getElementById('recurrence').addEventListener('change', function(){
  document.getElementById('weekdays_wrapper').style.display = this.value === 'weekly' ? 'block' : 'none';
});

let stopsContainer = document.getElementById('stops_container');
let cities = {!! $cities->map(fn($c)=>['id'=>$c->id,'name'=>$c->name])->toJson() !!};

function addStopRow(data = {}) {
  let idx = stopsContainer.children.length;
  let wrapper = document.createElement('div');
  wrapper.className = 'stop-row mb-2 p-2 border rounded';
  wrapper.innerHTML = `
    <div class="row gx-2 align-items-center">
      <div class="col-5">
        <select name="stops[${idx}][city_id]" class="form-control" required>
          <option value="">{{ __('trips.form.select_city') }}</option>
          ${cities.map(c=>`<option value="${c.id}" ${data.city_id==c.id?'selected':''}>${c.name}</option>`).join('')}
        </select>
      </div>
      <div class="col-3"><input type="time" name="stops[${idx}][arrival_time]" class="form-control" value="${data.arrival_time||''}" placeholder="Arrival"></div>
      <div class="col-3"><input type="time" name="stops[${idx}][departure_time]" class="form-control" value="${data.departure_time||''}" placeholder="Departure"></div>
      <div class="col-1"><button type="button" class="btn btn-danger remove-stop">X</button></div>
    </div>
  `;
  stopsContainer.appendChild(wrapper);

  wrapper.querySelector('.remove-stop').addEventListener('click', ()=> wrapper.remove());
}

document.getElementById('add_stop').addEventListener('click', ()=> addStopRow());
</script>
@endsection
