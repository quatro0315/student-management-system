<?php
require_once 'config.php';


if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("
            SELECT u.*, ut.Usertype AS USERTYPE_NAME
            FROM tbl_users u
            LEFT JOIN tbl_usertype ut ON ut.ID = u.tbl_usertype_id
            WHERE u.USERNAME = ? LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['PASSWORD'])) {
            
            session_regenerate_id(true);

            $_SESSION['user_id']       = $user['ID'];
            $_SESSION['username']      = $user['USERNAME'];
            $_SESSION['fullname']      = $user['FULLNAME'] ?: $user['USERNAME'];
            $_SESSION['usertype_id']   = $user['tbl_usertype_id'];
            $_SESSION['usertype_name'] = $user['USERTYPE_NAME'] ?? 'User';

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Student Management System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
  <div class="login-card">
    <div class="login-brand">
      <span class="brand-icon">🎓</span>
      <h1>Student Management System</h1>
      <p>Sign in to continue</p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="login-form">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" placeholder="Enter username" autofocus required
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter password" required>

      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>

    <p class="login-hint">Don't have an account? <a href="register.php">Sign up</a></p>

  </div>
</body>
</html>
