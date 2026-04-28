@extends('admin.layouts.app')

@section('content')

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit mr-2" style="color:#FF8C00;"></i>
            {{ __('provider_edit.page_title') }}
        </h1>
        <a href="{{ route('admin.providers.show', $provider->id) }}" class="btn btn-secondary btn-sm shadow-sm">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> {{ __('provider_edit.back') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <strong><i class="fas fa-exclamation-triangle mr-1"></i> {{ __('provider_edit.validation_error') }}</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form action="{{ route('admin.providers.update', $provider->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('POST')

        <div class="row">

            <!-- LEFT COLUMN: Logo & Identity -->
            <div class="col-xl-4 col-lg-5 mb-4">

                <!-- Logo Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3" style="border-left: 4px solid #FF8C00;">
                        <h6 class="m-0 font-weight-bold" style="color:#FF8C00;">
                            <i class="fas fa-image mr-1"></i> {{ __('provider_edit.logo_section') }}
                        </h6>
                    </div>
                    <div class="card-body text-center">

                        <!-- Logo Preview -->
                        <div class="mb-3">
                            <img id="logoPreview"
                                src="{{ $provider->logo ? asset('storage/' . $provider->logo) : 'https://via.placeholder.com/160x160?text=No+Logo' }}"
                                class="rounded shadow"
                                style="width:160px; height:160px; object-fit:cover; border: 3px solid #e3e6f0;">
                        </div>

                        <label class="btn btn-sm" style="background-color:#FF8C00; color:#fff; border:none; cursor:pointer;">
                            <i class="fas fa-upload mr-1"></i> {{ __('provider_edit.choose_logo') }}
                            <input type="file" name="logo" id="logoInput" accept="image/*" class="d-none">
                        </label>

                        <p class="text-muted mt-2 mb-0" style="font-size:0.78rem;">
                            {{ __('provider_edit.logo_hint') }}
                        </p>

                        @error('logo')
                            <div class="text-danger mt-1" style="font-size:0.82rem;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Status & Type Card -->
                <div class="card shadow-sm">
                    <div class="card-header py-3" style="border-left: 4px solid #00829F;">
                        <h6 class="m-0 font-weight-bold" style="color:#00829F;">
                            <i class="fas fa-sliders-h mr-1"></i> {{ __('provider_edit.settings_section') }}
                        </h6>
                    </div>
                    <div class="card-body">

                        <!-- Status -->
                        <div class="form-group">
                            <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                {{ __('provider_edit.status') }} <span class="text-danger">*</span>
                            </label>
                            <select name="status" class="form-control form-control-sm @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', $provider->status) == 'active' ? 'selected' : '' }}>
                                    {{ __('provider_edit.status_active') }}
                                </option>
                                <option value="inactive" {{ old('status', $provider->status) == 'inactive' ? 'selected' : '' }}>
                                    {{ __('provider_edit.status_inactive') }}
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Provider Type -->
                        <div class="form-group">
                            <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                {{ __('provider_edit.type') }} <span class="text-danger">*</span>
                            </label>
                            <select name="type" class="form-control form-control-sm @error('type') is-invalid @enderror">
                                @foreach(['bus','train','air','car'] as $type)
                                    <option value="{{ $type }}" {{ old('type', $provider->type) == $type ? 'selected' : '' }}>
                                        {{ __('provider_edit.types.' . $type) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Commission Rate -->
                        <div class="form-group">
                            <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                {{ __('provider_edit.commission_rate') }} (%)
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number"
                                    name="commission_rate"
                                    value="{{ old('commission_rate', $provider->commission_rate) }}"
                                    min="0" max="100" step="0.01"
                                    class="form-control @error('commission_rate') is-invalid @enderror"
                                    placeholder="0.00">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('commission_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Payout Method -->
                        <div class="form-group mb-0">
                            <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                {{ __('provider_edit.payout_method') }}
                            </label>
                            <select name="payout_method" class="form-control form-control-sm @error('payout_method') is-invalid @enderror">
                                <option value="manual" {{ old('payout_method', $provider->payout_method) == 'manual' ? 'selected' : '' }}>
                                    {{ __('provider_edit.payout_manual') }}
                                </option>
                                <option value="auto" {{ old('payout_method', $provider->payout_method) == 'auto' ? 'selected' : '' }}>
                                    {{ __('provider_edit.payout_auto') }}
                                </option>
                            </select>
                            @error('payout_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

            </div><!-- /LEFT COLUMN -->

            <!-- RIGHT COLUMN: Main Details -->
            <div class="col-xl-8 col-lg-7 mb-4">

                <!-- Agency Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3" style="border-left: 4px solid #FF8C00;">
                        <h6 class="m-0 font-weight-bold" style="color:#FF8C00;">
                            <i class="fas fa-building mr-1"></i> {{ __('provider_edit.agency_section') }}
                        </h6>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <!-- Provider Name -->
                            <div class="col-12 form-group">
                                <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                    {{ __('provider_edit.name') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    name="name"
                                    value="{{ old('name', $provider->name) }}"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="{{ __('provider_edit.name_placeholder') }}"
                                    required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Address -->
                            <div class="col-12 form-group mb-0">
                                <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                    {{ __('provider_edit.address') }}
                                </label>
                                <textarea name="address"
                                    rows="2"
                                    class="form-control @error('address') is-invalid @enderror"
                                    placeholder="{{ __('provider_edit.address_placeholder') }}">{{ old('address', $provider->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Contact Person -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3" style="border-left: 4px solid #00829F;">
                        <h6 class="m-0 font-weight-bold" style="color:#00829F;">
                            <i class="fas fa-user-tie mr-1"></i> {{ __('provider_edit.contact_section') }}
                        </h6>
                    </div>
                    <div class="card-body">

                        <div class="row">

                            <!-- Contact Person -->
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                    {{ __('provider_edit.contact_person') }}
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user fa-sm"></i></span>
                                    </div>
                                    <input type="text"
                                        name="contact_person"
                                        value="{{ old('contact_person', $provider->contact_person) }}"
                                        class="form-control @error('contact_person') is-invalid @enderror"
                                        placeholder="{{ __('provider_edit.contact_person_placeholder') }}">
                                    @error('contact_person')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                    {{ __('provider_edit.phone') }}
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone fa-sm"></i></span>
                                    </div>
                                    <input type="text"
                                        name="phone"
                                        value="{{ old('phone', $provider->phone) }}"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        placeholder="{{ __('provider_edit.phone_placeholder') }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6 form-group mb-md-0">
                                <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                    {{ __('provider_edit.email') }}
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope fa-sm"></i></span>
                                    </div>
                                    <input type="email"
                                        name="email"
                                        value="{{ old('email', $provider->email) }}"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="{{ __('provider_edit.email_placeholder') }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Orange MSISDN -->
                            <div class="col-md-6 form-group mb-0">
                                <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                    {{ __('provider_edit.orange_msisdn') }}
                                    <small class="text-muted font-weight-normal">({{ __('provider_edit.orange_msisdn_hint') }})</small>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" style="background-color:#ff6600; color:#fff; border-color:#ff6600;">
                                            <i class="fas fa-mobile-alt fa-sm"></i>
                                        </span>
                                    </div>
                                    <input type="text"
                                        name="orange_msisdn"
                                        value="{{ old('orange_msisdn', $provider->orange_msisdn) }}"
                                        class="form-control @error('orange_msisdn') is-invalid @enderror"
                                        placeholder="{{ __('provider_edit.orange_msisdn_placeholder') }}">
                                    @error('orange_msisdn')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- MTN MSISDN -->
                            <div class="col-md-6 form-group mb-0 mt-2">
                            </div>
                            <div class="col-md-6 form-group mb-0">
                                <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                    {{ __('provider_edit.mtn_msisdn') }}
                                    <small class="text-muted font-weight-normal">({{ __('provider_edit.mtn_msisdn_hint') }})</small>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" style="background-color:#00829F; color:#fff; border-color:#00829F;">
                                            <i class="fas fa-mobile-alt fa-sm"></i>
                                        </span>
                                    </div>
                                    <input type="text"
                                        name="mtn_msisdn"
                                        value="{{ old('mtn_msisdn', $provider->mtn_msisdn) }}"
                                        class="form-control @error('mtn_msisdn') is-invalid @enderror"
                                        placeholder="{{ __('provider_edit.mtn_msisdn_placeholder') }}">
                                    @error('mtn_msisdn')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <a href="{{ route('admin.providers.show', $provider->id) }}"
                            class="btn btn-secondary">
                            <i class="fas fa-times mr-1"></i> {{ __('provider_edit.cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary-app px-4">
                            <i class="fas fa-save mr-1"></i> {{ __('provider_edit.save') }}
                        </button>
                    </div>
                </div>

            </div><!-- /RIGHT COLUMN -->

        </div><!-- /row -->
    </form>

</div>

@endsection

@push('scripts')
<script>
    // Live logo preview
    document.getElementById('logoInput').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('{{ __("provider_edit.logo_type_error") }}');
            this.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert('{{ __("provider_edit.logo_size_error") }}');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (evt) {
            document.getElementById('logoPreview').src = evt.target.result;
        };
        reader.readAsDataURL(file);
    });
</script>
@endpush