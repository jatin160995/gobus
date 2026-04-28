@extends('provider.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>{{ __('trips.index.title') }}</h3>
    <a href="{{ route('provider.trips.create') }}" class="btn btn-danger">{{ __('trips.index.create') }}</a>
</div>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-striped">
  <thead>
    <tr>
      <th>#</th>
      <th>{{ __('trips.form.route') }}</th>
      <th>{{ __('trips.form.vehicle') }}</th>
      <th>{{ __('trips.form.departure_time') }}</th>
      <th>{{ __('trips.form.recurrence') }}</th>
      <th>{{ __('trips.form.start_date') }}</th>
      <th>{{ __('trips.form.end_date') }}</th>
      <th>{{ __('trips.form.actions') }}</th>
    </tr>
  </thead>

  <tbody>
    @foreach($trips as $trip)
    <tr>
      <td>{{ $trip->id }}</td>

      <td>
        {{ optional($trip->route->departureCity)->name }} →
        {{ optional($trip->route->arrivalCity)->name }}
      </td>

      <td>{{ optional($trip->vehicle)->plate_number ?? '-' }}</td>

      <td>{{ $trip->departure_datetime?->format('H:i') ?? '-' }}</td>

      <td>{{ ucfirst($trip->recurrence) }}</td>

      <!-- START DATE -->
      <td>
        {{ $trip->start_date ? \Carbon\Carbon::parse($trip->start_date)->format('d M Y') : '-' }}
      </td>

      <!-- END DATE -->
      <td>
        {{ $trip->end_date ? \Carbon\Carbon::parse($trip->end_date)->format('d M Y') : '-' }}
      </td>

      <td>
        <a href="{{ route('provider.trips.edit', $trip->id) }}" class="btn btn-sm btn-primary">
            {{ __('trips.index.edit') }}
        </a>

        <form method="POST" action="{{ route('provider.trips.destroy', $trip->id) }}" style="display:inline;">
            @csrf @method('DELETE')
            <button onclick="return confirm('{{ __('trips.index.delete_confirm') }}')" class="btn btn-sm btn-danger">
                {{ __('trips.index.delete') }}
            </button>
        </form>
      </td>
    </tr>
    @endforeach
  </tbody>

</table>

{{ $trips->links() }}

@endsection
