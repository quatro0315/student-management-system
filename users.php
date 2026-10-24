<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';
require_once 'includes/user_photo.php';

$pageTitle = 'Users';
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$usertypes = $pdo->query("SELECT ID, Usertype FROM tbl_usertype ORDER BY Usertype")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $username = trim($_POST['USERNAME'] ?? '');
    $fullname = trim($_POST['FULLNAME'] ?? '');
    $usertypeId = $_POST['tbl_usertype_id'] ?? '';
    $password = $_POST['PASSWORD'] ?? '';
    $confirm  = $_POST['CONFIRM_PASSWORD'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $username) || strpos($username, '..') !== false) {
        $errors[] = "Username must be 3-50 characters: letters, numbers, underscore or dot only.";
    }
    if ($fullname === '')   $errors[] = "Full name is required.";
    if ($usertypeId === '') $errors[] = "User type is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    [$hasPhoto, $photoError] = validateUserPhoto('imageUpload');
    if ($photoError) $errors[] = $photoError;

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_users WHERE USERNAME = ?");
        $check->execute([$username]);
        if ($check->fetch()) $errors[] = "That username is already taken.";
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO tbl_users (USERNAME, PASSWORD, FULLNAME, tbl_usertype_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $fullname, $usertypeId]);
        $msg = 'User added successfully.';
        if ($hasPhoto) {
            $saveErr = saveUserPhoto($username, 'imageUpload');
            $msg .= $saveErr ? " (Photo not saved: $saveErr)" : ' The image was uploaded successfully.';
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => $msg];
        header("Location: users.php");
        exit;
    }
}

$q = trim($_GET['q'] ?? '');
$sql = "
    SELECT u.*, ut.Usertype
    FROM tbl_users u
    LEFT JOIN tbl_usertype ut ON ut.ID = u.tbl_usertype_id
";
$params = [];
if ($q !== '') {
    $sql .= " WHERE u.USERNAME LIKE ? OR u.FULLNAME LIKE ? OR ut.Usertype LIKE ?";
    $params = ["%$q%", "%$q%", "%$q%"];
}
$sql .= " ORDER BY u.USERNAME ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$total = $pdo->query("SELECT COUNT(*) c FROM tbl_users")->fetch()['c'];

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Users</h1><p>Manage system user accounts and their access level.</p></div>
  <span class="records-pill"><?= (int)$total ?> Records</span>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
<?php endif; ?>

<div class="split-grid">

  <div class="panel-card">
    <div class="panel-card-head"><span class="bar"></span><h2>Add New User</h2></div>
    <div class="panel-card-body">
      <form method="POST" action="users.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="field">
          <label for="FULLNAME">Full Name <span class="req">*</span></label>
          <input type="text" id="FULLNAME" name="FULLNAME" required
                 value="<?= htmlspecialchars($_POST['FULLNAME'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="USERNAME">Username <span class="req">*</span></label>
          <input type="text" id="USERNAME" name="USERNAME" required
                 value="<?= htmlspecialchars($_POST['USERNAME'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="tbl_usertype_id">User Type <span class="req">*</span></label>
          <select id="tbl_usertype_id" name="tbl_usertype_id" required>
            <option value="">-- Select User Type --</option>
            <?php foreach ($usertypes as $ut): ?>
              <option value="<?= $ut['ID'] ?>" <?= (($_POST['tbl_usertype_id'] ?? '') == $ut['ID']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($ut['Usertype']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="imageUpload">Photo</label>
          <input type="file" id="imageUpload" name="imageUpload" accept="image/jpeg,image/png,image/gif">
        </div>
        <div class="field">
          <label for="PASSWORD">Password <span class="req">*</span></label>
          <input type="password" id="PASSWORD" name="PASSWORD" required minlength="6">
        </div>
        <div class="field">
          <label for="CONFIRM_PASSWORD">Confirm Password <span class="req">*</span></label>
          <input type="password" id="CONFIRM_PASSWORD" name="CONFIRM_PASSWORD" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary btn-block">+ Add User</button>
      </form>
    </div>
  </div>

  <div class="panel-card">
    <div class="panel-card-head"><span class="bar"></span><h2>All Users</h2></div>
    <div class="panel-card-body">
      <form method="GET" action="users.php" class="search-row">
        <input type="text" name="q" placeholder="Search username, name, or user type..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn btn-primary">🔍 Search</button>
        <a href="users.php" class="btn btn-secondary">↺ Clear</a>
      </form>
      <table class="table">
        <thead>
          <tr><th class="col-num">#</th><th>Photo</th><th>Username</th><th>Full Name</th><th>User Type</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="empty-state">No users found.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>
              <td><?= userPhotoHtml($r['USERNAME']) ?></td>
              <td><?= htmlspecialchars($r['USERNAME']) ?></td>
              <td><?= htmlspecialchars($r['FULLNAME'] ?? '—') ?></td>
              <td><span class="badge badge-accent"><?= htmlspecialchars($r['Usertype'] ?? 'Unassigned') ?></span></td>
              <td class="actions">
                <a href="user_edit.php?id=<?= (int)$r['ID'] ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                <?php if ((int)$r['ID'] !== (int)$_SESSION['user_id']): ?>
                  <form method="POST" action="user_delete.php" style="display:inline"
                        onsubmit="return confirm('Delete this user account?');">
                    <input type="hidden" name="id" value="<?= (int)$r['ID'] ?>">
                    <button type="submit" class="btn btn-small btn-danger">🗑️ Delete</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>
