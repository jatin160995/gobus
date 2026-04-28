@extends('admin.providers.show')

@section('provider_tab')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-triangle mr-1"></i> {{ $errors->first() }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
@endif

<div class="row">

    {{-- LEFT: Assigned Users --}}
    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between" style="border-left:4px solid #FF8C00;">
                <h6 class="mb-0 font-weight-bold" style="color:#FF8C00;">
                    <i class="fas fa-users mr-2"></i>{{ __('provider_users.assigned_users') }}
                </h6>
                <span class="badge badge-pill" style="background:#FF8C00; color:#fff; font-size:0.8rem; padding:5px 12px;">
                    {{ $assignedUsers->count() }}
                </span>
            </div>

            <div class="card-body p-0">
                @forelse($assignedUsers as $user)
                    <div class="d-flex align-items-center justify-content-between px-4 py-3"
                        style="border-bottom:1px solid #f5f5f5;">

                        <div class="d-flex align-items-center">
                            {{-- Avatar --}}
                            <div class="rounded-circle d-flex align-items-center justify-content-center mr-3 flex-shrink-0"
                                style="width:42px; height:42px; background: linear-gradient(135deg, #FF8C00, #e17c00); color:#fff; font-weight:700; font-size:1rem;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>

                            <div>
                                <div class="font-weight-bold text-gray-800" style="font-size:0.92rem;">
                                    {{ $user->name }}
                                </div>
                                <div class="text-muted" style="font-size:0.8rem; line-height:1.6;">
                                    @if($user->email)
                                        <i class="fas fa-envelope fa-xs mr-1"></i>{{ $user->email }}
                                    @endif
                                    @if($user->phone)
                                        &nbsp;·&nbsp;<i class="fas fa-phone fa-xs mr-1"></i>{{ $user->phone }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center ml-3">
                            <span class="badge mr-3"
                                style="background:{{ $user->pivot->role === 'manager' ? '#e8f5e9' : '#e3f2fd' }};
                                       color:{{ $user->pivot->role === 'manager' ? '#2e7d32' : '#1565c0' }};
                                       border:1px solid {{ $user->pivot->role === 'manager' ? '#a5d6a7' : '#90caf9' }};
                                       border-radius:20px; padding:4px 10px; font-size:0.78rem;">
                                <i class="fas fa-{{ $user->pivot->role === 'manager' ? 'star' : 'user' }} fa-xs mr-1"></i>
                                {{ __('provider_users.roles.' . $user->pivot->role) }}
                            </span>

                            <form action="{{ route('admin.providers.removeUser', [$provider->id, $user->id]) }}"
                                  method="POST"
                                  onsubmit="return confirm('Remove {{ $user->name }} from this provider?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="btn btn-sm"
                                    style="background:#fff0f0; color:#c0392b; border:1px solid #f5c6cb; border-radius:6px;"
                                    title="Remove user">
                                    <i class="fas fa-user-minus fa-sm"></i>
                                </button>
                            </form>
                        </div>

                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-user-slash fa-3x mb-3 d-block" style="color:#e0e0e0;"></i>
                        <p class="mb-0">{{ __('provider_users.no_users_assigned') }}</p>
                        <small>Use the panel on the right to assign users.</small>
                    </div>
                @endforelse
            </div>

            @if($assignedUsers->count())
                <div class="card-footer text-right" style="background:#fafafa;">
                    <a href="{{ route('admin.provider-users.create', ['provider_id' => $provider->id]) }}"
                        class="btn btn-sm"
                        style="background:#FF8C00; color:#fff; border:none;">
                        <i class="fas fa-user-plus mr-1"></i> {{ __('provider_users.create_new_provider_user') }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- RIGHT: Assign Panel --}}
    <div class="col-lg-5 mb-4">

        {{-- Assign Existing User --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="border-left:4px solid #00829F;">
                <h6 class="mb-0 font-weight-bold" style="color:#00829F;">
                    <i class="fas fa-user-check mr-2"></i>{{ __('provider_users.assign_user') }}
                </h6>
            </div>

            <div class="card-body">

                @if($availableUsers->count())

                    <form action="{{ route('admin.providers.assignUser', $provider->id) }}" method="POST" id="assignForm">
                        @csrf

                        {{-- Hidden real select that gets submitted --}}
                        <input type="hidden" name="user_id" id="selectedUserId">

                        {{-- Search box --}}
                        <div class="mb-3">
                            <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                {{ __('provider_users.select_user') }}
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                </div>
                                <input type="text"
                                    id="userSearchInput"
                                    class="form-control border-left-0"
                                    placeholder="Search by name, email or phone..."
                                    autocomplete="off">
                            </div>
                        </div>

                        {{-- Selected user preview --}}
                        <div id="selectedUserPreview" style="display:none;" class="mb-3 p-3 rounded"
                            style="background:#f0f9fb; border:1px solid #b2dfdb;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mr-2 flex-shrink-0"
                                        style="width:34px; height:34px; background:#00829F; color:#fff; font-weight:700; font-size:0.85rem;">
                                        <span id="previewInitial"></span>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold" style="font-size:0.88rem;" id="previewName"></div>
                                        <div class="text-muted" style="font-size:0.78rem;" id="previewContact"></div>
                                    </div>
                                </div>
                                <button type="button" id="clearSelection" class="btn btn-sm btn-link text-danger p-0">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        {{-- User list (searchable cards) --}}
                        <div id="userListContainer"
                            style="max-height:220px; overflow-y:auto; border:1px solid #e3e6f0; border-radius:6px;">
                            @foreach($availableUsers as $u)
                                <div class="user-option px-3 py-2 d-flex align-items-center"
                                    data-id="{{ $u->id }}"
                                    data-name="{{ strtolower($u->name) }}"
                                    data-email="{{ strtolower($u->email ?? '') }}"
                                    data-phone="{{ $u->phone ?? '' }}"
                                    data-display-name="{{ $u->name }}"
                                    data-display-contact="{{ $u->email ?? $u->phone ?? '' }}"
                                    data-initial="{{ strtoupper(substr($u->name, 0, 1)) }}"
                                    style="cursor:pointer; border-bottom:1px solid #f5f5f5; transition:background 0.15s;">

                                    <div class="rounded-circle d-flex align-items-center justify-content-center mr-2 flex-shrink-0"
                                        style="width:32px; height:32px; background:#e0f2f1; color:#00829F; font-weight:700; font-size:0.8rem;">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-size:0.88rem; font-weight:600; color:#2c2c2c;">{{ $u->name }}</div>
                                        <div style="font-size:0.76rem; color:#888;">
                                            {{ $u->email ?? '' }}
                                            @if($u->email && $u->phone) · @endif
                                            {{ $u->phone ?? '' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            
                            {{-- Placed inside container for better visual flow --}}
                            <div id="noUsersFound" class="text-center py-4 text-muted" style="display:none; font-size:0.85rem;">
                                <i class="fas fa-search mb-2 d-block fa-lg"></i> No matching users found.
                            </div>
                        </div>

                        {{-- Role --}}
                        <div class="mt-3">
                            <label class="font-weight-bold text-gray-700" style="font-size:0.85rem;">
                                {{ __('provider_users.role') }}
                            </label>
                            <div class="d-flex" style="gap:10px;">
                                <label class="flex-fill mb-0">
                                    <input type="radio" name="role" value="manager" class="d-none role-radio" checked>
                                    <div class="role-card text-center py-2 px-3 rounded"
                                        style="border:2px solid #a5d6a7; background:#e8f5e9; cursor:pointer; transition:all 0.2s;">
                                        <i class="fas fa-star fa-sm mb-1 d-block" style="color:#2e7d32;"></i>
                                        <span style="font-size:0.82rem; font-weight:600; color:#2e7d32;">
                                            {{ __('provider_users.roles.manager') }}
                                        </span>
                                    </div>
                                </label>
                                <label class="flex-fill mb-0">
                                    <input type="radio" name="role" value="staff" class="d-none role-radio">
                                    <div class="role-card text-center py-2 px-3 rounded"
                                        style="border:2px solid #e0e0e0; background:#f5f5f5; cursor:pointer; transition:all 0.2s;">
                                        <i class="fas fa-user fa-sm mb-1 d-block" style="color:#555;"></i>
                                        <span style="font-size:0.82rem; font-weight:600; color:#555;">
                                            {{ __('provider_users.roles.staff') }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <button type="submit" id="assignBtn"
                            class="btn btn-block mt-3 disabled"
                            style="background:#00829F; color:#fff; border:none; opacity:0.5;"
                            disabled>
                            <i class="fas fa-user-check mr-1"></i> {{ __('provider_users.assign') }}
                        </button>

                    </form>

                @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2 d-block" style="color:#a5d6a7;"></i>
                        <p class="mb-2" style="font-size:0.88rem;">All provider users are already assigned.</p>
                        <a href="{{ route('admin.provider-users.create') }}"
                            class="btn btn-sm"
                            style="background:#FF8C00; color:#fff; border:none;">
                            <i class="fas fa-user-plus mr-1"></i> Create New User
                        </a>
                    </div>
                @endif

            </div>
        </div>

        {{-- Create New User shortcut --}}
        <div class="card shadow-sm" style="border:2px dashed #e3e6f0;">
            <div class="card-body text-center py-3">
                <p class="text-muted mb-2" style="font-size:0.85rem;">
                    Need a new provider account?
                </p>
                <a href="{{ route('admin.provider-users.create') }}"
                    class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-user-plus mr-1"></i> {{ __('provider_users.create_new_provider_user') }}
                </a>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const searchInput    = document.getElementById('userSearchInput');
    const userOptions    = document.querySelectorAll('.user-option');
    const container      = document.getElementById('userListContainer');
    const noFound        = document.getElementById('noUsersFound');
    const preview        = document.getElementById('selectedUserPreview');
    const previewName    = document.getElementById('previewName');
    const previewContact = document.getElementById('previewContact');
    const previewInitial = document.getElementById('previewInitial');
    const selectedId     = document.getElementById('selectedUserId');
    const assignBtn      = document.getElementById('assignBtn');
    const clearBtn       = document.getElementById('clearSelection');

    let userSelected = false;

    // ── Search filtering ──────────────────────────────────────
    searchInput.addEventListener('input', function () {
        if (userSelected) return;

        const q = this.value.toLowerCase().trim();
        let visible = 0;

        userOptions.forEach(function (opt) {
            const match = opt.dataset.name.includes(q)
                       || opt.dataset.email.includes(q)
                       || opt.dataset.phone.includes(q);

            if (match) {
                opt.classList.remove('d-none');
                opt.classList.add('d-flex');
                visible++;
            } else {
                opt.classList.remove('d-flex');
                opt.classList.add('d-none');
            }
        });

        container.style.display = '';
        noFound.style.display = (visible === 0) ? 'block' : 'none';
    });

    // ── Select a user ─────────────────────────────────────────
    userOptions.forEach(function (opt) {
        opt.addEventListener('mouseenter', function () {
            if (!opt.classList.contains('selected')) {
                opt.style.background = '#f0f9fb';
            }
        });
        opt.addEventListener('mouseleave', function () {
            if (!opt.classList.contains('selected')) {
                opt.style.background = '';
            }
        });

        opt.addEventListener('click', function () {
            userOptions.forEach(function (o) {
                o.classList.remove('selected');
                o.style.background = '';
                o.style.borderLeft = '';
            });

            opt.classList.add('selected');
            opt.style.background = '#e0f2f1';
            opt.style.borderLeft = '3px solid #00829F';

            selectedId.value          = opt.dataset.id;
            previewInitial.textContent  = opt.dataset.initial;
            previewName.textContent     = opt.dataset.displayName;
            previewContact.textContent  = opt.dataset.displayContact;
            preview.style.display     = 'block';

            searchInput.value = '';
            container.style.display = 'none';
            noFound.style.display = 'none';
            userSelected = true;

            assignBtn.disabled      = false;
            assignBtn.style.opacity = '1';
            assignBtn.classList.remove('disabled');
        });
    });

    // ── Clear selection ───────────────────────────────────────
    clearBtn.addEventListener('click', function () {
        selectedId.value        = '';
        preview.style.display   = 'none';
        userSelected            = false;

        userOptions.forEach(function (o) {
            o.classList.remove('selected');
            o.classList.remove('d-none');
            o.classList.add('d-flex');
            o.style.background = '';
            o.style.borderLeft = '';
        });

        searchInput.value = '';
        container.style.display = '';
        noFound.style.display = 'none';
        searchInput.focus();

        assignBtn.disabled      = true;
        assignBtn.style.opacity = '0.5';
        assignBtn.classList.add('disabled');
    });

    // ── Role card toggle ──────────────────────────────────────
    document.querySelectorAll('.role-radio').forEach(function (radio) {
        radio.closest('label').querySelector('.role-card').addEventListener('click', function () {
            radio.checked = true;

            document.querySelectorAll('.role-radio').forEach(function (r) {
                const card = r.closest('label').querySelector('.role-card');
                if (r.checked) {
                    card.style.borderColor = r.value === 'manager' ? '#a5d6a7' : '#90caf9';
                    card.style.background  = r.value === 'manager' ? '#e8f5e9' : '#e3f2fd';
                } else {
                    card.style.borderColor = '#e0e0e0';
                    card.style.background  = '#f5f5f5';
                }
            });
        });
    });

    // ── Prevent submit if no user selected ───────────────────
    document.getElementById('assignForm')?.addEventListener('submit', function (e) {
        if (!selectedId.value) {
            e.preventDefault();
            searchInput.focus();
            searchInput.style.borderColor = '#dc3545';
            setTimeout(() => { searchInput.style.borderColor = ''; }, 2000);
        }
    });

});
</script>
@endpush

@endsection