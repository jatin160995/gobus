@extends('admin.layouts.app')
@section('title', 'Create Setting')

@section('content')

<div class="container-fluid">

    <h3 class="mb-4">Create New Setting</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" action="{{ route('settings.store') }}">
                @csrf

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Key *</label>
                        <input type="text" name="key" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Label</label>
                        <input type="text" name="label" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Group *</label>
                        <input type="text" name="group" class="form-control" placeholder="api, legal, payments" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Type *</label>
                        <select name="type" class="form-control">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="password">Password</option>
                            <option value="editor">Rich Text</option>
                            <option value="boolean">Boolean</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Sensitive?</label>
                        <select name="is_sensitive" class="form-control">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label>Default Value</label>
                        <textarea name="value" class="form-control"></textarea>
                    </div>

                </div>

                <button class="btn btn-primary">Create Setting</button>

            </form>

        </div>
    </div>

</div>

@endsection
