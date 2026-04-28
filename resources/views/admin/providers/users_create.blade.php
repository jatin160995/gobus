@extends('admin.layouts.app')

@section('content')

<div class="container-fluid">
    {{-- Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                <i class="fas fa-user-plus mr-2" style="color:#FF8C00;"></i>
                {{ __('provider_user.create_title') }}
            </h1>
            @if(isset($provider))
                <p class="text-muted mb-0" style="font-size:0.85rem;">
                    Creating user for
                    <span class="font-weight-bold" style="color:#FF8C00;">{{ $provider->name }}</span>
                    — they can be assigned after creation.
                </p>
            @endif
        </div>
        <a href="{{ isset($provider) ? route('admin.providers.users', $provider->id) : url()->previous() }}"
            class="btn btn-sm btn-secondary shadow-sm mt-2 mt-sm-0">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-12 col-xl-12 ">

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert"
                    style="border-left:4px solid #dc3545;">
                    <div class="font-weight-bold mb-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Please fix the following errors:
                    </div>
                    <ul class="mb-0 pl-3">
                        @foreach($errors->all() as $error)
                            <li style="font-size:0.88rem;">{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <div class="card shadow-sm">

                <div class="card-header py-3" style="border-left:4px solid #FF8C00; background:#fff;">
                    <h6 class="mb-0 font-weight-bold" style="color:#FF8C00;">
                        <i class="fas fa-id-card mr-2"></i> Account Details
                    </h6>
                </div>

                <div class="card-body pt-4">

                    <form action="{{ route('admin.provider-users.store') }}" method="POST" id="createUserForm">
                        @csrf

                        {{-- Pass provider_id through so we can redirect back --}}
                        @if(isset($provider))
                            <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                        @endif

                        {{-- Full Name --}}
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                {{ __('provider_user.name') }} <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-user fa-sm text-muted"></i>
                                    </span>
                                </div>
                                <input type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    class="form-control border-left-0 @error('name') is-invalid @enderror"
                                    placeholder="Full name"
                                    required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                {{ __('provider_user.email') }} <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-envelope fa-sm text-muted"></i>
                                    </span>
                                </div>
                                <input type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    class="form-control border-left-0 @error('email') is-invalid @enderror"
                                    placeholder="email@example.com"
                                    required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Used to log in to the provider panel.</small>
                        </div>

                        {{-- Phone --}}
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                {{ __('provider_user.phone') }} <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-phone fa-sm text-muted"></i>
                                    </span>
                                </div>
                                <input type="text"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    class="form-control border-left-0 @error('phone') is-invalid @enderror"
                                    placeholder="+237 6XX XXX XXX"
                                    required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Divider --}}
                        <hr class="my-4">

                        {{-- Password --}}
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                {{ __('provider_user.password') }} <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-lock fa-sm text-muted"></i>
                                    </span>
                                </div>
                                <input type="password"
                                    name="password"
                                    id="passwordField"
                                    class="form-control border-left-0 border-right-0 @error('password') is-invalid @enderror"
                                    placeholder="Minimum 6 characters"
                                    required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary bg-white"
                                        id="togglePassword"
                                        style="border-left:0; z-index:4;">
                                        <i class="fas fa-eye fa-sm" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                Confirm Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-lock fa-sm text-muted"></i>
                                    </span>
                                </div>
                                <input type="password"
                                    name="password_confirmation"
                                    id="passwordConfirmField"
                                    class="form-control border-left-0 border-right-0 @error('password_confirmation') is-invalid @enderror"
                                    placeholder="Re-enter password"
                                    required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary bg-white"
                                        id="togglePasswordConfirm"
                                        style="border-left:0; z-index:4;">
                                        <i class="fas fa-eye fa-sm" id="togglePasswordConfirmIcon"></i>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            {{-- Live match indicator --}}
                            <div id="passwordMatchMsg" style="font-size:0.8rem; margin-top:4px; display:none;"></div>
                        </div>

                        {{-- Password strength bar --}}
                        <div class="mb-4">
                            <div style="height:4px; border-radius:4px; background:#e9ecef; overflow:hidden;">
                                <div id="strengthBar" style="height:100%; width:0%; transition:all 0.3s; border-radius:4px;"></div>
                            </div>
                            <small id="strengthLabel" class="text-muted"></small>
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex align-items-center justify-content-between pt-2">
                            <a href="{{ isset($provider) ? route('admin.providers.users', $provider->id) : url()->previous() }}"
                                class="btn btn-secondary">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn px-4" id="submitBtn"
                                style="background:#FF8C00; color:#fff; border:none; min-width:160px;">
                                <i class="fas fa-user-plus mr-1"></i> {{ __('provider_user.create_btn') }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            {{-- Info note --}}
            @if(isset($provider))
                <div class="mt-3 p-3 rounded" style="background:#f0f9fb; border:1px solid #b2dfdb; font-size:0.83rem;">
                    <i class="fas fa-info-circle mr-1" style="color:#00829F;"></i>
                    After creating the user, you'll be redirected back to
                    <strong>{{ $provider->name }}</strong>'s users tab where you can assign them.
                </div>
            @endif

        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const passwordField        = document.getElementById('passwordField');
    const passwordConfirmField = document.getElementById('passwordConfirmField');
    const strengthBar          = document.getElementById('strengthBar');
    const strengthLabel        = document.getElementById('strengthLabel');
    const matchMsg             = document.getElementById('passwordMatchMsg');

    // ── Toggle password visibility ────────────────────────────
    document.getElementById('togglePassword').addEventListener('click', function () {
        const icon = document.getElementById('togglePasswordIcon');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    document.getElementById('togglePasswordConfirm').addEventListener('click', function () {
        const icon = document.getElementById('togglePasswordConfirmIcon');
        if (passwordConfirmField.type === 'password') {
            passwordConfirmField.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordConfirmField.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    // ── Password strength ─────────────────────────────────────
    passwordField.addEventListener('input', function () {
        const val = this.value;
        let score = 0;

        if (val.length >= 6)  score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { label: '',          color: '',        width: '0%'   },
            { label: 'Weak',      color: '#dc3545', width: '20%'  },
            { label: 'Weak',      color: '#dc3545', width: '35%'  },
            { label: 'Fair',      color: '#ffc107', width: '55%'  },
            { label: 'Good',      color: '#17a2b8', width: '75%'  },
            { label: 'Strong',    color: '#28a745', width: '100%' },
        ];

        const level = levels[score] || levels[0];
        strengthBar.style.width      = val.length ? level.width : '0%';
        strengthBar.style.background = level.color;
        strengthLabel.textContent    = val.length ? level.label : '';
        strengthLabel.style.color    = level.color;

        checkMatch();
    });

    // ── Password match ────────────────────────────────────────
    passwordConfirmField.addEventListener('input', checkMatch);

    function checkMatch() {
        const p1 = passwordField.value;
        const p2 = passwordConfirmField.value;

        if (!p2) {
            matchMsg.style.display = 'none';
            return;
        }

        matchMsg.style.display = 'block';

        if (p1 === p2) {
            matchMsg.innerHTML  = '<i class="fas fa-check-circle mr-1" style="color:#28a745;"></i><span style="color:#28a745;">Passwords match</span>';
            passwordConfirmField.style.borderColor = '#28a745';
        } else {
            matchMsg.innerHTML  = '<i class="fas fa-times-circle mr-1" style="color:#dc3545;"></i><span style="color:#dc3545;">Passwords do not match</span>';
            passwordConfirmField.style.borderColor = '#dc3545';
        }
    }

    // ── Prevent submit if passwords don't match ───────────────
    document.getElementById('createUserForm').addEventListener('submit', function (e) {
        if (passwordField.value !== passwordConfirmField.value) {
            e.preventDefault();
            matchMsg.style.display = 'block';
            matchMsg.innerHTML = '<i class="fas fa-times-circle mr-1" style="color:#dc3545;"></i><span style="color:#dc3545;">Passwords do not match</span>';
            passwordConfirmField.focus();
        }
    });

});
</script>
@endpush

@endsection