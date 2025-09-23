        </main> <!-- End of main content -->
    </div> <!-- End of content-wrapper -->
</div> <!-- End of page-wrapper -->

<!-- JAVASCRIPT LIBRARIES -->

<!-- Bootstrap 5 JS Bundle (includes Popper.js) -->
<!-- THIS SCRIPT IS ESSENTIAL for modals to work. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Flatpickr JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Layout Controller Script for Sidebar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pageWrapper = document.querySelector('.page-wrapper');
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && pageWrapper) {
        sidebar.addEventListener('mouseenter', () => pageWrapper.classList.add('sidebar-expanded'));
        sidebar.addEventListener('mouseleave', () => pageWrapper.classList.remove('sidebar-expanded'));
    }
});
</script>

</body>
</html>

