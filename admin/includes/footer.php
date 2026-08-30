<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
?>
</main>
    </div>
    <footer class="admin-footer">
        <span>Admin panel foundation</span>
        <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></span>
    </footer>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
