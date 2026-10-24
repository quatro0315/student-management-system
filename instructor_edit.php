<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/instructor_upload.php';

$pageTitle = 'Edit Instructor';
$errors = [];
$salutations = ['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Engr.', 'Prof.', 'Atty.'];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tbl_instructors WHERE ID = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Instructor not found.'];
    header("Location: instructors.php");
    exit;
}

$form = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['SALUTATION'] = trim($_POST['SALUTATION'] ?? '');
    $form['FIRSTNAME']  = trim($_POST['FIRSTNAME'] ?? '');
    $form['MIDDLENAME'] = trim($_POST['MIDDLENAME'] ?? '');
    $form['LASTNAME']   = trim($_POST['LASTNAME'] ?? '');
    $removePhoto = !empty($_POST['REMOVE_PHOTO']);

    if ($form['SALUTATION'] === '') $errors[] = "Salutation is required.";
    if ($form['FIRSTNAME'] === '')  $errors[] = "First name is required.";
    if ($form['LASTNAME'] === '')   $errors[] = "Last name is required.";

    [$newPhoto, $photoError] = handleInstructorPhoto('PHOTO');
    if ($photoError) $errors[] = $photoError;

    if (empty($errors)) {
        $photo = $row['PHOTO'];
        if ($newPhoto) {
            deleteInstructorPhoto($photo);
            $photo = $newPhoto;
        } elseif ($removePhoto) {
            deleteInstructorPhoto($photo);
            $photo = null;
        }

        $stmt = $pdo->prepare("UPDATE tbl_instructors SET SALUTATION = ?, FIRSTNAME = ?, MIDDLENAME = ?, LASTNAME = ?, PHOTO = ? WHERE ID = ?");
        $stmt->execute([
            $form['SALUTATION'], $form['FIRSTNAME'],
            $form['MIDDLENAME'] !== '' ? $form['MIDDLENAME'] : null,
            $form['LASTNAME'], $photo, $id,
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Instructor updated successfully.'];
        header("Location: instructors.php");
        exit;
    }
    deleteInstructorPhoto($newPhoto ?? null);
}

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Edit Instructor</h1><p>Update this instructor's details.</p></div>
</div>

<div class="panel-card" style="max-width:500px;">
  <div class="panel-card-head"><span class="bar"></span><h2>Instructor Details</h2></div>
  <div class="panel-card-body">
    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="instructor_edit.php?id=<?= $id ?>" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="field">
        <label for="SALUTATION">Salutation <span class="req">*</span></label>
        <select id="SALUTATION" name="SALUTATION" required>
          <option value="">-- Select --</option>
          <?php
            $opts = $salutations;
            if ($form['SALUTATION'] !== '' && !in_array($form['SALUTATION'], $opts, true)) $opts[] = $form['SALUTATION'];
            foreach ($opts as $s): ?>
            <option value="<?= htmlspecialchars($s) ?>" <?= $form['SALUTATION'] === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="FIRSTNAME">First Name <span class="req">*</span></label>
        <input type="text" id="FIRSTNAME" name="FIRSTNAME" required maxlength="100" value="<?= htmlspecialchars($form['FIRSTNAME']) ?>">
      </div>
      <div class="field">
        <label for="MIDDLENAME">Middle Name</label>
        <input type="text" id="MIDDLENAME" name="MIDDLENAME" maxlength="100" value="<?= htmlspecialchars($form['MIDDLENAME'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="LASTNAME">Last Name <span class="req">*</span></label>
        <input type="text" id="LASTNAME" name="LASTNAME" required maxlength="100" value="<?= htmlspecialchars($form['LASTNAME']) ?>">
      </div>
      <div class="field">
        <label>Current Photo</label>
        <div><?= instructorPhotoHtml($row['PHOTO'], 'thumb thumb-lg') ?></div>
        <?php if ($row['PHOTO']): ?>
          <label style="font-weight:400;margin-top:6px;"><input type="checkbox" name="REMOVE_PHOTO" value="1"> Remove photo</label>
        <?php endif; ?>
      </div>
      <div class="field">
        <label for="PHOTO">Upload New Photo</label>
        <input type="file" id="PHOTO" name="PHOTO" accept="image/jpeg,image/png,image/gif,image/webp">
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Instructor</button>
        <a href="instructors.php" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
