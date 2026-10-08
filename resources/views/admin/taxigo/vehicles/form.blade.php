@extends('admin.layouts.app')

@php $editing = $vehicle->exists; @endphp
@section('title', $editing ? __('taxigo.edit_vehicle') : __('taxigo.new_vehicle'))

@section('content')
<div class="go-page-head">
  <div>
    <a href="{{ route('admin.taxigo.vehicles.index') }}" class="small fw-semibold"><i class="fas fa-arrow-left mr-1"></i>{{ __('taxigo.vehicles') }}</a>
    <h1 class="go-page-title mt-2">{{ $editing ? $vehicle->model . ' · ' . $vehicle->plate : __('taxigo.new_vehicle') }}</h1>
  </div>
</div>

@include('admin.taxigo._flash')

<form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.taxigo.vehicles.update', $vehicle) : route('admin.taxigo.vehicles.store') }}" class="card">
  @csrf
  @if ($editing) @method('PUT') @endif

  <div class="form-section">
    <div><h3>{{ __('taxigo.vehicle') }}</h3></div>
    <div class="row g-3">
      <div class="col-md-6">
        <label for="model">{{ __('taxigo.model') }}</label>
        <input id="model" name="model" class="form-control" value="{{ old('model', $vehicle->model) }}" required maxlength="100">
      </div>
      <div class="col-md-6">
        <label for="plate">{{ __('taxigo.plate') }}</label>
        <input id="plate" name="plate" class="form-control text-uppercase" value="{{ old('plate', $vehicle->plate) }}" placeholder="LT 123 AB" required maxlength="20">
      </div>
      <div class="col-md-6">
        <label for="color">{{ __('taxigo.color') }} <span class="text-muted fw-normal">({{ __('taxigo.optional') }})</span></label>
        <input id="color" name="color" class="form-control" value="{{ old('color', $vehicle->color) }}" maxlength="50">
      </div>
      <div class="col-md-6">
        <label for="seats">{{ __('taxigo.seats') }}</label>
        <input id="seats" type="number" min="1" max="9" name="seats" class="form-control" value="{{ old('seats', $vehicle->seats) }}" required>
      </div>
      <div class="col-md-12">
        <label for="photo">{{ __('taxigo.photo') }}</label>
        <div class="d-flex align-items-center gap-3">
          @if ($vehicle->photo)<img class="photo-preview" src="{{ asset('storage/' . $vehicle->photo) }}" alt="">@endif
          <input id="photo" type="file" name="photo" accept="image/*" class="form-control">
        </div>
        <div class="form-hint">{{ __('taxigo.vehicle_photo_hint') }}</div>
      </div>
    </div>
  </div>

  <div class="form-section">
    <div><h3>{{ __('taxigo.assigned_driver') }}</h3></div>
    <div class="row g-3">
      <div class="col-md-6">
        <label for="driver_id">{{ __('taxigo.driver') }}</label>
        <select id="driver_id" name="driver_id" class="form-select">
          <option value="">{{ __('taxigo.no_driver') }}</option>
          @foreach ($drivers as $d)
            <option value="{{ $d->id }}" @selected(old('driver_id', $vehicle->driver_id) == $d->id)>{{ $d->user->name }} · {{ $d->hub->code }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <label class="switch-row mb-0" for="is_active">
          <input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $vehicle->is_active ?? true))>
          <span><span class="d-block fw-semibold text-gray-800">{{ __('taxigo.in_service') }}</span><span class="form-hint d-block mt-0">{{ __('taxigo.in_service_hint') }}</span></span>
        </label>
      </div>
    </div>
  </div>

  <div class="card-footer d-flex justify-content-end gap-2">
    <a href="{{ route('admin.taxigo.vehicles.index') }}" class="btn btn-secondary">{{ __('taxigo.cancel') }}</a>
    <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i> {{ $editing ? __('taxigo.save') : __('taxigo.add_vehicle') }}</button>
  </div>
</form>
@endsection
