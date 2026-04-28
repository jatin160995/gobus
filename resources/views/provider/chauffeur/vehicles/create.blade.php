@extends('provider.layouts.app')
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@section('content')
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-0 text-gray-800">Add Chauffeur Vehicle</h1>
        <a href="{{ route('provider.chauffeur.vehicles.index') }}"
           class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back
        </a>
    </div>

    <form method="POST"
          action="{{ route('provider.chauffeur.vehicles.store') }}"
          enctype="multipart/form-data">
        @csrf

        {{-- ================= VEHICLE INFO CARD ================= --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Vehicle Information
                </h6>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control" required>
                            <option value="standard">Standard</option>
                            <option value="executive">Executive</option>
                            <option value="premium">Premium</option>
                            <option value="suv">SUV</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Seats</label>
                        <input type="number" name="seats" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Plate Number</label>
                        <input type="text" name="plate_number" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Color</label>
                        <input type="text" name="color" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Transmission</label>
                        <select name="transmission" class="form-control">
                            <option value="automatic">Automatic</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        {{-- ================= IMAGES CARD ================= --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    Vehicle Images
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
                            <select name="image_types[]" class="form-control" required>
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
                                   class="form-control"
                                   required>
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

        {{-- ================= STATUS & ACTION ================= --}}
        <div class="card shadow mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div class="form-check form-switch">
                    <input type="checkbox"
                           class="form-check-input"
                           name="is_active"
                           value="1"
                           checked>
                    <label class="form-check-label">
                        Active Vehicle
                    </label>
                </div>

                <div>
                    <a href="{{ route('provider.chauffeur.vehicles.index') }}"
                       class="btn btn-secondary">
                        Cancel
                    </a>

                    <button type="submit"
                            class="btn btn-primary">
                        Save Vehicle
                    </button>
                </div>

            </div>
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