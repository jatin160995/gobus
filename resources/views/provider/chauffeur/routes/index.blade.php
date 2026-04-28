@extends('provider.layouts.app')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between mb-4">
        <h1 class="h4 text-gray-800">Chauffeur Routes</h1>
        <a href="{{ route('provider.chauffeur.routes.create') }}"
           class="btn btn-primary btn-sm">
            + Add Route
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>To</th>
                        <th>Distance</th>
                        <th>Status</th>
                        <th width="120">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($routes as $route)
                    <tr>
                        <td>{{ $route->fromCity->name }}</td>
                        <td>{{ $route->toCity->name }}</td>
                        <td>{{ $route->distance_km }} km</td>
                        <td>
                            @if($route->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="d-flex gap-2">

                            <a href="{{ route('provider.chauffeur.routes.edit',$route->id) }}"
                            class="btn btn-sm btn-info">
                                Edit
                            </a>

                            <form action="{{ route('provider.chauffeur.routes.destroy',$route->id) }}"
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this route?');">

                                @csrf
                                @method('DELETE')

                                <button class="btn btn-sm btn-danger">
                                    Delete
                                </button>

                            </form>

                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            {{ $routes->links() }}
        </div>
    </div>
</div>
@endsection