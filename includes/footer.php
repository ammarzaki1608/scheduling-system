        </main> <!-- End of main content -->
    </div> <!-- End of content-wrapper -->
</div> <!-- End of page-wrapper -->

<!-- JAVASCRIPT LIBRARIES -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- UPDATED: New Layout Controller Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pageWrapper = document.querySelector('.page-wrapper');
    const sidebar = document.querySelector('.sidebar');

    if (sidebar && pageWrapper) {
        sidebar.addEventListener('mouseenter', () => {
            pageWrapper.classList.add('sidebar-expanded');
        });

        sidebar.addEventListener('mouseleave', () => {
            pageWrapper.classList.remove('sidebar-expanded');
        });
    }
});
</script>

</body>
</html>

