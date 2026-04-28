@extends('admin.layouts.app')
@section('title', 'Settings')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between mb-4">
        <h3>Settings</h3>
        <a href="{{ route('settings.create') }}" class="btn btn-primary">
            + Add New Setting
        </a>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3">
        @foreach($groups as $i => $group)
            <li class="nav-item">
                <a class="nav-link {{ $i === 0 ? 'active' : '' }}"
                   data-bs-toggle="tab"
                   href="#tab-{{ $group }}">
                    {{ ucfirst($group) }}
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content">

        @foreach($settings as $group => $items)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                 id="tab-{{ $group }}">

                <div class="card shadow-sm">
                    <div class="card-body">

                        <form method="POST" action="{{ route('settings.update', 0) }}">
                            @csrf
                            @method('PUT')

                            @foreach($items as $setting)
                                <div class="mb-3">
                                    <label class="form-label">
                                        {{ $setting->label ?? $setting->key }}
                                    </label>

                                    @include('admin.settings.partials.input', ['setting' => $setting])
                                </div>
                            @endforeach

                            <button class="btn btn-success">Save Changes</button>

                        </form>

                    </div>
                </div>

            </div>
        @endforeach

    </div>

</div>

@endsection
