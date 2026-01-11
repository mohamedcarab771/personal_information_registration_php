<?php
// Redirect away from Home page entirely.
// If logged in, go to dashboard; otherwise, go to login.
session_start();

$target = isset($_SESSION['username']) ? 'dashboard.php' : 'login.php';
header("Location: $target");
exit;
?>
