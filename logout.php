<?php
require __DIR__ . '/include/jwt_include.php';
jwt_init();
jwt_logout();
header('Location: login.php');
exit;
