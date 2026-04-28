@extends('admin.layouts.app')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">{{ __('provider_index.providers') }}</h1>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                {{ $providers->total() }} providers registered
            </p>
        </div>
        <a href="{{ route('admin.providers.create') }}"
            class="btn btn-sm shadow-sm mt-2 mt-sm-0"
            style="background:#FF8C00; color:#fff; border:none; padding:8px 18px;">
            <i class="fas fa-plus mr-1"></i> {{ __('provider_index.add_provider') }}
        </a>
    </div>

    {{-- Summary Stats --}}
    <div class="row mb-4">
        @php
            $totalActive   = $providers->getCollection()->where('status','active')->count();
            $totalInactive = $providers->getCollection()->where('status','inactive')->count();
            $totalBus      = $providers->getCollection()->where('type','bus')->count();
            $totalCar      = $providers->getCollection()->where('type','car')->count();
        @endphp

        <div class="col-6 col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #FF8C00 !important;">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mr-3"
                        style="width:42px;height:42px;background:#fff8f0;">
                        <i class="fas fa-building" style="color:#FF8C00;"></i>
                    </div>
                    <div>
                        <div class="font-weight-bold" style="font-size:1.4rem; color:#FF8C00; line-height:1;">
                            {{ $providers->total() }}
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">Total Providers</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #28a745 !important;">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mr-3"
                        style="width:42px;height:42px;background:#f0fff4;">
                        <i class="fas fa-check-circle" style="color:#28a745;"></i>
                    </div>
                    <div>
                        <div class="font-weight-bold" style="font-size:1.4rem; color:#28a745; line-height:1;">
                            {{ $totalActive }}
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">Active</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #00829F !important;">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mr-3"
                        style="width:42px;height:42px;background:#f0f9fb;">
                        <i class="fas fa-bus" style="color:#00829F;"></i>
                    </div>
                    <div>
                        <div class="font-weight-bold" style="font-size:1.4rem; color:#00829F; line-height:1;">
                            {{ $totalBus }}
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">Bus Providers</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #6f42c1 !important;">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mr-3"
                        style="width:42px;height:42px;background:#f5f0ff;">
                        <i class="fas fa-car" style="color:#6f42c1;"></i>
                    </div>
                    <div>
                        <div class="font-weight-bold" style="font-size:1.4rem; color:#6f42c1; line-height:1;">
                            {{ $totalCar }}
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">Car Providers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card shadow-sm">

        {{-- Filter Bar --}}
        <div class="card-header bg-white py-3">
            <div class="d-flex flex-wrap align-items-center" style="gap:10px;">

                {{-- Search --}}
                <div class="input-group" style="max-width:260px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-search text-muted fa-sm"></i>
                        </span>
                    </div>
                    <input type="text" id="providerSearch"
                        class="form-control border-left-0"
                        placeholder="Search name, email, phone..."
                        style="font-size:0.85rem;">
                </div>

                {{-- Type Filter --}}
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="filter-type btn btn-outline-secondary active" data-type="all">
                        All
                    </button>
                    <button type="button" class="filter-type btn btn-outline-secondary" data-type="bus">
                        <i class="fas fa-bus fa-sm mr-1"></i> Bus
                    </button>
                    <button type="button" class="filter-type btn btn-outline-secondary" data-type="car">
                        <i class="fas fa-car fa-sm mr-1"></i> Car
                    </button>
                    <button type="button" class="filter-type btn btn-outline-secondary" data-type="train">
                        <i class="fas fa-train fa-sm mr-1"></i> Train
                    </button>
                    <button type="button" class="filter-type btn btn-outline-secondary" data-type="air">
                        <i class="fas fa-plane fa-sm mr-1"></i> Air
                    </button>
                </div>

                {{-- Status Filter --}}
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="filter-status btn btn-outline-secondary active" data-status="all">
                        All Status
                    </button>
                    <button type="button" class="filter-status btn btn-outline-secondary" data-status="active">
                        Active
                    </button>
                    <button type="button" class="filter-status btn btn-outline-secondary" data-status="inactive">
                        Inactive
                    </button>
                </div>

            </div>
        </div>

        {{-- Provider Cards (replaces table to fix horizontal scroll) --}}
        <div class="card-body p-0" id="providerList">

            @forelse($providers as $p)
                @php
                    $typeColors = [
                        'bus'   => ['bg' => '#f0f9fb', 'color' => '#00829F', 'icon' => 'fa-bus'],
                        'car'   => ['bg' => '#f5f0ff', 'color' => '#6f42c1', 'icon' => 'fa-car'],
                        'train' => ['bg' => '#fff8f0', 'color' => '#FF8C00', 'icon' => 'fa-train'],
                        'air'   => ['bg' => '#f0fff4', 'color' => '#28a745', 'icon' => 'fa-plane'],
                    ];
                    $tc = $typeColors[$p->type] ?? ['bg' => '#f5f5f5', 'color' => '#555', 'icon' => 'fa-building'];
                @endphp

                <div class="provider-row px-4 py-3"
                    data-name="{{ strtolower($p->name) }}"
                    data-email="{{ strtolower($p->email ?? '') }}"
                    data-phone="{{ $p->phone ?? '' }}"
                    data-contact="{{ strtolower($p->contact_person ?? '') }}"
                    data-type="{{ $p->type }}"
                    data-status="{{ $p->status }}"
                    style="border-bottom:1px solid #f0f0f0; transition:background 0.15s;">

                    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">

                        {{-- Left: Logo + Info --}}
                        <div class="d-flex align-items-center" style="min-width:0; flex:1;">

                            {{-- Logo --}}
                            <div class="flex-shrink-0 mr-3">
                                @if($p->logo)
                                    <img src="{{ asset('storage/' . $p->logo) }}"
                                        width="52" height="52"
                                        class="rounded shadow-sm"
                                        style="object-fit:cover; border:2px solid #e3e6f0;">
                                @else
                                    <div class="rounded d-flex align-items-center justify-content-center shadow-sm"
                                        style="width:52px;height:52px;background:linear-gradient(135deg,#FF8C00,#e17c00);color:#fff;font-weight:700;font-size:1.2rem;">
                                        {{ strtoupper(substr($p->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>

                            {{-- Name + Meta --}}
                            <div style="min-width:0;">
                                <div class="d-flex align-items-center flex-wrap" style="gap:6px;">
                                    <span class="font-weight-bold text-gray-800" style="font-size:0.95rem;">
                                        {{ $p->name }}
                                    </span>

                                    {{-- Type Badge --}}
                                    <span class="badge"
                                        style="background:{{ $tc['bg'] }}; color:{{ $tc['color'] }};
                                               border:1px solid {{ $tc['color'] }}30;
                                               border-radius:20px; padding:3px 9px; font-size:0.72rem;">
                                        <i class="fas {{ $tc['icon'] }} fa-xs mr-1"></i>
                                        {{ strtoupper($p->type) }}
                                    </span>

                                    {{-- Status Badge --}}
                                    <span class="badge badge-{{ $p->status === 'active' ? 'success' : 'danger' }}"
                                        style="border-radius:20px; padding:3px 9px; font-size:0.72rem;">
                                        {{ ucfirst($p->status) }}
                                    </span>
                                </div>

                                {{-- Contact row --}}
                                <div class="text-muted mt-1" style="font-size:0.79rem; line-height:1.7;">
                                    @if($p->contact_person)
                                        <span class="mr-3">
                                            <i class="fas fa-user fa-xs mr-1"></i>{{ $p->contact_person }}
                                        </span>
                                    @endif
                                    @if($p->phone)
                                        <span class="mr-3">
                                            <i class="fas fa-phone fa-xs mr-1"></i>{{ $p->phone }}
                                        </span>
                                    @endif
                                    @if($p->email)
                                        <span class="mr-3">
                                            <i class="fas fa-envelope fa-xs mr-1"></i>{{ $p->email }}
                                        </span>
                                    @endif
                                    @if($p->orange_msisdn)
                                        <span class="mr-3">
                                            <i class="fas fa-mobile-alt fa-xs mr-1" style="color:#ff6600;"></i>{{ $p->orange_msisdn }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Middle: Stats --}}
                        <div class="d-flex align-items-center flex-shrink-0" style="gap:16px;">

                            {{-- Commission --}}
                            <div class="text-center">
                                <div class="font-weight-bold" style="font-size:1rem; color:#f9a825;">
                                    {{ $p->commission_rate }}%
                                </div>
                                <div class="text-muted" style="font-size:0.7rem;">Commission</div>
                            </div>

                            {{-- Payout --}}
                            <div class="text-center">
                                <div>
                                    <span class="badge badge-{{ $p->payout_method === 'auto' ? 'info' : 'secondary' }}"
                                        style="font-size:0.7rem; border-radius:20px; padding:3px 8px;">
                                        {{ ucfirst($p->payout_method) }}
                                    </span>
                                </div>
                                <div class="text-muted" style="font-size:0.7rem;">Payout</div>
                            </div>

                            {{-- Divider --}}
                            <div style="width:1px; height:36px; background:#e3e6f0;"></div>

                            {{-- Actions --}}
                            <div class="d-flex align-items-center" style="gap:6px;">
                                <a href="{{ route('admin.providers.show', $p->id) }}"
                                    class="btn btn-sm"
                                    style="background:#FF8C00; color:#fff; border:none; padding:6px 14px; font-size:0.82rem;"
                                    title="View Details">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                                <a href="{{ route('admin.providers.edit', $p->id) }}"
                                    class="btn btn-sm btn-outline-secondary"
                                    style="padding:6px 10px; font-size:0.82rem;"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

            @empty
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-building fa-3x mb-3 d-block" style="color:#e0e0e0;"></i>
                    <p class="mb-2">No providers found.</p>
                    <a href="{{ route('admin.providers.create') }}"
                        class="btn btn-sm"
                        style="background:#FF8C00; color:#fff; border:none;">
                        <i class="fas fa-plus mr-1"></i> Add First Provider
                    </a>
                </div>
            @endforelse

            {{-- No results row (shown by JS filter) --}}
            <div id="noFilterResults" class="text-center py-5 text-muted d-none">
                <i class="fas fa-search fa-2x mb-2 d-block" style="color:#e0e0e0;"></i>
                No providers match your filters.
            </div>

        </div>

        {{-- Pagination --}}
        @if($providers->hasPages())
            <div class="card-footer bg-white">
                {{ $providers->links() }}
            </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const rows         = document.querySelectorAll('.provider-row');
    const searchInput  = document.getElementById('providerSearch');
    const noResults    = document.getElementById('noFilterResults');

    let activeType   = 'all';
    let activeStatus = 'all';
    let searchQuery  = '';

    function applyFilters() {
        let visible = 0;

        rows.forEach(function (row) {
            const name    = row.dataset.name;
            const email   = row.dataset.email;
            const phone   = row.dataset.phone;
            const contact = row.dataset.contact;
            const type    = row.dataset.type;
            const status  = row.dataset.status;

            const matchSearch = !searchQuery
                || name.includes(searchQuery)
                || email.includes(searchQuery)
                || phone.includes(searchQuery)
                || contact.includes(searchQuery);

            const matchType   = activeType   === 'all' || type   === activeType;
            const matchStatus = activeStatus === 'all' || status === activeStatus;

            if (matchSearch && matchType && matchStatus) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        noResults.classList.toggle('d-none', visible > 0);
    }

    // Search
    searchInput.addEventListener('input', function () {
        searchQuery = this.value.toLowerCase().trim();
        applyFilters();
    });

    // Type filter
    document.querySelectorAll('.filter-type').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filter-type').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeType = this.dataset.type;
            applyFilters();
        });
    });

    // Status filter
    document.querySelectorAll('.filter-status').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filter-status').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeStatus = this.dataset.status;
            applyFilters();
        });
    });

    // Hover effect
    rows.forEach(function (row) {
        row.addEventListener('mouseenter', function () {
            this.style.background = '#fafafa';
        });
        row.addEventListener('mouseleave', function () {
            this.style.background = '';
        });
    });

});
</script>
@endpush

@endsection