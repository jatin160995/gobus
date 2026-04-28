@extends('admin.layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">{{ __('users_detail.details_title') }}</h3>

        <a href="{{ route('admin.users.list') }}" class="btn btn-secondary btn-sm">
            ← {{ __('users_detail.back_to_list') }}
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="row mb-4">

                <!-- Avatar -->
                <div class="col-md-3 text-center">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                         style="width:100px; height:100px; font-size:36px;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>

                <!-- User Info -->
                <div class="col-md-9">

                    <h4 class="mb-1">{{ $user->name }}</h4>

                    <p class="mb-1"><strong>{{ __('users_detail.email') }}:</strong> {{ $user->email ?? '-' }}</p>

                    <p class="mb-1"><strong>{{ __('users_detail.phone') }}:</strong> {{ $user->phone ?? '-' }}</p>

                    <p class="mb-1">
                        <strong>{{ __('users_detail.status') }}:</strong>
                        <span class="badge 
                            @if($user->status == 'active') bg-success
                            @elseif($user->status == 'inactive') bg-warning
                            @else bg-danger @endif">
                            {{ __('users_detail.status_' . $user->status) }}
                        </span>
                    </p>

                    <p class="mb-1">
                        <strong>{{ __('users_detail.email_verified') }}:</strong>
                        @if($user->email_verified_at)
                            <span class="text-success">{{ $user->email_verified_at }}</span>
                        @else
                            <span class="text-danger">{{ __('users_detail.not_verified') }}</span>
                        @endif
                    </p>

                    <p class="mb-1">
                        <strong>{{ __('users_detail.phone_verified') }}:</strong>
                        @if($user->phone_verified_at)
                            <span class="text-success">{{ $user->phone_verified_at }}</span>
                        @else
                            <span class="text-danger">{{ __('users_detail.not_verified') }}</span>
                        @endif
                    </p>

                    <p class="mb-1">
                        <strong>{{ __('users_detail.registered_at') }}:</strong> 
                        {{ $user->created_at->format('d M Y, h:i A') }}
                    </p>

                </div>
            </div>

            <hr>

            {{-- Devices --}}
            @if(isset($devices) && count($devices) > 0)
                <h5 class="mb-3">{{ __('users_detail.devices_title') }}</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('users_detail.device_name') }}</th>
                                <th>{{ __('users_detail.device_os') }}</th>
                                <th>{{ __('users_detail.device_model') }}</th>
                                <th>{{ __('users_detail.app_version') }}</th>
                                <th>{{ __('users_detail.fcm_token') }}</th>
                                <th>{{ __('users_detail.last_login') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($devices as $d)
                                <tr>
                                    <td>{{ $d->device_name }}</td>
                                    <td>{{ $d->device_os }}</td>
                                    <td>{{ $d->device_model }}</td>
                                    <td>{{ $d->app_version }}</td>
                                    <td>{{ $d->fcm_token }}</td>
                                    <td>{{ $d->last_login_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @else
                <p class="text-muted">{{ __('users_detail.no_devices') }}</p>
            @endif

        </div>
    </div>

</div>

@endsection
