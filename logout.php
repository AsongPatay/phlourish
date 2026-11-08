<?php
require __DIR__ . '/includes/auth.php';
ph_logout();
header('Location: login.php');
exit;
