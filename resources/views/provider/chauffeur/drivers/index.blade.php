@extends('provider.layouts.app')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between mb-4">
        <h1 class="h4 text-gray-800">Drivers</h1>
        <a href="{{ route('provider.chauffeur.drivers.create') }}"
           class="btn btn-primary btn-sm">
            + Add Driver
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>License</th>
                        <th>Expiry</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th width="140">Action</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach($drivers as $driver)
                        <tr>
                            <td>{{ $driver->name }}</td>
                            <td>{{ $driver->phone }}</td>
                            <td>{{ $driver->license_number }}</td>
                            <td>{{ $driver->license_expiry->format('d M Y') }}</td>
                            <td>{{ $driver->rating }}</td>
                            <td>
                                @if($driver->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('provider.chauffeur.drivers.edit',$driver->id) }}"
                                   class="btn btn-sm btn-info">Edit</a>

                                <form method="POST"
                                      action="{{ route('provider.chauffeur.drivers.destroy',$driver->id) }}"
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this driver?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>

            {{ $drivers->links() }}

        </div>
    </div>
</div>
@endsection