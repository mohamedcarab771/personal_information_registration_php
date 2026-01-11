<?php
session_start();
$userForLog = $_SESSION['username'] ?? ($_COOKIE['remember_user'] ?? 'guest');
@file_put_contents(__DIR__ . '/session_log.txt', sprintf("[%s] logout %s\n", date('c'), $userForLog), FILE_APPEND);
session_unset();
session_destroy();
setcookie('remember_user', '', time() - 3600, '/');
header("Location: login.php");
?>
