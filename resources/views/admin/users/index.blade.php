@extends('admin.layouts.app')

@section('content')

<div class="container-fluid">
    <h3 class="mb-4">{{ __('users.title') }}</h3>

    <div class="card">
        <div class="card-body p-0">

            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('users.name') }}</th>
                        <th>{{ __('users.email') }}</th>
                        <th>{{ __('users.phone') }}</th>
                        <th>{{ __('users.status') }}</th>
                        <th>{{ __('users.registered_at') }}</th>
                        <th>{{ __('users.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>{{ $u->id }}</td>
                            <td>{{ $u->name }}</td>
                            <td>{{ $u->email ?? '-' }}</td>
                            <td>{{ $u->phone ?? '-' }}</td>

                            <td>
                                <span class="badge 
                                    @if($u->status == 'active') bg-success 
                                    @elseif($u->status == 'inactive') bg-warning 
                                    @else bg-danger @endif">
                                    {{ __('users.status_' . $u->status) }}
                                </span>
                            </td>

                            <td>{{ $u->created_at->format('d M Y') }}</td>

                            <td>
                                <a href="{{ route('admin.user.view', $u->id) }}" 
                                    class="btn btn-sm btn-primary">
                                    {{ __('users.view') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>

        </div>

        <div class="card-footer">
            {{ $users->links() }}
        </div>

    </div>
</div>

@endsection
