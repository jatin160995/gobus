@extends('admin.layouts.app')

@section('content')

<div class="container-fluid">

    <h3 class="mb-4">Add New City</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('admin.cities.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>City Code *</label>
                        <input type="text" name="city_code" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Country</label>
                        <input type="text" name="country" class="form-control">
                    </div>
                </div>

                <button class="btn btn-primary">Save City</button>

            </form>

        </div>
    </div>

</div>

@endsection
