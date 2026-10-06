<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in · GO Admin</title>
  <link rel="icon" type="image/png" href="{{ asset('favicon-go.png') }}">
  <link href="{{ asset('admin/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="{{ asset('admin/css/sb-admin-2.min.css') }}" rel="stylesheet">
  <link href="{{ asset('admin/css/theme.css') }}?v={{ filemtime(public_path('admin/css/theme.css')) }}" rel="stylesheet">
</head>
<body>
<div class="go-auth">
  <section class="go-auth-art">
    <div class="d-flex align-items-center gap-3">
      <img src="{{ asset('admin/img/go-logo.png') }}" alt="GO" width="44" height="44" style="border-radius:12px">
      <div>
        <div class="fw-bold" style="font-size:18px">GO Admin</div>
        <div style="color:#9DB4BD;font-size:12.5px">ALO Technologies</div>
      </div>
    </div>

    <div>
      <h2>Premium airport transfers, run from one place.</h2>
      <p>Bookings, drivers, fares and every payout for TaxiGo at Douala and Yaoundé Nsimalen.</p>
      <div class="go-auth-points">
        <div><i class="fas fa-plane-arrival"></i>Fixed fares for every zone, day and night</div>
        <div><i class="fas fa-mobile-alt"></i>100% cashless with MTN MoMo and Orange Money</div>
        <div><i class="fas fa-random"></i>Revenue split to every partner, instantly</div>
      </div>
    </div>

    <div style="color:#7E97A2;font-size:12px">&copy; {{ date('Y') }} ALO Technologies · Douala, Cameroon</div>
  </section>

  <section class="go-auth-form">
    <div class="go-auth-box">
      <img src="{{ asset('admin/img/go-logo.png') }}" alt="GO" width="48" height="48" style="border-radius:12px">
      <h1>Welcome back</h1>
      <p class="lead-sub">Sign in to the GO admin panel.</p>

      @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('login.submit') }}">
        @csrf
        <div class="form-group">
          <label for="email">Email address</label>
          <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="you@alotechnologies.com" autocomplete="username" required autofocus>
        </div>
        <div class="form-group mb-4">
          <label for="password">Password</label>
          <input id="password" type="password" name="password" class="form-control" placeholder="••••••••" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-lg btn-block">Sign in</button>
      </form>
    </div>
  </section>
</div>
</body>
</html>
