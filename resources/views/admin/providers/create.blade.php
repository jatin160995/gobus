@extends('admin.layouts.app')

@section('content')

<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-4">{{ __('provider_create.add_new_provider') }}</h3>
        <a href="{{ route('admin.providers.list') }}" class="btn btn-secondary btn-sm">
            ← {{ __('provider_create.back_to_providers') }}
        </a>
    </div>
    

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('admin.providers.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('provider_create.provider_name') }} *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('provider_create.contact_person') }}</label>
                        <input type="text" name="contact_person" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('provider_create.phone') }}</label>
                        <input type="text" name="phone" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('provider_create.email') }}</label>
                        <input type="email" name="email" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('provider_create.commission_rate') }} (%) *</label>
                        <input type="number" step="0.01" name="commission_rate" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('provider_create.payout_method') }} *</label>
                        <select name="payout_method" class="form-control">
                            <option value="manual">{{ __('provider_create.methods.manual') }}</option>
                            <option value="auto">{{ __('provider_create.methods.auto') }}</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Orange msisdn </label>
                        <input type="text"  name="orange_msisdn" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">MTN msisdn </label>
                        <input type="text"  name="mtn_msisdn" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('provider_create.provider_type') }} *</label>
                        <select name="type" class="form-control">
                            <option value="bus">{{ __('provider_create.types.bus') }}</option>
                            <option value="car">{{ __('provider_create.types.car') }}</option>
                            <option value="train">{{ __('provider_create.types.train') }}</option>
                            <option value="air">{{ __('provider_create.types.air') }}</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('provider_create.status') }} *</label>
                        <select name="status" class="form-control">
                            <option value="active">{{ __('provider_create.statuses.active') }}</option>
                            <option value="inactive">{{ __('provider_create.statuses.inactive') }}</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('provider_create.address') }}</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('provider_create.logo') }}</label>
                        <input type="file" name="logo" class="form-control">
                    </div>

                </div>

                <button type="submit" class="btn btn-primary">{{ __('provider_create.save_provider') }}</button>

            </form>

        </div>
    </div>

</div>

@endsection
