<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

maatlas_admin_logout();
maatlas_admin_redirect('/admin/login.php');
