@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="status">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
@if (session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
@if ($errors->any())
  <div class="alert alert-danger" role="alert">
    <i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}
  </div>
@endif
