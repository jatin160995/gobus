@extends('provider.layouts.app')

@section('content')

<h2 class="mb-4">Edit Vehicle</h2>

<form action="{{ route('provider.vehicles.update', $vehicle->id) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="mb-3">
        <label>Plate Number</label>
        <input type="text" name="plate_number" class="form-control" value="{{ $vehicle->plate_number }}" required>
    </div>

    <div class="mb-3">
        <label>Model</label>
        <input type="text" name="model" class="form-control" value="{{ $vehicle->model }}" required>
    </div>

    <div class="mb-3">
        <label>Capacity</label>
        <input type="number" name="capacity" class="form-control" value="{{ $vehicle->capacity }}" required>
    </div>

    <div class="mb-3">
        <label>Comfort Type</label>
        <select name="comfort_type" class="form-control">
            <option value="standard" {{ $vehicle->comfort_type=='standard'?'selected':'' }}>Standard</option>
            <option value="vip" {{ $vehicle->comfort_type=='vip'?'selected':'' }}>VIP</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Main Photo (Featured)</label>
        <input type="file" name="photo" class="form-control">
        @if($vehicle->photo)
            <img src="{{ asset('storage/' . $vehicle->photo) }}" width="120" class="mt-2">
        @endif
    </div>

    {{-- ✅ Layout Image --}}
    <div class="mb-3">
        <label>Seating Layout Image</label>
        <input type="file" name="layout" class="form-control">

        @if($vehicle->layout)
            <div class="mt-2">
                <img src="{{ asset('storage/' . $vehicle->layout) }}" width="200">
            </div>
        @endif
    </div>

    <div class="mb-3">
        <label>Add More Gallery Images</label>
        <input type="file" name="images[]" class="form-control" multiple>
    </div>

    <button class="btn btn-danger">Update</button>
</form>

<hr>

<h5>Existing Images:</h5>

@if (count($vehicle->images) == 0)
    <h6 class="mb-5">No Gallery image</h6>
@endif

<div class="row">
    @foreach($vehicle->images as $img)
        <div class="col-md-2 text-center mb-3">

            <img src="{{ asset('storage/' . $img->image_path) }}" class="img-fluid mb-1">

            <form method="POST" action="{{ route('provider.provider.vehicle.image.delete', $img->id) }}">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-danger w-100">Delete</button>
            </form>

        </div>
    @endforeach
</div>

@endsection
