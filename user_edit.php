<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';
require_once 'includes/user_photo.php';

$pageTitle = 'Edit User';
$errors = [];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tbl_users WHERE ID = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'User not found.'];
    header("Location: users.php");
    exit;
}

$form = $row;
$usertypes = $pdo->query("SELECT ID, Usertype FROM tbl_usertype ORDER BY Usertype")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['USERNAME'] = trim($_POST['USERNAME'] ?? '');
    $form['FULLNAME'] = trim($_POST['FULLNAME'] ?? '');
    $form['tbl_usertype_id'] = $_POST['tbl_usertype_id'] ?? '';
    $newPassword = $_POST['PASSWORD'] ?? '';
    $confirm     = $_POST['CONFIRM_PASSWORD'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $form['USERNAME']) || strpos($form['USERNAME'], '..') !== false) {
        $errors[] = "Username must be 3-50 characters: letters, numbers, underscore or dot only.";
    }
    if ($form['FULLNAME'] === '')       $errors[] = "Full name is required.";
    if ($form['tbl_usertype_id'] === '') $errors[] = "User type is required.";

    // Password is optional on edit — only validate if the admin is changing it
    if ($newPassword !== '' || $confirm !== '') {
        if (strlen($newPassword) < 6) $errors[] = "New password must be at least 6 characters.";
        if ($newPassword !== $confirm) $errors[] = "Passwords do not match.";
    }

    [$hasPhoto, $photoError] = validateUserPhoto('imageUpload');
    if ($photoError) $errors[] = $photoError;

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_users WHERE USERNAME = ? AND ID != ?");
        $check->execute([$form['USERNAME'], $id]);
        if ($check->fetch()) $errors[] = "That username is already taken.";
    }

    if (empty($errors)) {
        if ($newPassword !== '') {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE tbl_users SET USERNAME = ?, FULLNAME = ?, tbl_usertype_id = ?, PASSWORD = ? WHERE ID = ?");
            $stmt->execute([$form['USERNAME'], $form['FULLNAME'], $form['tbl_usertype_id'], $hash, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE tbl_users SET USERNAME = ?, FULLNAME = ?, tbl_usertype_id = ? WHERE ID = ?");
            $stmt->execute([$form['USERNAME'], $form['FULLNAME'], $form['tbl_usertype_id'], $id]);
        }

        // Photo is named after the username: follow a rename, then apply a newly chosen photo
        if ($row['USERNAME'] !== $form['USERNAME']) renameUserPhoto($row['USERNAME'], $form['USERNAME']);
        if ($hasPhoto) {
            $saveErr = saveUserPhoto($form['USERNAME'], 'imageUpload');
            if ($saveErr) $_SESSION['flash'] = ['type' => 'error', 'message' => "User updated, but the photo was not saved: $saveErr"];
        }

        // Keep the session display name in sync if the admin edited their own account
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['username'] = $form['USERNAME'];
            $_SESSION['fullname'] = $form['FULLNAME'];
        }

        if (empty($_SESSION['flash'])) $_SESSION['flash'] = ['type' => 'success', 'message' => 'User updated successfully.'];
        header("Location: users.php");
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Edit User</h1><p>Update this user's account details. Leave the password fields blank to keep it unchanged.</p></div>
</div>

<div class="panel-card" style="max-width:520px;">
  <div class="panel-card-head"><span class="bar"></span><h2>User Details</h2></div>
  <div class="panel-card-body">
    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="user_edit.php?id=<?= $id ?>" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="field">
        <label for="FULLNAME">Full Name <span class="req">*</span></label>
        <input type="text" id="FULLNAME" name="FULLNAME" required value="<?= htmlspecialchars($form['FULLNAME'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="USERNAME">Username <span class="req">*</span></label>
        <input type="text" id="USERNAME" name="USERNAME" required value="<?= htmlspecialchars($form['USERNAME']) ?>">
      </div>
      <div class="field">
        <label for="tbl_usertype_id">User Type <span class="req">*</span></label>
        <select id="tbl_usertype_id" name="tbl_usertype_id" required>
          <option value="">-- Select User Type --</option>
          <?php foreach ($usertypes as $ut): ?>
            <option value="<?= $ut['ID'] ?>" <?= ((int)$form['tbl_usertype_id'] === (int)$ut['ID']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($ut['Usertype']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="imageUpload">Photo</label>
        <div style="margin-bottom:8px;"><?= userPhotoHtml($row['USERNAME'], 'thumb thumb-lg') ?></div>
        <input type="file" id="imageUpload" name="imageUpload" accept="image/jpeg,image/png,image/gif">
        <small style="color:#6b7280;">Leave empty to keep the current photo.</small>
      </div>
      <div class="field">
        <label for="PASSWORD">New Password</label>
        <input type="password" id="PASSWORD" name="PASSWORD" minlength="6" placeholder="Leave blank to keep current password">
      </div>
      <div class="field">
        <label for="CONFIRM_PASSWORD">Confirm New Password</label>
        <input type="password" id="CONFIRM_PASSWORD" name="CONFIRM_PASSWORD" minlength="6">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="users.php" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
