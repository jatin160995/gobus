@extends('provider.layouts.app')

@section('content')
<div class="container-fluid">

    {{-- Page Heading --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-0 text-gray-800">Edit Chauffeur Vehicle</h1>
        <a href="{{ route('provider.chauffeur.vehicles.index') }}"
           class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back
        </a>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('provider.chauffeur.vehicles.update', $vehicle->id) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- ================= VEHICLE INFO ================= --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Vehicle Information
                </h6>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Brand</label>
                        <input type="text" name="brand"
                               value="{{ old('brand', $vehicle->brand) }}"
                               class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Model</label>
                        <input type="text" name="model"
                               value="{{ old('model', $vehicle->model) }}"
                               class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Year</label>
                        <input type="number" name="year"
                               value="{{ old('year', $vehicle->year) }}"
                               class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Category</label>
                        <select name="category" class="form-control" required>
                            <option value="standard" {{ $vehicle->category=='standard'?'selected':'' }}>Standard</option>
                            <option value="executive" {{ $vehicle->category=='executive'?'selected':'' }}>Executive</option>
                            <option value="premium" {{ $vehicle->category=='premium'?'selected':'' }}>Premium</option>
                            <option value="suv" {{ $vehicle->category=='suv'?'selected':'' }}>SUV</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Seats</label>
                        <input type="number" name="seats"
                               value="{{ old('seats', $vehicle->seats) }}"
                               class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Plate Number</label>
                        <input type="text" name="plate_number"
                               value="{{ old('plate_number', $vehicle->plate_number) }}"
                               class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Color</label>
                        <input type="text" name="color"
                               value="{{ old('color', $vehicle->color) }}"
                               class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Transmission</label>
                        <select name="transmission" class="form-control">
                            <option value="automatic" {{ $vehicle->transmission=='automatic'?'selected':'' }}>Automatic</option>
                            <option value="manual" {{ $vehicle->transmission=='manual'?'selected':'' }}>Manual</option>
                        </select>
                    </div>

                    <div class="col-md-6 d-flex align-items-center mt-4">
                        <div class="form-check">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   class="form-check-input"
                                   {{ $vehicle->is_active ? 'checked' : '' }}>
                            <label class="form-check-label">
                                Active Vehicle
                            </label>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ================= EXISTING IMAGES ================= --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Existing Images
                </h6>
            </div>

            <div class="card-body">
                <div class="row">
                    @foreach($vehicle->images as $image)
                        <div class="col-md-3 mb-4 text-center">
                            <div class="card border-0 shadow-sm">
                                <img src="{{ asset('storage/'.$image->image_path) }}"
                                     class="img-fluid rounded-top"
                                     style="height:150px;object-fit:cover;">
                                <div class="card-body p-2">
                                    <small class="text-muted d-block mb-2">
                                        {{ ucfirst(str_replace('_',' ',$image->type)) }}
                                    </small>

                                    <div class="form-check">
                                        <input type="checkbox"
                                               name="delete_images[]"
                                               value="{{ $image->id }}"
                                               class="form-check-input">
                                        <label class="form-check-label text-danger">
                                            Delete
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ================= ADD NEW IMAGES ================= --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    Add New Images
                </h6>
                <button type="button"
                        class="btn btn-sm btn-primary"
                        onclick="addImageRow()">
                    + Add Image
                </button>
            </div>

            <div class="card-body">
                <div id="image-container">

                    <div class="row image-row mb-3">
                        <div class="col-md-4">
                            <select name="image_types[]" class="form-control">
                                <option value="">Select Type</option>
                                <option value="front">Front</option>
                                <option value="rear">Rear</option>
                                <option value="interior">Interior</option>
                                <option value="left_side">Left Side</option>
                                <option value="right_side">Right Side</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <input type="file"
                                   name="images[]"
                                   class="form-control">
                        </div>

                        <div class="col-md-2">
                            <button type="button"
                                    class="btn btn-danger btn-block"
                                    onclick="removeImageRow(this)">
                                Remove
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ================= SUBMIT ================= --}}
        <div class="text-right mb-5">
            <button type="submit" class="btn btn-primary px-4">
                Update Vehicle
            </button>
        </div>

    </form>
</div>

<script>
function addImageRow() {
    let container = document.getElementById('image-container');
    let row = container.querySelector('.image-row').cloneNode(true);
    row.querySelectorAll('input').forEach(input => input.value = '');
    row.querySelector('select').selectedIndex = 0;
    container.appendChild(row);
}

function removeImageRow(button) {
    let rows = document.querySelectorAll('.image-row');
    if (rows.length > 1) {
        button.closest('.image-row').remove();
    }
}
</script>

@endsection