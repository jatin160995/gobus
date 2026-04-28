<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create User - GoBus</title>
  <link href="{{ asset('admin/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
  <link href="{{ asset('admin/css/sb-admin-2.min.css') }}" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-xl-6 col-lg-8 col-md-9">
      <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-5">
          <h1 class="h4 text-gray-900 mb-4 text-center">Create User (Admin / Provider)</h1>

          @if(session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          @if($errors->any())
              <div class="alert alert-danger">
                  <ul class="mb-0">
                      @foreach($errors->all() as $error)
                          <li>{{ $error }}</li>
                      @endforeach
                  </ul>
              </div>
          @endif

          <form method="POST" action="{{ route('register.submit') }}">
            @csrf

            <div class="form-group">
              <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-user" placeholder="Full Name" required>
            </div>

            <div class="form-group">
              <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-user" placeholder="Email Address" required>
            </div>

            <div class="form-group">
              <input type="text" name="phone" value="{{ old('phone') }}" class="form-control form-control-user" placeholder="Phone Number" required>
            </div>

            <div class="form-group row">
              <div class="col-sm-6 mb-3 mb-sm-0">
                <input type="password" name="password" class="form-control form-control-user" placeholder="Password" required>
              </div>
              <div class="col-sm-6">
                <input type="password" name="password_confirmation" class="form-control form-control-user" placeholder="Confirm Password" required>
              </div>
            </div>

            <div class="form-group">
              <select name="role" class="form-control" required>
                <option value="">-- Select Role --</option>
                <option value="admin" {{ old('role')=='admin' ? 'selected' : '' }}>Admin</option>
                <option value="provider" {{ old('role')=='provider' ? 'selected' : '' }}>Provider</option>
              </select>
            </div>

            <button type="submit" class="btn btn-primary btn-user btn-block">
              Create User
            </button>

            <hr>
            <div class="text-center">
              <a class="small" href="{{ route('login') }}">Already have an account? Login</a>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<script src="{{ asset('admin/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('admin/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('admin/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('admin/js/sb-admin-2.min.js') }}"></script>
</body>
</html>
