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

$expireAfter = 5; // minutes
if(isset($_SESSION['last_action'])){
    $secondsInactive = time() - $_SESSION['last_action'];
    if($secondsInactive >= ($expireAfter * 60)){
        $userForLog = $_SESSION['username'] ?? 'guest';
        @file_put_contents(__DIR__ . '/session_log.txt', sprintf("[%s] timeout %s\n", date('c'), $userForLog), FILE_APPEND);
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }
}
$_SESSION['last_action'] = time();

if(!isset($_SESSION['username'])){ header("Location: login.php"); exit(); }
include("conection.php");

$statusMsg = '';

if(isset($_POST['insert']) || isset($_POST['update']) || isset($_POST['delete'])){
    $validCsrf = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    if(!$validCsrf){ die('Invalid CSRF token'); }
}

if(isset($_POST['insert'])){
    $idVal = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
    $country = $_POST['country'] ?? '';

    if($idVal !== null){
        $sql = "INSERT INTO personalinfo (id, name, phone, address, email, sex, age, country) VALUES (?,?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        if($stmt){
            $stmt->bind_param('isssssis', $idVal, $name, $phone, $address, $email, $sex, $age, $country);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $sql = "INSERT INTO personalinfo (name, phone, address, email, sex, age, country) VALUES (?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        if($stmt){
            $stmt->bind_param('sssssis', $name, $phone, $address, $email, $sex, $age, $country);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if(isset($_POST['update']) && $_POST['id'] !== ''){
    $id = (int)$_POST['id'];

    // Pull current values; close the reader before issuing the update to avoid connection sync issues.
    $curName = $curPhone = $curAddress = $curEmail = $curSex = $curAge = $curCountry = null;
    $get = $conn->prepare("SELECT name, phone, address, email, sex, age, country FROM personalinfo WHERE id=?");
    if($get){
        $get->bind_param('i', $id);
        if($get->execute()){
            $get->bind_result($curName, $curPhone, $curAddress, $curEmail, $curSex, $curAge, $curCountry);
            $get->fetch();
        }
        $get->close();
    }

    if($curName !== null){
        $name = (isset($_POST['name']) && $_POST['name'] !== '') ? $_POST['name'] : $curName;
        $phone = (isset($_POST['phone']) && $_POST['phone'] !== '') ? $_POST['phone'] : $curPhone;
        $address = (isset($_POST['address']) && $_POST['address'] !== '') ? $_POST['address'] : $curAddress;
        $email = (isset($_POST['email']) && $_POST['email'] !== '') ? $_POST['email'] : $curEmail;
        $sex = (isset($_POST['sex']) && $_POST['sex'] !== '') ? $_POST['sex'] : $curSex;
        $age = (isset($_POST['age']) && $_POST['age'] !== '') ? (int)$_POST['age'] : (int)$curAge;
        $country = (isset($_POST['country']) && $_POST['country'] !== '') ? $_POST['country'] : $curCountry;

        $sql = "UPDATE personalinfo SET name=?, phone=?, address=?, email=?, sex=?, age=?, country=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        if($stmt){
            $stmt->bind_param('sssssisi', $name, $phone, $address, $email, $sex, $age, $country, $id);
            if($stmt->execute()){
                $statusMsg = 'Record updated.';
            } else {
                $statusMsg = 'Update failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $statusMsg = 'Update failed: ' . $conn->error;
        }
    } else {
        $statusMsg = 'No record found for that ID.';
    }
}

if(isset($_POST['delete']) && !empty($_POST['id'])){
    $sql = "DELETE FROM personalinfo WHERE id=?";
    $stmt = $conn->prepare($sql);
    if($stmt){
        $stmt->bind_param('i', $_POST['id']);
        if($stmt->execute()){
            $statusMsg = $stmt->affected_rows > 0 ? 'Record deleted.' : 'No record found for that ID.';
        } else {
            $statusMsg = 'Delete failed: ' . $stmt->error;
        }
        $stmt->close();
    }
} elseif(isset($_POST['delete'])) {
    $statusMsg = 'Provide an ID to delete.';
}
?>
<!DOCTYPE html>
<html>
<head>
        <title>Dashboard</title>
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

        <a class="nav-link active" href="dashboard.php"><span class="icon">📊</span>Dashboard</a>
        <a class="nav-link" href="register.php"><span class="icon">📝</span>Register</a>
        <a class="nav-link" href="login.php"><span class="icon">🔐</span>Login</a>
        <a class="nav-link" href="expire.php"><span class="icon">⏳</span>Expire</a>
        <a class="nav-link" href="readme.php"><span class="icon">📄</span>Readme</a>
        <a class="nav-link" href="logout.php"><span class="icon">🚪</span>Logout</a>
    </nav>

    <main>
        <div class="card">
            <h2>Dashboard</h2>
            <p>Welcome <?php echo $_SESSION['username']; ?></p>
            <p>User: <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES); ?></p>
            <?php if (!empty($_SESSION['login_time'])): ?>
                <p>Logged in at: <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></p>
            <?php endif; ?>
            <p>Last action: <?php echo date('Y-m-d H:i:s', $_SESSION['last_action']); ?></p>
        </div>

        <div class="card">
            <h3>Manage Personal Info</h3>
            <?php if($statusMsg): ?>
                <div class="notice"><?php echo htmlspecialchars($statusMsg, ENT_QUOTES); ?></div>
            <?php endif; ?>
            <form method="post" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
                <div><label>ID (optional insert / update / delete)</label><input class="input" type="number" name="id"></div>
                <div><label>Name</label><input class="input" type="text" name="name"></div>
                <div><label>Phone</label><input class="input" type="text" name="phone"></div>
                <div><label>Address</label><input class="input" type="text" name="address"></div>
                <div><label>Email</label><input class="input" type="email" name="email"></div>
                <div><label>Sex</label><input class="input" type="text" name="sex"></div>
                <div><label>Age</label><input class="input" type="number" name="age"></div>
                <div><label>Country</label><input class="input" type="text" name="country"></div>
                <div style="grid-column:1/-1; display:flex; gap:10px; flex-wrap:wrap;">
                    <button class="btn" name="insert" type="submit"><span class="icon">➕</span> Insert</button>
                    <button class="btn secondary" name="update" type="submit"><span class="icon">✏️</span> Update</button>
                    <button class="btn danger" name="delete" type="submit" formnovalidate><span class="icon">🗑️</span> Delete</button>
                </div>
            </form>
        </div>

        <div class="card table-wrap">
            <h3>Personal Info List</h3>
            <table>
                <tr><th>ID</th><th>Name</th><th>Phone</th><th>Address</th><th>Email</th><th>Sex</th><th>Age</th><th>Country</th></tr>
                <?php
                $result = $conn->query("SELECT * FROM personalinfo ORDER BY id DESC");
                while($row = $result->fetch_assoc()){
                        echo "<tr><td>".$row['id']."</td><td>".$row['name']."</td><td>".$row['phone']."</td><td>".$row['address']."</td><td>".$row['email']."</td><td>".$row['sex']."</td><td>".$row['age']."</td><td>".$row['country']."</td></tr>";
                }
                ?>
            </table>
        </div>
    </main>
</div>

<footer>© 2026 BIT29 Project</footer>
</body>
</html>
