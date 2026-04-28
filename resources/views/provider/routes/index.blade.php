@extends('provider.layouts.app')

@section('content')
<h3 class="mb-3">{{ __('routes.index.title') }}</h3>

<a href="{{ route('provider.routes.create') }}" class="btn btn-danger mb-3">
    {{ __('routes.index.add_route') }}
</a>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-striped">
<thead>
    <tr>
        <th>#</th>
        <th>{{ __('routes.form.departure') }}</th>
        <th>{{ __('routes.form.arrival') }}</th>
        <th>{{ __('routes.form.distance') }}</th>
        <th>{{ __('routes.form.duration') }}</th>
        <th>{{ __('routes.form.transport_type') }}</th>
        <th>{{ __('routes.form.actions') }}</th>
    </tr>
</thead>
<tbody>
@foreach($routes as $route)
<tr>
    <td>{{ $route->id }}</td>
    <td>{{ $route->departureCity->name }}</td>
    <td>{{ $route->arrivalCity->name }}</td>
    <td>{{ $route->distance_km }} km</td>
    <td>{{ $route->duration_minutes }} min</td>
    <td>{{ ucfirst($route->transport_type) }}</td>

    <td>
        <a href="{{ route('provider.routes.edit', $route->id) }}" class="btn btn-sm btn-primary">
            {{ __('routes.form.edit') }}
        </a>

        <form action="{{ route('provider.routes.destroy', $route->id) }}" method="POST" style="display:inline;">
            @csrf @method('DELETE')
            <button onclick="return confirm('Delete this route?')" class="btn btn-sm btn-danger">
                {{ __('routes.form.delete') }}
            </button>
        </form>
    </td>
</tr>
@endforeach
</tbody>
</table>

{{ $routes->links() }}

@endsection
