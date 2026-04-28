@extends('admin.layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Cities</h3>
        <a href="{{ route('admin.cities.create') }}" class="btn btn-primary btn-sm">+ Add City</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>City Code</th>
                        <th>Country</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($cities as $c)
                        <tr>
                            <td>{{ $c->id }}</td>
                            <td>{{ $c->name }}</td>
                            <td>{{ $c->city_code }}</td>
                            <td>{{ $c->country ?? '-' }}</td>
                            <td>{{ $c->created_at }}</td>
                            <td>
                                <a href="{{ route('admin.cities.edit', $c->id) }}" 
                                    class="btn btn-sm btn-info">Edit</a>

                                <form action="{{ route('admin.cities.delete', $c->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf @method('DELETE')

                                    <button class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this city?')">
                                        Delete
                                    </button>
                                </form>

                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

        <div class="card-footer">
            {{ $cities->links() }}
        </div>
    </div>

</div>

@endsection
