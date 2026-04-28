@extends('provider.layouts.app')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between mb-4">
        <h1 class="h4 text-gray-800">Add Chauffeur Route</h1>
        <a href="{{ route('provider.chauffeur.routes.index') }}"
           class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

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
          action="{{ route('provider.chauffeur.routes.store') }}">
        @csrf

        <div class="card shadow mb-4">
            <div class="card-header">
                Route Information
            </div>

            <div class="card-body row">

                <div class="col-md-6 mb-3">
                    <label>From Location</label>
                    <select name="from_city_id"
                            class="form-control"
                            required>
                        <option value="">Select Location</option>
                        @foreach($cities as $location)
                            <option value="{{ $location->id }}">
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>To Location</label>
                    <select name="to_city_id"
                            class="form-control"
                            required>
                        <option value="">Select Location</option>
                        @foreach($cities as $location)
                            <option value="{{ $location->id }}">
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Distance (KM)</label>
                    <input type="number"
                           step="0.01"
                           name="distance_km"
                           class="form-control"
                           placeholder="Optional">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Estimated Duration (Minutes)</label>
                    <input type="number"
                           name="estimated_duration_minutes"
                           class="form-control"
                           placeholder="Optional">
                </div>

                <div class="col-md-12">
                    <div class="form-check">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               class="form-check-input"
                               checked>
                        <label class="form-check-label">
                            Active Route
                        </label>
                    </div>
                </div>

            </div>
        </div>

        <div class="text-right">
            <button class="btn btn-primary px-4">
                Save Route
            </button>
        </div>

    </form>
</div>
@endsection