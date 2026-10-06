    </div> <!-- End of Content -->

    <footer class="go-footer">
      <span>&copy; {{ date('Y') }} ALO Technologies · GO</span>
      <span>TaxiGo · Douala &amp; Yaoundé Nsimalen</span>
    </footer>
  </div> <!-- End of Content Wrapper -->
</div> <!-- End of Page Wrapper -->

<!-- Bootstrap core JavaScript-->
<script src="{{ asset('admin/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('admin/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('admin/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('admin/js/sb-admin-2.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Mobile menu
  document.querySelectorAll('[data-go-nav-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () { document.body.classList.toggle('go-nav-open'); });
  });
  document.querySelectorAll('[data-go-nav-close]').forEach(function (el) {
    el.addEventListener('click', function () { document.body.classList.remove('go-nav-open'); });
  });
</script>

@stack('scripts')
</body>
</html>
