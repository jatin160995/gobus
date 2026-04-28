@extends('provider.layouts.app')

@section('title', __('provider/profile.edit_profile'))

@section('content')

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-edit mr-2"></i>{{ __('provider/profile.edit_profile') }}
        </h1>
        <a href="{{ route('provider.dashboard') }}"
           class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i>
            {{ __('provider/profile.back_to_dashboard') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="row">

        {{-- ===== LEFT COLUMN: Overview + Quick Nav ===== --}}
        <div class="col-xl-4 col-lg-5 mb-4">

            <!-- Profile Overview Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-id-card mr-2"></i>{{ __('provider/profile.profile_overview') }}
                    </h6>
                </div>
                <div class="card-body text-center">

                    <!-- Avatar / Logo -->
                    <div class="mb-3">
                        @if($provider && $provider->logo)
                            <img src="{{ asset('storage/' . $provider->logo) }}"
                                 alt="{{ $provider->name }}"
                                 class="rounded-circle shadow"
                                 style="width:80px;height:80px;object-fit:cover;">
                        @else
                            <div class="rounded-circle bg-primary-app d-inline-flex align-items-center justify-content-center shadow"
                                 style="width:80px;height:80px;font-size:2rem;color:#fff;font-weight:700;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <h5 class="font-weight-bold text-gray-800 mb-0">{{ auth()->user()->name }}</h5>
                    <p class="text-muted small mb-1">{{ auth()->user()->email ?? '—' }}</p>
                    <p class="text-muted small mb-1">{{ auth()->user()->phone ?? '—' }}</p>
                    <span class="badge badge-{{ auth()->user()->status === 'active' ? 'success' : 'danger' }} mt-1">
                        {{ ucfirst(auth()->user()->status) }}
                    </span>

                    @if($provider)
                        <hr class="my-3">
                        <div class="text-left px-2">
                            <p class="mb-1 small">
                                <i class="fas fa-building text-primary mr-2"></i>
                                <strong>{{ __('provider/profile.agency') }}:</strong> {{ $provider->name }}
                            </p>
                            <p class="mb-1 small">
                                <i class="fas fa-tag text-primary mr-2"></i>
                                <strong>{{ __('provider/profile.type') }}:</strong>
                                <span class="badge badge-info">{{ ucfirst($provider->type) }}</span>
                            </p>
                            <p class="mb-1 small">
                                <i class="fas fa-user-tie text-primary mr-2"></i>
                                <strong>{{ __('provider/profile.role') }}:</strong> {{ ucfirst($providerRole) }}
                            </p>
                            <p class="mb-1 small">
                                <i class="fas fa-circle text-{{ $provider->status === 'active' ? 'success' : 'danger' }} mr-2"></i>
                                <strong>{{ __('provider/profile.status') }}:</strong> {{ ucfirst($provider->status) }}
                            </p>
                            @if($provider->address)
                                <p class="mb-1 small">
                                    <i class="fas fa-map-marker-alt text-primary mr-2"></i>
                                    {{ $provider->address }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Navigation Card -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-link mr-2"></i>{{ __('provider/profile.quick_navigation') }}
                    </h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#personal-info" class="list-group-item list-group-item-action py-2 small">
                        <i class="fas fa-user mr-2 text-gray-400"></i>
                        {{ __('provider/profile.personal_information') }}
                    </a>
                    @if($provider && $providerRole === 'manager')
                        <a href="#agency-info" class="list-group-item list-group-item-action py-2 small">
                            <i class="fas fa-building mr-2 text-gray-400"></i>
                            {{ __('provider/profile.agency_information') }}
                        </a>
                    @endif
                    <a href="#change-password" class="list-group-item list-group-item-action py-2 small">
                        <i class="fas fa-lock mr-2 text-gray-400"></i>
                        {{ __('provider/profile.change_password') }}
                    </a>
                </div>
            </div>

        </div>{{-- end left col --}}

        {{-- ===== RIGHT COLUMN: Forms ===== --}}
        <div class="col-xl-8 col-lg-7">

            {{-- ---- 1. Personal Information ---- --}}
            <div class="card shadow mb-4" id="personal-info">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user mr-2"></i>{{ __('provider/profile.personal_information') }}
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('provider.profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name">
                                    {{ __('provider/profile.name') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name"
                                       value="{{ old('name', auth()->user()->name) }}"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email">{{ __('provider/profile.email') }}</label>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email"
                                       value="{{ old('email', auth()->user()->email) }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="phone">{{ __('provider/profile.phone') }}</label>
                                <input type="text"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone"
                                       value="{{ old('phone', auth()->user()->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary-app">
                            <i class="fas fa-save mr-1"></i>{{ __('provider/profile.save_personal_info') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- ---- 2. Agency / Provider Information (managers only) ---- --}}
            @if($provider && $providerRole === 'manager')
            <div class="card shadow mb-4" id="agency-info">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-building mr-2"></i>{{ __('provider/profile.agency_information') }}
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('provider.profile.agency') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Logo Upload -->
                        <div class="form-group">
                            <label>{{ __('provider/profile.agency_logo') }}</label>
                            <div class="d-flex align-items-center mb-2">
                                @if($provider->logo)
                                    <img src="{{ asset('storage/' . $provider->logo) }}"
                                         alt="{{ __('provider/profile.agency_logo') }}"
                                         class="rounded mr-3 shadow-sm"
                                         style="width:64px;height:64px;object-fit:cover;">
                                @else
                                    <div class="rounded bg-light d-flex align-items-center justify-content-center mr-3 text-muted"
                                         style="width:64px;height:64px;font-size:1.5rem;">
                                        <i class="fas fa-image"></i>
                                    </div>
                                @endif
                                <div>
                                    <input type="file"
                                           class="form-control-file @error('logo') is-invalid @enderror"
                                           name="logo" accept="image/*">
                                    <small class="text-muted">{{ __('provider/profile.logo_hint') }}</small>
                                    @error('logo')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="agency_name">
                                    {{ __('provider/profile.agency_name') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('agency_name') is-invalid @enderror"
                                       id="agency_name" name="agency_name"
                                       value="{{ old('agency_name', $provider->name) }}"
                                       required>
                                @error('agency_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="agency_email">{{ __('provider/profile.agency_email') }}</label>
                                <input type="email"
                                       class="form-control @error('agency_email') is-invalid @enderror"
                                       id="agency_email" name="agency_email"
                                       value="{{ old('agency_email', $provider->email) }}">
                                @error('agency_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="agency_phone">{{ __('provider/profile.agency_phone') }}</label>
                                <input type="text"
                                       class="form-control @error('agency_phone') is-invalid @enderror"
                                       id="agency_phone" name="agency_phone"
                                       value="{{ old('agency_phone', $provider->phone) }}">
                                @error('agency_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="contact_person">{{ __('provider/profile.contact_person') }}</label>
                                <input type="text"
                                       class="form-control @error('contact_person') is-invalid @enderror"
                                       id="contact_person" name="contact_person"
                                       value="{{ old('contact_person', $provider->contact_person) }}">
                                @error('contact_person')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">{{ __('provider/profile.agency_address') }}</label>
                            <input type="text"
                                   class="form-control @error('address') is-invalid @enderror"
                                   id="address" name="address"
                                   value="{{ old('address', $provider->address) }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- @if($provider->type === 'car')
                        <div class="form-group">
                            <label for="orange_msisdn">{{ __('provider/profile.orange_msisdn') }}</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-mobile-alt"></i>
                                    </span>
                                </div>
                                <input type="text"
                                       class="form-control @error('orange_msisdn') is-invalid @enderror"
                                       id="orange_msisdn" name="orange_msisdn"
                                       placeholder="{{ __('provider/profile.orange_msisdn_placeholder') }}"
                                       value="{{ old('orange_msisdn', $provider->orange_msisdn) }}">
                                @error('orange_msisdn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">{{ __('provider/profile.orange_msisdn_hint') }}</small>
                        </div>
                        @endif --}}

                        <hr>
                        <button type="submit" class="btn btn-primary-app">
                            <i class="fas fa-save mr-1"></i>{{ __('provider/profile.save_agency_info') }}
                        </button>
                    </form>
                </div>
            </div>

            @elseif($provider && $providerRole !== 'manager')
            <div class="alert alert-info" id="agency-info">
                <i class="fas fa-info-circle mr-2"></i>
                {!! __('provider/profile.managers_only_notice') !!}
            </div>
            @endif

            {{-- ---- 3. Change Password ---- --}}
            <div class="card shadow mb-4" id="change-password">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-lock mr-2"></i>{{ __('provider/profile.change_password') }}
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('provider.profile.password') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="current_password">
                                {{ __('provider/profile.current_password') }}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   id="current_password" name="current_password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="password">
                                    {{ __('provider/profile.new_password') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       id="password" name="password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">{{ __('provider/profile.password_hint') }}</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="password_confirmation">
                                    {{ __('provider/profile.confirm_password') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="password"
                                       class="form-control"
                                       id="password_confirmation"
                                       name="password_confirmation">
                            </div>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key mr-1"></i>{{ __('provider/profile.update_password') }}
                        </button>
                    </form>
                </div>
            </div>

        </div>{{-- end right col --}}
    </div>{{-- end row --}}

</div>

@push('scripts')
<script>
    // Smooth scroll for quick nav links
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
</script>
@endpush

@endsection