@extends('admin.providers.show')

@section('provider_tab')

<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-car mr-2" style="color:#FF8C00;"></i> Chauffeur Vehicles</h5>
        <span class="badge badge-secondary">{{ $vehicles->count() }} total</span>
    </div>

    <div class="card-body p-0">
        @if($vehicles->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background:#f8f9fc;">
                        <tr>
                            <th class="pl-4">#</th>
                            <th>Photo</th>
                            <th>Vehicle</th>
                            <th>Category</th>
                            <th>Plate</th>
                            <th>Seats</th>
                            <th>Transmission</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vehicles as $v)
                            <tr>
                                <td class="pl-4">{{ $v->id }}</td>
                                <td>
                                    @php $img = $v->images->where('image_type','front')->first() ?? $v->images->first(); @endphp
                                    @if($img)
                                        <img src="{{ asset('storage/' . $img->image_path) }}"
                                            width="70" height="50"
                                            class="rounded"
                                            style="object-fit:cover;">
                                    @else
                                        <div class="rounded d-flex align-items-center justify-content-center"
                                            style="width:70px;height:50px;background:#f0f0f0;">
                                            <i class="fas fa-car text-muted"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $v->brand }} {{ $v->model }}</div>
                                    <small class="text-muted">{{ $v->year }} · {{ ucfirst($v->color ?? '-') }} · {{ ucfirst($v->fuel_type ?? '-') }}</small>
                                </td>
                                <td>
                                    <span class="badge"
                                        style="background-color:#fff8f0; color:#FF8C00; border:1px solid #FFD699; padding:4px 10px; border-radius:20px;">
                                        {{ ucfirst($v->category) }}
                                    </span>
                                </td>
                                <td><code>{{ $v->plate_number }}</code></td>
                                <td><i class="fas fa-user-friends fa-sm text-muted mr-1"></i>{{ $v->seats }}</td>
                                <td>{{ ucfirst($v->transmission) }}</td>
                                <td>
                                    <span class="badge badge-{{ $v->is_active ? 'success' : 'secondary' }}">
                                        {{ $v->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="fas fa-car fa-3x mb-3 d-block" style="color:#e0e0e0;"></i>
                No vehicles found for this provider.
            </div>
        @endif
    </div>
</div>

@endsection