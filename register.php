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

include("conection.php");

$notice = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $validCsrf = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    if(!$validCsrf){ die('Invalid CSRF token'); }

    $fname = $_POST['fname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $status = $_POST['status'] ?? '';

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (fname,lname,username,pssword,email,status) VALUES (?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    if($stmt){
        $stmt->bind_param('ssssss', $fname, $lname, $username, $hashed, $email, $status);
        if($stmt->execute()){ $notice = "User registered successfully."; }
        $stmt->close();
    } else {
        $notice = "Failed to prepare statement.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
        <title>Register</title>
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
        <a class="nav-link active" href="register.php"><span class="icon">📝</span>Register</a>
        <a class="nav-link" href="login.php"><span class="icon">🔐</span>Login</a>
        <a class="nav-link" href="expire.php"><span class="icon">⏳</span>Expire</a>
        <a class="nav-link" href="readme.php"><span class="icon">📄</span>Readme</a>
        <a class="nav-link" href="logout.php"><span class="icon">🚪</span>Logout</a>
    </nav>

    <main>
        <div class="card">
            <h2>Register</h2>
            <?php if(!empty($notice)) echo '<p>'.$notice.'</p>'; ?>
            <form method="post" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
                <div>
                    <label><span class="icon">🧑</span> First Name</label>
                    <input class="input" type="text" name="fname" required>
                </div>
                <div>
                    <label><span class="icon">🧑</span> Last Name</label>
                    <input class="input" type="text" name="lname" required>
                </div>
                <div>
                    <label><span class="icon">👤</span> Username</label>
                    <input class="input" type="text" name="username" required>
                </div>
                <div>
                    <label><span class="icon">🔒</span> Password</label>
                    <input class="input" type="password" name="password" required>
                </div>
                <div>
                    <label><span class="icon">✉️</span> Email</label>
                    <input class="input" type="email" name="email">
                </div>
                <div>
                    <label><span class="icon">⚙️</span> Status</label>
                    <select class="input" name="status">
                        <option>Active</option>
                        <option>Not Active</option>
                    </select>
                </div>
                <div style="grid-column: 1 / -1; display:flex; gap:10px; flex-wrap:wrap;">
                    <button class="btn" type="submit"><span class="icon">✅</span> Create Account</button>
                    <a class="btn secondary" href="login.php"><span class="icon">➡️</span> Login</a>
                </div>
            </form>
        </div>
    </main>
</div>

<footer>© 2026 BIT29 Project</footer>
</body>
</html>
