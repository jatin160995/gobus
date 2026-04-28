@extends('provider.layouts.app')

@section('content')

<h2 class="mb-4">Add Vehicle</h2>

<form action="{{ route('provider.vehicles.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="mb-3">
        <label>Plate Number</label>
        <input type="text" name="plate_number" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Model</label>
        <input type="text" name="model" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Capacity</label>
        <input type="number" name="capacity" class="form-control" value="50" required>
    </div>

    <div class="mb-3">
        <label>Comfort Type</label>
        <select name="comfort_type" class="form-control">
            <option value="standard">Standard</option>
            <option value="vip">VIP</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Main Photo (Featured)</label>
        <input type="file" name="photo" class="form-control">
    </div>

    {{-- ✅ NEW Layout Image Field --}}
    <div class="mb-3">
        <label>Seating Layout Image</label>
        <input type="file" name="layout" class="form-control">
        <small class="text-muted">
            Upload bus seating layout image (for seat reference).
        </small>
    </div>

    <div class="mb-3">
        <label>Gallery Images (Multiple)</label>
        <input type="file" name="images[]" class="form-control" multiple>
    </div>

    <button class="btn btn-danger">Save</button>
</form>

@endsection
