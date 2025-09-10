<?php
// This is the closing part of every page.
// It includes the footer content and necessary global JavaScript files.
?>
        </main> <!-- End of the main content area opened in header.php -->

    </div> <!-- End of the .d-flex wrapper opened in header.php -->
</div> <!-- End of the .page-wrapper opened in header.php -->

<!-- Global Footer -->
<footer class="mt-auto py-3 bg-light text-center">
    <div class="container">
        <span class="text-muted">&copy; <?= date("Y"); ?> <?= APP_NAME; ?>. All Rights Reserved.</span>
    </div>
</footer>

<!-- JAVASCRIPT LIBRARIES -->

<!-- Bootstrap 5 JS Bundle (includes Popper.js for tooltips, popovers, etc.) -->
<!-- This is ESSENTIAL for interactive components like modals and dropdowns to work. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Your Custom Application-wide JavaScript -->
<!-- This file should be included last so it can use the Bootstrap JS functions. -->
<script src="<?= BASE_URL; ?>assets/js/script.js"></script>

</body>
</html>