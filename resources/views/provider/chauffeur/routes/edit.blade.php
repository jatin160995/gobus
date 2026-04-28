@extends('provider.layouts.app')

@section('content')
<div class="container-fluid">

    <h1 class="h4 mb-4 text-gray-800">Edit Route</h1>

    <form method="POST"
          action="{{ route('provider.chauffeur.routes.update',$route->id) }}">
        @csrf
        @method('PUT')

        <div class="card shadow mb-4">
            <div class="card-header">
                Route Information
            </div>
            <div class="card-body row">

                <div class="col-md-6 mb-3">
                    <label>From Location</label>
                    <select name="from_city_id" class="form-control" required>
                        @foreach($cities as $location)
                            <option value="{{ $location->id }}"
                                {{ $route->from_city_id == $location->id ? 'selected':'' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>To Location</label>
                    <select name="to_city_id" class="form-control" required>
                        @foreach($cities as $location)
                            <option value="{{ $location->id }}"
                                {{ $route->to_city_id == $location->id ? 'selected':'' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                 <div class="col-md-6 mb-3">
                    <label>Distance (KM)</label>
                    <input type="number"
                           step="0.01"
                           name="distance_km"
                           class="form-control"
                           placeholder="Optional" value="{{ $route->distance_km }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Estimated Duration (Minutes)</label>
                    <input type="number"
                           name="estimated_duration_minutes"
                           class="form-control"
                           placeholder="Optional" value="{{ $route->estimated_duration_minutes }}">
                </div>

                <div class="col-md-12">
                    <div class="form-check">
                        <input type="checkbox"
                            name="is_active"
                            value="1"
                            class="form-check-input"
                            {{ $route->is_active == 1 ? 'checked' : 'unchecked' }}>
                        <label class="form-check-label">
                            Active Route
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- PRICING TABLE --}}
        <div class="card shadow mb-4">
            <div class="card-header">
                Route Pricing
            </div>

            <div class="card-body table-responsive">

                @php
                    $categories = ['standard','executive','premium','suv'];
                @endphp

                <table class="table table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Category</th>
                            <th>One Way</th>
                            <th>Round Trip (Same Day)</th>
                            <th>Per Day (Multi Day)</th>
                            <th>Currency</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach($categories as $category)

                            @php
                                $price = $route->prices
                                    ->where('vehicle_category',$category)
                                    ->first();
                            @endphp

                            <tr>
                                <td class="text-capitalize font-weight-bold">
                                    {{ $category }}
                                </td>

                                <td>
                                    <input type="number"
                                        step="0.01"
                                        class="form-control"
                                        name="prices[{{ $category }}][one_way_price]"
                                        value="{{ $price->one_way_price ?? '' }}"
                                        placeholder="0.00">
                                </td>

                                <td>
                                    <input type="number"
                                        step="0.01"
                                        class="form-control"
                                        name="prices[{{ $category }}][round_trip_price]"
                                        value="{{ $price->round_trip_price ?? '' }}"
                                        placeholder="0.00">
                                </td>

                                <td>
                                    <input type="number"
                                        step="0.01"
                                        class="form-control"
                                        name="prices[{{ $category }}][per_day_price]"
                                        value="{{ $price->per_day_price ?? '' }}"
                                        placeholder="0.00">
                                </td>

                                <td>
                                    <input type="text"
                                        class="form-control"
                                        name="prices[{{ $category }}][currency]"
                                        value="{{ $price->currency ?? 'XAF' }}" disabled>
                                </td>

                                <td class="text-center">
                                    <input type="checkbox"
                                        name="prices[{{ $category }}][is_active]"
                                        {{ ($price->is_active ?? false) ? 'checked':'' }}>
                                </td>

                            </tr>

                        @endforeach

                    </tbody>
                </table>

            </div>
        </div>

        <div class="text-right">
            <button class="btn btn-primary">Update Route</button>
        </div>

    </form>
</div>
@endsection