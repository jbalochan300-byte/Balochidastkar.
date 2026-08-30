<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
?>
</main>
<footer class="site-footer border-top mt-5" id="contact">
    <div class="container py-4 d-flex flex-column flex-md-row justify-content-between gap-2">
        <span class="text-muted">&copy; <?= date('Y') ?> Balochi Heritage | Designed &amp; Developed by Mehrullah Ali</span>
        <div class="social-links" aria-label="Social links"><a href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Instagram">Instagram</a><a href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Facebook">Facebook</a><a href="https://www.pinterest.com/" target="_blank" rel="noopener" aria-label="Pinterest">Pinterest</a></div>
    </div>
</footer>
<button class="back-to-top" type="button" id="backToTop" aria-label="Back to top" title="Back to top">&uarr;</button>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
