<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>GoBus Login</title>
  <link href="{{ asset('admin/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
  <link href="{{ asset('admin/css/sb-admin-2.min.css') }}" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-xl-5 col-lg-6 col-md-8">
      <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-5">
          <h1 class="h4 text-gray-900 mb-4 text-center">Login to GoBus</h1>

          @if($errors->any())
              <div class="alert alert-danger">
                  {{ $errors->first() }}
              </div>
          @endif

          <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <div class="form-group">
              <input type="email" name="email" class="form-control form-control-user" placeholder="Email Address" required>
            </div>
            <div class="form-group">
              <input type="password" name="password" class="form-control form-control-user" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-user btn-block">Login</button>
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
