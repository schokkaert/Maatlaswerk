<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

maatlas_admin_logout();
header('Location: /admin/login.php');
exit;
