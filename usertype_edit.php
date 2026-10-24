<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';

$pageTitle = 'Edit User Type';
$errors = [];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tbl_usertype WHERE ID = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'User type not found.'];
    header("Location: usertypes.php");
    exit;
}

$form = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['Usertype'] = trim($_POST['Usertype'] ?? '');

    if ($form['Usertype'] === '') $errors[] = "User type name is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_usertype WHERE Usertype = ? AND ID != ?");
        $check->execute([$form['Usertype'], $id]);
        if ($check->fetch()) $errors[] = "That user type is already used by another record.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE tbl_usertype SET Usertype = ? WHERE ID = ?");
        $stmt->execute([$form['Usertype'], $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User type updated successfully.'];
        header("Location: usertypes.php");
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Edit User Type</h1><p>Update this user type's name.</p></div>
</div>

<div class="panel-card" style="max-width:500px;">
  <div class="panel-card-head"><span class="bar"></span><h2>User Type Details</h2></div>
  <div class="panel-card-body">
    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="usertype_edit.php?id=<?= $id ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="field">
        <label for="Usertype">User Type <span class="req">*</span></label>
        <input type="text" id="Usertype" name="Usertype" required value="<?= htmlspecialchars($form['Usertype']) ?>">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update User Type</button>
        <a href="usertypes.php" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
