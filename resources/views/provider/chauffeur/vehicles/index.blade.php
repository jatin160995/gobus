@extends('provider.layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">Chauffeur Vehicles</h4>
        <a href="{{ route('provider.chauffeur.vehicles.create') }}" class="btn btn-primary px-4">
            + Add Vehicle
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Image</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Category</th>
                        <th>Seats</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($vehicles as $vehicle)
                    <tr>
                        <td width="80">
                            @php
                                $front = $vehicle->images->where('image_type','front')->first();
                            @endphp

                            @if($front)
                                <img src="{{ asset('storage/'.$front->image_path) }}"
                                     class="rounded"
                                     width="60"
                                     height="40"
                                     style="object-fit:cover;">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                     style="width:60px;height:40px;">
                                     —
                                </div>
                            @endif
                        </td>

                        <td>{{ $vehicle->brand }}</td>
                        <td>{{ $vehicle->model }}</td>
                        <td>
                            <span class="badge bg-info text-dark">
                                {{ ucfirst($vehicle->category) }}
                            </span>
                        </td>
                        <td>{{ $vehicle->seats }}</td>
                        <td>
                            @if($vehicle->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('provider.chauffeur.vehicles.edit',$vehicle->id) }}"
                               class="btn btn-sm btn-outline-primary">
                                Edit
                            </a>

                            <form action="{{ route('provider.chauffeur.vehicles.destroy',$vehicle->id) }}"
                                  method="POST"
                                  class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Delete vehicle?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            No vehicles found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

        </div>
    </div>

</div>
@endsection