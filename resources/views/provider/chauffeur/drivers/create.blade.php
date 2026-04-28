@extends('provider.layouts.app')

@section('content')
<div class="container-fluid">

    <h1 class="h4 mb-4 text-gray-800">Add Driver</h1>

    <form method="POST"
          action="{{ route('provider.chauffeur.drivers.store') }}">
        @csrf

        <div class="card shadow mb-4">
            <div class="card-body row">

                <div class="col-md-6 mb-3">
                    <label>Name</label>
                    <input type="text" name="name"
                           class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Phone</label>
                    <input type="text" name="phone"
                           class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>License Number</label>
                    <input type="text" name="license_number"
                           class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>License Expiry</label>
                    <input type="date" name="license_expiry"
                           class="form-control" required>
                </div>

                <div class="col-md-12">
                    <div class="form-check">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               checked
                               class="form-check-input">
                        <label class="form-check-label">
                            Active Driver
                        </label>
                    </div>
                </div>

            </div>
        </div>

        <button class="btn btn-primary">Save Driver</button>

    </form>

</div>
@endsection