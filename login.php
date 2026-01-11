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
include("conection.php");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Auto-login via remember-me cookie if session is not set
if(empty($_SESSION['username']) && isset($_COOKIE['remember_user'])){
    $_SESSION['username'] = $_COOKIE['remember_user'];
    session_regenerate_id(true);
    header("Location: dashboard.php");
    exit;
}

$notice = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $validCsrf = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    if(!$validCsrf){ die('Invalid CSRF token'); }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $sql = "SELECT id, pssword, status FROM users WHERE username=? LIMIT 1";
    $stmt = $conn->prepare($sql);

    if($stmt){
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if($stmt->num_rows === 1){
            $stmt->bind_result($id, $dbPassword, $status);
            $stmt->fetch();

            $valid = password_verify($password, $dbPassword);

            if($valid){
                $_SESSION['username'] = $username;
                session_regenerate_id(true);

                $remember = !empty($_POST['remember']);
                if($remember){
                    setcookie('remember_user', $username, [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'secure' => false, // set true if HTTPS
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                } else {
                    setcookie('remember_user', '', time() - 3600, '/');
                }

                $loginTime = time();
                $_SESSION['login_time'] = $loginTime;
                @file_put_contents(__DIR__ . '/session_log.txt', sprintf("[%s] login %s\n", date('c', $loginTime), $username), FILE_APPEND);

                header("Location: dashboard.php");
                exit;
            } else {
                $notice = "Invalid login.";
            }
        } else {
            $notice = "Invalid login.";
        }
        $stmt->close();
    } else {
        $notice = "Failed to prepare statement.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
        <title>Login</title>
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
        <a class="nav-link active" href="login.php"><span class="icon">🔐</span>Login</a>
        <a class="nav-link" href="expire.php"><span class="icon">⏳</span>Expire</a>
        <a class="nav-link" href="readme.php"><span class="icon">📄</span>Readme</a>
        <a class="nav-link" href="logout.php"><span class="icon">🚪</span>Logout</a>
    </nav>

    <main>
        <div class="card">
            <h2>Login</h2>
            <?php if(!empty($notice)) echo '<p>'.$notice.'</p>'; ?>
            <form method="post" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
                <div>
                    <label><span class="icon">👤</span> Username</label>
                    <input class="input" type="text" name="username" required>
                </div>
                <div>
                    <label><span class="icon">🔒</span> Password</label>
                    <input class="input" type="password" name="password" required>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">Remember me</label>
                </div>
                <div style="grid-column: 1 / -1; display:flex; gap:10px;">
                    <button class="btn" type="submit"><span class="icon">➡️</span> Login</button>
                    <a class="btn secondary" href="register.php"><span class="icon">📝</span> Register</a>
                </div>
            </form>
        </div>
    </main>
</div>

<footer>© 2026 BIT29 Project</footer>
</body>
</html>
