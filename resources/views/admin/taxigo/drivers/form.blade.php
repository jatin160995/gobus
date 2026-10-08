@extends('admin.layouts.app')

@php $editing = $driver->exists; @endphp
@section('title', $editing ? __('taxigo.edit_driver') : __('taxigo.new_driver'))

@section('content')
<div class="go-page-head">
  <div>
    <a href="{{ route('admin.taxigo.drivers.index') }}" class="small fw-semibold"><i class="fas fa-arrow-left mr-1"></i>{{ __('taxigo.drivers') }}</a>
    <h1 class="go-page-title mt-2">{{ $editing ? $driver->user->name : __('taxigo.new_driver') }}</h1>
  </div>
</div>

@include('admin.taxigo._flash')

<form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.taxigo.drivers.update', $driver) : route('admin.taxigo.drivers.store') }}" class="card">
  @csrf
  @if ($editing) @method('PUT') @endif

  <div class="form-section">
    <div><h3>{{ __('taxigo.identity') }}</h3></div>
    <div class="row g-3">
      <div class="col-md-6">
        <label for="name">{{ __('taxigo.name') }}</label>
        <input id="name" name="name" class="form-control" value="{{ old('name', $driver->user?->name) }}" required maxlength="150">
      </div>
      <div class="col-md-6">
        <label for="hub_id">{{ __('taxigo.hub') }}</label>
        <select id="hub_id" name="hub_id" class="form-select" required>
          @foreach ($hubs as $hub)<option value="{{ $hub->id }}" @selected(old('hub_id', $driver->hub_id) == $hub->id)>{{ $hub->code }} · {{ $hub->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-6">
        <label for="licence_number">{{ __('taxigo.licence') }} <span class="text-muted fw-normal">({{ __('taxigo.optional') }})</span></label>
        <input id="licence_number" name="licence_number" class="form-control" value="{{ old('licence_number', $driver->licence_number) }}" maxlength="50">
      </div>
      <div class="col-md-6">
        <label for="photo">{{ __('taxigo.photo') }}</label>
        <div class="d-flex align-items-center gap-3">
          @if ($driver->photo)<img class="photo-preview" src="{{ asset('storage/' . $driver->photo) }}" alt="">@endif
          <input id="photo" type="file" name="photo" accept="image/*" class="form-control">
        </div>
        <div class="form-hint">{{ __('taxigo.photo_hint') }}</div>
      </div>
    </div>
  </div>

  <div class="form-section">
    <div><h3>{{ __('taxigo.login') }}</h3></div>
    <div class="row g-3">
      <div class="col-md-6">
        <label for="phone">{{ __('taxigo.phone') }}</label>
        <input id="phone" type="tel" name="phone" class="form-control" value="{{ old('phone', $driver->user?->phone) }}" placeholder="6XX XX XX XX" required>
      </div>
      <div class="col-md-6">
        <label for="email">{{ __('taxigo.email') }} <span class="text-muted fw-normal">({{ __('taxigo.optional') }})</span></label>
        <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $driver->user?->email) }}">
      </div>
      <div class="col-md-6">
        <label for="password">{{ __('taxigo.password') }}</label>
        <input id="password" type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" @required(!$editing)
               placeholder="{{ $editing ? __('taxigo.password_keep') : '' }}">
        <div class="form-hint">{{ __('taxigo.password_hint') }}</div>
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <label class="switch-row mb-0" for="is_active">
          <input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $driver->is_active ?? true))>
          <span><span class="d-block fw-semibold text-gray-800">{{ __('taxigo.account_active') }}</span><span class="form-hint d-block mt-0">{{ __('taxigo.account_active_hint') }}</span></span>
        </label>
      </div>
    </div>
  </div>

  <div class="form-section">
    <div><h3>{{ __('taxigo.payout') }}</h3><p class="hint">{{ __('taxigo.payout_hint') }}</p></div>
    <div class="row g-3">
      <div class="col-md-6">
        <label for="payout_channel">{{ __('taxigo.payout_channel') }}</label>
        <select id="payout_channel" name="payout_channel" class="form-select" required>
          @foreach (['mtn', 'orange'] as $ch)<option value="{{ $ch }}" @selected(old('payout_channel', $driver->payout_channel) === $ch)>{{ __('taxigo.channels')[$ch] }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-6">
        <label for="payout_msisdn">{{ __('taxigo.payout_number') }}</label>
        <input id="payout_msisdn" type="tel" name="payout_msisdn" class="form-control" value="{{ old('payout_msisdn', $driver->payout_msisdn) }}" placeholder="6XX XX XX XX" required>
      </div>
    </div>
  </div>

  <div class="card-footer d-flex justify-content-end gap-2">
    <a href="{{ route('admin.taxigo.drivers.index') }}" class="btn btn-secondary">{{ __('taxigo.cancel') }}</a>
    <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i> {{ $editing ? __('taxigo.save') : __('taxigo.add_driver') }}</button>
  </div>
</form>
@endsection
