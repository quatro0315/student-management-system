<?php
require_once 'config.php';
require_once 'includes/user_photo.php';

// Already signed in? No need to register.
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$form = ['FULLNAME' => '', 'USERNAME' => '', 'USERTYPE_ID' => ''];

$usertypes = $pdo->query("SELECT ID, Usertype FROM tbl_usertype ORDER BY ID")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['FULLNAME'] = trim($_POST['FULLNAME'] ?? '');
    $form['USERNAME'] = trim($_POST['USERNAME'] ?? '');
    $form['USERTYPE_ID'] = trim($_POST['USERTYPE_ID'] ?? '');
    $password = $_POST['PASSWORD'] ?? '';
    $confirm  = $_POST['CONFIRM_PASSWORD'] ?? '';

    if ($form['FULLNAME'] === '') {
        $errors[] = "Full name is required.";
    } elseif (mb_strlen($form['FULLNAME']) > 100) {
        $errors[] = "Full name must be 100 characters or fewer.";
    }

    if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $form['USERNAME'])) {
        $errors[] = "Username must be 3-50 characters: letters, numbers, underscore or dot only.";
    }

    if (ALLOW_ROLE_SELECTION_ON_SIGNUP) {
        $validIds = array_map('strval', array_column($usertypes, 'ID'));
        if (!in_array($form['USERTYPE_ID'], $validIds, true)) {
            $errors[] = "Please choose a user type.";
        }
    }

    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    // Profile photo (optional) - validated now, saved after the account is created
    [$hasPhoto, $photoError] = validateUserPhoto('imageUpload');
    if ($photoError) $errors[] = $photoError;

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_users WHERE USERNAME = ?");
        $check->execute([$form['USERNAME']]);
        if ($check->fetch()) $errors[] = "That username is already taken.";
    }

    if (empty($errors)) {
        if (ALLOW_ROLE_SELECTION_ON_SIGNUP) {
            // User type chosen on the form (validated above against tbl_usertype)
            $studentTypeId = (int)$form['USERTYPE_ID'];
        } else {
            // Sign-up locked down: self-registered accounts are ALWAYS the lowest-privilege Student type.
            $ut = $pdo->prepare("SELECT ID FROM tbl_usertype WHERE Usertype = 'Student' LIMIT 1");
            $ut->execute();
            $studentTypeId = $ut->fetchColumn() ?: null;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_users (USERNAME, PASSWORD, FULLNAME, tbl_usertype_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $form['USERNAME'],
                password_hash($password, PASSWORD_DEFAULT),
                $form['FULLNAME'],
                $studentTypeId,
            ]);
            $msg = 'Account created! You can now log in.';
            if ($hasPhoto) {
                $saveErr = saveUserPhoto($form['USERNAME'], 'imageUpload');
                $msg .= $saveErr ? " (Photo not saved: $saveErr)" : ' The image was uploaded successfully.';
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => $msg];
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            // 23000 = duplicate key (two people grabbed the same username at once)
            if ($e->getCode() === '23000') {
                $errors[] = "That username is already taken.";
            } else {
                throw $e;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up - Student Management System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
  <div class="login-card">
    <div class="login-brand">
      <span class="brand-icon">🎓</span>
      <h1>Create an Account</h1>
      <p>Sign up to get started</p>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
    <?php endif; ?>

    <form method="POST" action="register.php" class="login-form" enctype="multipart/form-data">
      <label for="FULLNAME">Full Name</label>
      <input type="text" id="FULLNAME" name="FULLNAME" placeholder="Juan Dela Cruz" maxlength="100" autofocus required
             value="<?= htmlspecialchars($form['FULLNAME']) ?>">

      <label for="USERNAME">Username</label>
      <input type="text" id="USERNAME" name="USERNAME" placeholder="Choose a username" maxlength="50"
             pattern="[A-Za-z0-9_.]{3,50}" title="3-50 characters: letters, numbers, underscore or dot" required
             value="<?= htmlspecialchars($form['USERNAME']) ?>">

      <?php if (ALLOW_ROLE_SELECTION_ON_SIGNUP): ?>
      <label for="USERTYPE_ID">User Type</label>
      <select id="USERTYPE_ID" name="USERTYPE_ID" required>
        <option value="">-- Select user type --</option>
        <?php foreach ($usertypes as $ut): ?>
          <option value="<?= (int)$ut['ID'] ?>" <?= (string)$form['USERTYPE_ID'] === (string)$ut['ID'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($ut['Usertype']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>

      <label for="imageUpload">Photo</label>
      <input type="file" id="imageUpload" name="imageUpload" accept="image/jpeg,image/png,image/gif">

      <label for="PASSWORD">Password</label>
      <input type="password" id="PASSWORD" name="PASSWORD" placeholder="At least 6 characters" minlength="6" required>

      <label for="CONFIRM_PASSWORD">Confirm Password</label>
      <input type="password" id="CONFIRM_PASSWORD" name="CONFIRM_PASSWORD" placeholder="Re-enter password" minlength="6" required>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top:18px;">Sign Up</button>
    </form>

    <p class="login-hint">Already have an account? <a href="login.php">Log in</a></p>
  </div>
</body>
</html>
