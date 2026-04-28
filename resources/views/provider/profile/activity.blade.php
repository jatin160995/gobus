@extends('provider.layouts.app')

@section('title', __('provider/profile.activity_log'))

@section('content')

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-history mr-2"></i>{{ __('provider/profile.activity_log') }}
        </h1>
        <a href="{{ route('provider.profile.edit') }}" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> {{ __('provider/profile.back') }}
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list mr-2"></i>{{ __('provider/profile.recent_activity') }}
            </h6>
            <span class="badge badge-primary">{{ $logs->total() }} {{ __('provider/profile.entries') }}</span>
        </div>
        <div class="card-body p-0">
            @if($logs->count())
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('provider/profile.action') }}</th>
                                <th>{{ __('provider/profile.date_time') }}</th>
                                <th>{{ __('provider/profile.ip_address') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td class="text-muted small">{{ $log->id }}</td>
                                <td>
                                    <i class="fas fa-dot-circle text-primary mr-1" style="font-size:0.6rem;"></i>
                                    {{ $log->action }}
                                </td>
                                <td class="text-muted small">
                                  {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, H:i') }}
                                    <br>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</small>
                                </td>
                                <td class="text-muted small">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">
                    {{ $logs->links() }}
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-history fa-3x mb-3 d-block" style="opacity:0.3;"></i>
                    {{ __('provider/profile.no_activity') }}
                </div>
            @endif
        </div>
    </div>

</div>

@endsection