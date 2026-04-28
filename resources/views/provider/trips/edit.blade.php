@extends('provider.layouts.app')

@section('content')
<h3>{{ __('trips.form.title_edit') }}</h3>

{{-- show validation or other errors --}}
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

{{-- If backend signalled a booking conflict show a small alert (modal will open automatically) --}}
@if(session('booking_conflict'))
  <div class="alert alert-warning">
    {{ session('booking_conflict_message') ?? 'Some seats on existing schedules are reserved/booked. Editing the date range will remove existing schedules & seats.' }}
    <br>
    A confirmation dialog will open so you can choose to proceed.
  </div>
@endif

<form id="mainEditForm" method="POST" action="{{ route('provider.trips.update', $trip->id) }}">
  @csrf @method('PUT')

  <!-- ROUTE -->
  <div class="mb-3">
    <label>{{ __('trips.form.route') }}</label>
    <select name="route_id" class="form-control" required>
      @foreach($routes as $r)
        <option value="{{ $r->id }}" {{ $r->id == $trip->route_id ? 'selected' : '' }}>
          {{ optional($r->departureCity)->name ?? '' }} → {{ optional($r->arrivalCity)->name ?? '' }}
        </option>
      @endforeach
    </select>
  </div>

  <!-- VEHICLE -->
  <div class="mb-3">
    <label>{{ __('trips.form.vehicle') }}</label>
    <select name="vehicle_id" class="form-control" required>
      @foreach($vehicles as $v)
        <option value="{{ $v->id }}" {{ $v->id == $trip->vehicle_id ? 'selected' : '' }}>
          {{ $v->plate_number }} — {{ $v->model }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="row">
    <!-- PRICE -->
    <div class="col-md-3 mb-3">
      <label>{{ __('trips.form.price') }}</label>
      <input type="text" name="price" class="form-control" value="{{ $trip->price }}" required>
    </div>

    <div class="col-md-3 mb-3">
      <label>{{ __('trips.form.round_trip_price') }}</label>
      <input type="text" 
            name="round_trip_price" 
            class="form-control" 
            value="{{ $trip->round_trip_price }}"
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

    <!-- SEATS -->
    <div class="col-md-3 mb-3">
      <label>{{ __('trips.form.seats_total') }}</label>
      <input type="number" name="seats_total" class="form-control" value="{{ $trip->seats_total }}">
    </div>

    <!-- COMFORT -->
    <div class="col-md-3 mb-3">
      <label>{{ __('trips.form.comfort_type') }}</label>
      <select name="comfort_type" class="form-control">
        <option value="standard" {{ $trip->comfort_type == 'standard' ? 'selected' : '' }}>Standard</option>
        <option value="vip" {{ $trip->comfort_type == 'vip' ? 'selected' : '' }}>VIP</option>
      </select>
    </div>

    <!-- TIME -->
    <div class="col-md-3 mb-3">
      <label>{{ __('trips.form.departure_time') }}</label>
      <input type="time" name="departure_time" class="form-control"
             value="{{ \Carbon\Carbon::parse($trip->departure_datetime)->format('H:i') }}" required>
    </div>
  </div>

  <div class="row">
    <!-- START DATE -->
    <div class="col mb-3">
      <label>{{ __('trips.form.start_date') }}</label>
      <input type="date" name="start_date" class="form-control" value="{{ optional($trip->start_date)->format('Y-m-d') }}" required>
    </div>

    <!-- END DATE -->
    <div class="col mb-3">
      <label>{{ __('trips.form.end_date') }}</label>
      <input type="date" name="end_date" class="form-control" value="{{ optional($trip->end_date)->format('Y-m-d') }}">
    </div>
  </div>

  <!-- RECURRENCE -->
  <div class="mb-3">
    <label>{{ __('trips.form.recurrence') }}</label>
    <select id="recurrence" name="recurrence" class="form-control">
      <option value="none" {{ $trip->recurrence == 'none' ? 'selected' : '' }}>{{ __('trips.form.once') }}</option>
      <option value="daily" {{ $trip->recurrence == 'daily' ? 'selected' : '' }}>{{ __('trips.form.daily') }}</option>
      <option value="weekly" {{ $trip->recurrence == 'weekly' ? 'selected' : '' }}>{{ __('trips.form.weekly') }}</option>
    </select>
  </div>

  <!-- WEEKDAYS -->
  <div id="weekdays_wrapper" class="mb-3" style="{{ $trip->recurrence == 'weekly' ? '' : 'display:none;' }}">
    <label>{{ __('trips.form.weekdays') }}</label>
    <div class="d-flex gap-2 flex-wrap">
      @php $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; @endphp
      @foreach($days as $i=>$d)
        <label class="form-check form-check-inline">
          <input type="checkbox" name="weekdays[]" value="{{ $i }}"
            class="form-check-input"
            {{ in_array($i, $trip->weekdays ?? []) ? 'checked' : '' }}>
          {{ $d }}
        </label>
      @endforeach
    </div>
  </div>

  <!-- TRANSPORT TYPE -->
  <div class="mb-3">
    <label>{{ __('trips.form.transport_type') }}</label>
    <select name="transport_type" class="form-control" required>
      <option value="bus" {{ $trip->transport_type=='bus' ? 'selected' : '' }}>Bus</option>
      <option value="train" {{ $trip->transport_type=='train' ? 'selected' : '' }}>Train</option>
      <option value="air" {{ $trip->transport_type=='air' ? 'selected' : '' }}>Air</option>
      <option value="car" {{ $trip->transport_type=='car' ? 'selected' : '' }}>Car</option>
    </select>
  </div>

  <!-- STOPS -->
  <div class="mb-3">
    <label>{{ __('trips.form.stops') }}</label>
    <div id="stops_container"></div>
    <button type="button" id="add_stop" class="btn btn-secondary mt-2">{{ __('trips.form.add_stop') }}</button>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-danger">{{ __('trips.form.save') }}</button>

    {{-- If backend returned booking_conflict we also show a "Force update" button to open modal (optional) --}}
    @if(session('booking_conflict'))
      <button type="button" class="btn btn-warning" id="openConflictModalBtn">Resolve Booking Conflict</button>
    @endif
  </div>
</form>

{{-- BOOKING CONFLICT MODAL (Bootstrap 5) --}}
<div class="modal fade" id="bookingConflictModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Warning: Bookings Exist</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <p>
          {!! session('booking_conflict_message', 'Some seats on existing schedules are reserved/booked. If you regenerate schedules those bookings may be affected.') !!}
        </p>
        <p><strong>Please choose how you want to proceed:</strong></p>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="1" id="notifyPassengers" checked>
          <label class="form-check-label" for="notifyPassengers">
            Notify affected passengers by email (recommended)
          </label>
        </div>

        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" value="1" id="notifyProvider" checked>
          <label class="form-check-label" for="notifyProvider">
            Notify provider/agency admin by email
          </label>
        </div>

        <p class="mt-3 text-muted small">
          Note: after confirmation, all old schedules & seats for this trip will be removed and regenerated for the new dates. Bookings may be affected.
        </p>

        {{-- TODO: Add checkboxes/controls for SMS and Push notifications here in future --}}
        {{-- TODO: Provide "Export impacted bookings" link (generate CSV) --}}
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

        {{-- Confirm form will be populated by JS with all main form fields + force_update + notification flags --}}
        <form id="confirmForceUpdateForm" method="POST" action="{{ route('provider.trips.update', $trip->id) }}">
          @csrf
          @method('PUT')
          <input type="hidden" name="force_update" value="1">
          <input type="hidden" name="notify_passengers" id="notify_passengers_input" value="1">
          <input type="hidden" name="notify_provider" id="notify_provider_input" value="1">
          <button type="button" id="confirmForceBtn" class="btn btn-danger">Yes — Regenerate schedules & notify</button>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- JS: recurrence toggle, stops handling, modal copy-and-submit logic --}}
