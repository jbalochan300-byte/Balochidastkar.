<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

redirect(url(isAdminLoggedIn() ? 'admin/dashboard.php' : 'admin/login.php'));
