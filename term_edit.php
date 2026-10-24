<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Edit Term';
$errors = [];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tbl_term WHERE ID = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Term not found.'];
    header("Location: terms.php");
    exit;
}

$form = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['TERM'] = trim($_POST['TERM'] ?? '');
    $form['DESCRIPTION'] = trim($_POST['DESCRIPTION'] ?? '');

    if ($form['TERM'] === '') $errors[] = "Term is required.";
    if ($form['DESCRIPTION'] === '') $errors[] = "Description is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_term WHERE TERM = ? AND ID != ?");
        $check->execute([$form['TERM'], $id]);
        if ($check->fetch()) $errors[] = "That term is already used by another record.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE tbl_term SET TERM = ?, DESCRIPTION = ? WHERE ID = ?");
        $stmt->execute([$form['TERM'], $form['DESCRIPTION'], $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Term updated successfully.'];
        header("Location: terms.php");
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Edit Term</h1><p>Update this term's details.</p></div>
</div>

<div class="panel-card" style="max-width:500px;">
  <div class="panel-card-head"><span class="bar"></span><h2>Term Details</h2></div>
  <div class="panel-card-body">
    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="term_edit.php?id=<?= $id ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="field">
        <label for="TERM">Term <span class="req">*</span></label>
        <input type="text" id="TERM" name="TERM" required value="<?= htmlspecialchars($form['TERM']) ?>">
      </div>
      <div class="field">
        <label for="DESCRIPTION">Description <span class="req">*</span></label>
        <input type="text" id="DESCRIPTION" name="DESCRIPTION" required value="<?= htmlspecialchars($form['DESCRIPTION']) ?>">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Term</button>
        <a href="terms.php" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