<script>
document.addEventListener('DOMContentLoaded', function() {

  // Recurrence toggle
  document.getElementById('recurrence').addEventListener('change', function(){
    document.getElementById('weekdays_wrapper').style.display = this.value === 'weekly' ? 'block' : 'none';
  });

  // Stops UI
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
            ${cities.map(c => `<option value="${c.id}" ${data.city_id==c.id?'selected':''}>${c.name}</option>`).join('')}
          </select>
        </div>
        <div class="col-3">
          <input type="time" name="stops[${idx}][arrival_time]" class="form-control" value="${data.arrival_time||''}">
        </div>
        <div class="col-3">
          <input type="time" name="stops[${idx}][departure_time]" class="form-control" value="${data.departure_time||''}">
        </div>
        <div class="col-1">
          <button type="button" class="btn btn-danger remove-stop">X</button>
        </div>
      </div>
    `;
    stopsContainer.appendChild(wrapper);
    wrapper.querySelector('.remove-stop').onclick = () => wrapper.remove();
  }

  // populate existing stops
  let existingStops = {!! $trip->stops->map(fn($s)=>[
      'city_id'=>$s->city_id,
      'arrival_time'=>$s->arrival_time,
      'departure_time'=>$s->departure_time
  ])->toJson() !!};

  existingStops.forEach(s => addStopRow(s));
  document.getElementById('add_stop').onclick = () => addStopRow();

  // If backend signalled conflict, show modal automatically
  @if(session('booking_conflict'))
    var bookingModalEl = document.getElementById('bookingConflictModal');
    var bookingModal = new bootstrap.Modal(bookingModalEl);
    bookingModal.show();
  @endif

  // If user clicks the optional "Resolve Booking Conflict" button, open modal
  var openConflictBtn = document.getElementById('openConflictModalBtn');
  if (openConflictBtn) {
    openConflictBtn.addEventListener('click', function() {
      var bookingModal2 = new bootstrap.Modal(document.getElementById('bookingConflictModal'));
      bookingModal2.show();
    });
  }

  // Modal confirm: copy main form inputs into confirm form, include notification flags, submit
  document.getElementById('confirmForceBtn').addEventListener('click', function() {
    var mainForm = document.getElementById('mainEditForm');
    var confirmForm = document.getElementById('confirmForceUpdateForm');

    // update notification hidden inputs from checkbox states
    document.getElementById('notify_passengers_input').value = document.getElementById('notifyPassengers').checked ? '1' : '0';
    document.getElementById('notify_provider_input').value = document.getElementById('notifyProvider').checked ? '1' : '0';

    // Remove previously appended dynamic inputs except _token, _method, force_update, notify_*.
    Array.from(confirmForm.querySelectorAll('input,select,textarea')).forEach(el=>{
      if (!['_token','_method','force_update','notify_passengers','notify_provider'].includes(el.name)) {
        el.remove();
      }
    });

    // Copy all named inputs from mainForm
    var elements = mainForm.querySelectorAll('input, select, textarea');
    elements.forEach(function(el){
      if (!el.name) return;
      if (['_token','_method'].includes(el.name)) return;

      var name = el.name;
      var type = el.type;

      if (el.tagName === 'SELECT') {
        // create hidden input with select value
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = name; h.value = el.value;
        confirmForm.appendChild(h);
      } else if (type === 'checkbox') {
        if (el.checked) {
          var h = document.createElement('input');
          h.type = 'hidden'; h.name = name; h.value = el.value;
          confirmForm.appendChild(h);
        }
      } else if (type === 'radio') {
        if (el.checked) {
          var h = document.createElement('input');
          h.type = 'hidden'; h.name = name; h.value = el.value;
          confirmForm.appendChild(h);
        }
      } else {
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = name; h.value = el.value;
        confirmForm.appendChild(h);
      }
    });

    // Submit confirm form
    confirmForm.submit();
  });

});
</script>

@endsection
