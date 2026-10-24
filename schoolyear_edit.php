<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Edit School Year';
$errors = [];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tbl_schoolyear WHERE ID = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'School year not found.'];
    header("Location: schoolyears.php");
    exit;
}

$form = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['SCHOOLYEAR'] = trim($_POST['SCHOOLYEAR'] ?? '');
    $form['DESCRIPTION'] = trim($_POST['DESCRIPTION'] ?? '');

    if ($form['SCHOOLYEAR'] === '') $errors[] = "School year is required.";
    if ($form['DESCRIPTION'] === '') $errors[] = "Description is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_schoolyear WHERE SCHOOLYEAR = ? AND ID != ?");
        $check->execute([$form['SCHOOLYEAR'], $id]);
        if ($check->fetch()) $errors[] = "That school year is already used by another record.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE tbl_schoolyear SET SCHOOLYEAR = ?, DESCRIPTION = ? WHERE ID = ?");
        $stmt->execute([$form['SCHOOLYEAR'], $form['DESCRIPTION'], $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'School year updated successfully.'];
        header("Location: schoolyears.php");
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Edit School Year</h1><p>Update this school year's details.</p></div>
</div>

<div class="panel-card" style="max-width:500px;">
  <div class="panel-card-head"><span class="bar"></span><h2>School Year Details</h2></div>
  <div class="panel-card-body">
    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="schoolyear_edit.php?id=<?= $id ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="field">
        <label for="SCHOOLYEAR">School Year <span class="req">*</span></label>
        <input type="text" id="SCHOOLYEAR" name="SCHOOLYEAR" required value="<?= htmlspecialchars($form['SCHOOLYEAR']) ?>">
      </div>
      <div class="field">
        <label for="DESCRIPTION">Description <span class="req">*</span></label>
        <input type="text" id="DESCRIPTION" name="DESCRIPTION" required value="<?= htmlspecialchars($form['DESCRIPTION']) ?>">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update School Year</button>
        <a href="schoolyears.php" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
