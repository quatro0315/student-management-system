<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Edit Course';
$errors = [];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tbl_course WHERE ID = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Course not found.'];
    header("Location: courses.php");
    exit;
}

$form = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['COURSE'] = trim($_POST['COURSE'] ?? '');
    $form['DESCRIPTION'] = trim($_POST['DESCRIPTION'] ?? '');

    if ($form['COURSE'] === '') $errors[] = "Course code is required.";
    if ($form['DESCRIPTION'] === '') $errors[] = "Description is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_course WHERE COURSE = ? AND ID != ?");
        $check->execute([$form['COURSE'], $id]);
        if ($check->fetch()) $errors[] = "That course code is already used by another record.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE tbl_course SET COURSE = ?, DESCRIPTION = ? WHERE ID = ?");
        $stmt->execute([$form['COURSE'], $form['DESCRIPTION'], $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Course updated successfully.'];
        header("Location: courses.php");
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Edit Course</h1><p>Update this course's details.</p></div>
</div>

<div class="panel-card" style="max-width:500px;">
  <div class="panel-card-head"><span class="bar"></span><h2>Course Details</h2></div>
  <div class="panel-card-body">
    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="course_edit.php?id=<?= $id ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="field">
        <label for="COURSE">Course Code <span class="req">*</span></label>
        <input type="text" id="COURSE" name="COURSE" required value="<?= htmlspecialchars($form['COURSE']) ?>">
      </div>
      <div class="field">
        <label for="DESCRIPTION">Description <span class="req">*</span></label>
        <input type="text" id="DESCRIPTION" name="DESCRIPTION" required value="<?= htmlspecialchars($form['DESCRIPTION']) ?>">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Course</button>
        <a href="courses.php" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
