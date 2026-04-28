@extends('provider.layouts.app')

@section('content')
<h3>{{ __('routes.create.title') }}</h3>

<form action="{{ route('provider.routes.store') }}" method="POST">
@csrf

<div class="mb-3">
    <label>{{ __('routes.form.departure') }}</label>
    <select name="departure_city_id" class="form-control" required>
        <option value="">{{ __('routes.form.select_city') }}</option>
        @foreach($cities as $city)
        <option value="{{ $city->id }}">{{ $city->name }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label>{{ __('routes.form.arrival') }}</label>
    <select name="arrival_city_id" class="form-control" required>
        <option value="">{{ __('routes.form.select_city') }}</option>
        @foreach($cities as $city)
        <option value="{{ $city->id }}">{{ $city->name }}</option>
        @endforeach
    </select>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label>{{ __('routes.form.distance') }}</label>
        <input type="number" step="0.01" name="distance_km" class="form-control" required>
    </div>

    <div class="col-md-4 mb-3">
        <label>{{ __('routes.form.duration') }}</label>
        <input type="number" name="duration_minutes" class="form-control" required>
    </div>

    <div class="col-md-4 mb-3">
        <label>{{ __('routes.form.transport_type') }}</label>
        <select name="transport_type" class="form-control">
            <option value="bus">Bus</option>
            <option value="train">Train</option>
            <option value="air">Air</option>
            <option value="car">Car</option>
        </select>
    </div>
</div>

<button class="btn btn-danger">{{ __('routes.form.save') }}</button>
</form>
@endsection
