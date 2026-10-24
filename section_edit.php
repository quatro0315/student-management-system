<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Edit Section';
$errors = [];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tbl_section WHERE ID = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Section not found.'];
    header("Location: sections.php");
    exit;
}

$form = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['SECTION'] = trim($_POST['SECTION'] ?? '');
    $form['DESCRIPTION'] = trim($_POST['DESCRIPTION'] ?? '');

    if ($form['SECTION'] === '') $errors[] = "Section name is required.";
    if ($form['DESCRIPTION'] === '') $errors[] = "Description is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_section WHERE SECTION = ? AND ID != ?");
        $check->execute([$form['SECTION'], $id]);
        if ($check->fetch()) $errors[] = "That section is already used by another record.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE tbl_section SET SECTION = ?, DESCRIPTION = ? WHERE ID = ?");
        $stmt->execute([$form['SECTION'], $form['DESCRIPTION'], $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Section updated successfully.'];
        header("Location: sections.php");
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Edit Section</h1><p>Update this section's details.</p></div>
</div>

<div class="panel-card" style="max-width:500px;">
  <div class="panel-card-head"><span class="bar"></span><h2>Section Details</h2></div>
  <div class="panel-card-body">
    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="section_edit.php?id=<?= $id ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="field">
        <label for="SECTION">Section <span class="req">*</span></label>
        <input type="text" id="SECTION" name="SECTION" required value="<?= htmlspecialchars($form['SECTION']) ?>">
      </div>
      <div class="field">
        <label for="DESCRIPTION">Description <span class="req">*</span></label>
        <input type="text" id="DESCRIPTION" name="DESCRIPTION" required value="<?= htmlspecialchars($form['DESCRIPTION']) ?>">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Section</button>
        <a href="sections.php" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
