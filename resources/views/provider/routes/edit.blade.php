@extends('provider.layouts.app')

@section('content')
<h3>{{ __('routes.edit.title') }}</h3>

<form action="{{ route('provider.routes.update', $route->id) }}" method="POST">
@csrf @method('PUT')

<div class="mb-3">
    <label>{{ __('routes.form.departure') }}</label>
    <select name="departure_city_id" class="form-control" required>
        @foreach($cities as $city)
        <option value="{{ $city->id }}" {{ $route->departure_city_id == $city->id ? 'selected' : '' }}>
            {{ $city->name }}
        </option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label>{{ __('routes.form.arrival') }}</label>
    <select name="arrival_city_id" class="form-control" required>
        @foreach($cities as $city)
        <option value="{{ $city->id }}" {{ $route->arrival_city_id == $city->id ? 'selected' : '' }}>
            {{ $city->name }}
        </option>
        @endforeach
    </select>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label>{{ __('routes.form.distance') }}</label>
        <input type="number" step="0.01" name="distance_km" value="{{ $route->distance_km }}" class="form-control">
    </div>

    <div class="col-md-4 mb-3">
        <label>{{ __('routes.form.duration') }}</label>
        <input type="number" name="duration_minutes" value="{{ $route->duration_minutes }}" class="form-control">
    </div>

    <div class="col-md-4 mb-3">
        <label>{{ __('routes.form.transport_type') }}</label>
        <select name="transport_type" class="form-control">
            <option value="bus" {{ $route->transport_type == 'bus' ? 'selected' : '' }}>Bus</option>
            <option value="train" {{ $route->transport_type == 'train' ? 'selected' : '' }}>Train</option>
            <option value="air" {{ $route->transport_type == 'air' ? 'selected' : '' }}>Air</option>
        </select>
    </div>
</div>

<button class="btn btn-danger">{{ __('routes.form.update') }}</button>
</form>
@endsection
