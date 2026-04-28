        </div> <!-- End of Content -->

        <!-- Footer -->
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; ALO Technologies 2025</span>{{-- {{ date('Y') }} --}}
                </div>
            </div>
        </footer>
        <!-- End of Footer -->
    </div> <!-- End of Content Wrapper -->
</div> <!-- End of Page Wrapper -->

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Bootstrap core JavaScript-->
<script src="{{ asset('admin/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('admin/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('admin/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('admin/js/sb-admin-2.min.js') }}"></script>

@stack('scripts')
</body>
</html>
