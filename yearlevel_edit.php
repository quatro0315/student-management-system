<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Edit Year Level';
$errors = [];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tbl_yearlevel WHERE ID = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Year level not found.'];
    header("Location: yearlevels.php");
    exit;
}

$form = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['YEARLEVEL'] = trim($_POST['YEARLEVEL'] ?? '');
    $form['DESCRIPTION'] = trim($_POST['DESCRIPTION'] ?? '');

    if ($form['YEARLEVEL'] === '') $errors[] = "Year level is required.";
    if ($form['DESCRIPTION'] === '') $errors[] = "Description is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_yearlevel WHERE YEARLEVEL = ? AND ID != ?");
        $check->execute([$form['YEARLEVEL'], $id]);
        if ($check->fetch()) $errors[] = "That year level is already used by another record.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE tbl_yearlevel SET YEARLEVEL = ?, DESCRIPTION = ? WHERE ID = ?");
        $stmt->execute([$form['YEARLEVEL'], $form['DESCRIPTION'], $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Year level updated successfully.'];
        header("Location: yearlevels.php");
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Edit Year Level</h1><p>Update this year level's details.</p></div>
</div>

<div class="panel-card" style="max-width:500px;">
  <div class="panel-card-head"><span class="bar"></span><h2>Year Level Details</h2></div>
  <div class="panel-card-body">
    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="yearlevel_edit.php?id=<?= $id ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="field">
        <label for="YEARLEVEL">Year Level <span class="req">*</span></label>
        <input type="text" id="YEARLEVEL" name="YEARLEVEL" required value="<?= htmlspecialchars($form['YEARLEVEL']) ?>">
      </div>
      <div class="field">
        <label for="DESCRIPTION">Description <span class="req">*</span></label>
        <input type="text" id="DESCRIPTION" name="DESCRIPTION" required value="<?= htmlspecialchars($form['DESCRIPTION']) ?>">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Year Level</button>
        <a href="yearlevels.php" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
