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
$expireAfter=5; // minutes
if(isset($_SESSION['last_action'])){
    $secondsInactive=time()-$_SESSION['last_action'];
    if($secondsInactive>=($expireAfter*60)){
        session_unset(); session_destroy();
        echo "Session expired. <a href='login.php'>Login again</a>";
        exit();
    }
}
$_SESSION['last_action']=time();
?>
<!DOCTYPE html>
<html>
<head>
        <title>Expire</title>
        <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="icon">🌐</div>
    <div class="header-title">personal information registration</div>
</header>

<div class="layout">
    <nav class="sidebar">
        <div class="nav-section">Navigate</div>

        <a class="nav-link" href="dashboard.php"><span class="icon">📊</span>Dashboard</a>
        <a class="nav-link" href="register.php"><span class="icon">📝</span>Register</a>
        <a class="nav-link" href="login.php"><span class="icon">🔐</span>Login</a>
        <a class="nav-link active" href="expire.php"><span class="icon">⏳</span>Expire</a>
        <a class="nav-link" href="readme.php"><span class="icon">📄</span>Readme</a>
        <a class="nav-link" href="logout.php"><span class="icon">🚪</span>Logout</a>
    </nav>

    <main>
        <div class="card">
            <h2>Session Status</h2>
            <p>Your session is active. Idle timeout is <?php echo $expireAfter; ?> minutes.</p>
        </div>
    </main>
</div>

<footer>© 2026 BIT29 Project</footer>
</body>
</html>
