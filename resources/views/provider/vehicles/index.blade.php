@extends('provider.layouts.app')

@section('content')

<h2 class="mb-4">My Vehicles</h2>

<a href="{{ route('provider.vehicles.create') }}" class="btn btn-danger mb-3">Add New Vehicle</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Photo</th>
            <th>Plate Number</th>
            <th>Model</th>
            <th>Capacity</th>
            <th>Comfort</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($vehicles as $vehicle)
        <tr>
            <td>
                @if($vehicle->photo)
                    <img src="{{ asset('storage/' . $vehicle->photo) }}" width="80">
                @endif
            </td>
            <td>{{ $vehicle->plate_number }}</td>
            <td>{{ $vehicle->model }}</td>
            <td>{{ $vehicle->capacity }}</td>
            <td>{{ ucfirst($vehicle->comfort_type) }}</td>
            <td>
                <a href="{{ route('provider.vehicles.edit', $vehicle->id) }}" class="btn btn-sm btn-primary">Edit</a>
                <form action="{{ route('provider.vehicles.destroy', $vehicle->id) }}" method="POST" style="display:inline;">
                    @csrf @method('DELETE')
                    <button onclick="return confirm('Delete this vehicle?')" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{ $vehicles->links() }}

@endsection
