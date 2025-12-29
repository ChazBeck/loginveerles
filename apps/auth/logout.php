<?php
require __DIR__ . '/include/auth_include.php';
auth_init();
auth_logout();
header('Location: login.php');
exit;
