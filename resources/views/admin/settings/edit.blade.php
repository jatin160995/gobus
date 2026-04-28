@extends('admin.layouts.app')

@section('content')

<div class="container-fluid">

    <h3 class="mb-4">
        Edit: {{ ucfirst(str_replace('_', ' ', $setting->key)) }}
    </h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" action="{{ route('admin.settings.update', $setting->id) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Content</label>

                    @if($setting->type === 'html')
                        <textarea name="value" class="form-control" rows="10">
{{ $setting->value }}
                        </textarea>
                    @else
                        <input type="text" name="value"
                               value="{{ $setting->value }}"
                               class="form-control">
                    @endif
                </div>

                <button class="btn btn-success">Save Changes</button>
                <a href="{{ route('admin.settings.index', ['group' => $setting->group]) }}"
                   class="btn btn-secondary">
                    Cancel
                </a>

            </form>

        </div>
    </div>

</div>

@endsection
