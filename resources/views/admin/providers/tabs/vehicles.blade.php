@extends('admin.providers.show')

@section('provider_tab')

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">{{ __('provider_show.vehicles_tab') }}</h5>
    </div>

    <div class="card-body">
        @if($vehicles->count())
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Photo</th>
                        <th>Vehicle No</th>
                        <th>Model</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicles as $v)
                        <tr>
                            <td>{{ $v->id }}</td>
                            <td>
                                @if($v->photo)
                                    <img src="{{ asset('storage/' . $v->photo) }}" width="80">
                                @endif
                            </td>
                            <td>{{ $v->plate_number }}</td>
                            <td>{{ $v->model }}</td>
                            <td>{{ ucfirst($v->comfort_type) }}</td>
                            <td>{{ ucfirst($v->capacity) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted mb-0">No vehicles found.</p>
        @endif
    </div>
</div>

@endsection
