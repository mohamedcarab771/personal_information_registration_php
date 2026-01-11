<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'secure' => false, // set true if serving over HTTPS
	'httponly' => true,
	'samesite' => 'Lax'
]);
session_start();

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$cookieMessage = '';
$cookieValue = $_COOKIE['demo_cookie'] ?? '(none)';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	$validCsrf = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
	if(!$validCsrf){ die('Invalid CSRF token'); }

	if(isset($_POST['delete_cookie'])){
		setcookie('demo_cookie', '', time() - 3600, '/');
		$cookieValue = '(none)';
		$cookieMessage = 'Cookie deleted.';
	} elseif(isset($_POST['cookie_value'])){
		$newVal = $_POST['cookie_value'];
		setcookie('demo_cookie', $newVal, [
			'expires' => time() + 3600,
			'path' => '/',
			'secure' => false, // set true if HTTPS
			'httponly' => true,
			'samesite' => 'Lax'
		]);
		$cookieValue = $newVal;
		$cookieMessage = 'Cookie set/updated.';
	}
}
?>
<!DOCTYPE html>
<html>
<head>
		<title>Readme</title>
		<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
	<div class="header-title">personal information registration</div>
</header>

<div class="layout">
	<nav class="sidebar">
		<div class="nav-section">Navigate</div>

		<a class="nav-link" href="dashboard.php"><span class="icon">📊</span>Dashboard</a>
		<a class="nav-link" href="register.php"><span class="icon">📝</span>Register</a>
		<a class="nav-link" href="login.php"><span class="icon">🔐</span>Login</a>
		<a class="nav-link" href="expire.php"><span class="icon">⏳</span>Expire</a>
		<a class="nav-link active" href="readme.php"><span class="icon">📄</span>Readme</a>
		<a class="nav-link" href="logout.php"><span class="icon">🚪</span>Logout</a>
	</nav>

	<main>
		<div class="card">
			<h2>Project Description</h2>
			<p>This project implements PHP & MySQL concepts: CRUD, sessions, cookies, validation.</p>
			<p>Group Members: tree Students</p>
			<p>name and id</p>
			<p>mohamed muse farah                   id:IT22129166</p>
			<p>Abdishakur Adan Mohamud              id:IT22129047</p>
			<p>Abdifatah Osman Ahmed                id:IT00000000</p>
	
		</div>

		
</div>

<footer>© 2026 BIT29A Project</footer>
</body>
</html>
