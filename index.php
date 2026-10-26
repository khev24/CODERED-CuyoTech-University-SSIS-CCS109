<?php
require_once __DIR__ . '/includes/auth.php';
$user = current_user();
redirect($user ? portal($user) . '/dashboard.php' : 'auth/login.php');
