<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
blockIfReadOnly('subjects.php');

$pageTitle = 'Edit Subject';
$errors = [];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tbl_subject WHERE ID = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Subject not found.'];
    header("Location: subjects.php");
    exit;
}

// Normalize the prerequisite key for form use (DB column has a hyphen)
$row['PREREQUISITE'] = $row['PRE-REQUISITE'];
$form = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['COURSE','YEARLEVEL','TERM','CODE','SUBJECTTITLE','LECTURE','LABORATORY','CREDIT','GRADE','PREREQUISITE'] as $key) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    if ($form['COURSE'] === '')       $errors[] = "Course is required.";
    if ($form['YEARLEVEL'] === '')    $errors[] = "Year level is required.";
    if ($form['TERM'] === '')         $errors[] = "Term is required.";
    if ($form['CODE'] === '')         $errors[] = "Subject code is required.";
    if ($form['SUBJECTTITLE'] === '') $errors[] = "Subject title is required.";
    if ($form['LECTURE'] === '' || !is_numeric($form['LECTURE']))       $errors[] = "Lecture units must be a number.";
    if ($form['LABORATORY'] === '' || !is_numeric($form['LABORATORY'])) $errors[] = "Laboratory units must be a number.";
    if ($form['CREDIT'] === '' || !is_numeric($form['CREDIT']))         $errors[] = "Credit units must be a number.";
    if ($form['PREREQUISITE'] === '') $form['PREREQUISITE'] = 'NONE';

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_subject WHERE CODE = ? AND ID != ?");
        $check->execute([$form['CODE'], $id]);
        if ($check->fetch()) $errors[] = "That subject code is already used by another record.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE tbl_subject SET
              COURSE = ?, YEARLEVEL = ?, TERM = ?, CODE = ?, SUBJECTTITLE = ?,
              LECTURE = ?, LABORATORY = ?, CREDIT = ?, GRADE = ?, `PRE-REQUISITE` = ?
            WHERE ID = ?
        ");
        $stmt->execute([
            $form['COURSE'], $form['YEARLEVEL'], $form['TERM'], $form['CODE'], $form['SUBJECTTITLE'],
            $form['LECTURE'], $form['LABORATORY'], $form['CREDIT'], ($form['GRADE'] !== '' ? $form['GRADE'] : 0),
            $form['PREREQUISITE'], $id,
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Subject updated successfully.'];
        header("Location: subjects.php");
        exit;
    }
}

$courseOptions    = $pdo->query("SELECT DESCRIPTION FROM tbl_course ORDER BY DESCRIPTION")->fetchAll(PDO::FETCH_COLUMN);
$yearlevelOptions = $pdo->query("SELECT DESCRIPTION FROM tbl_yearlevel ORDER BY ID")->fetchAll(PDO::FETCH_COLUMN);
$termOptions      = $pdo->query("SELECT DESCRIPTION FROM tbl_term ORDER BY ID")->fetchAll(PDO::FETCH_COLUMN);

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Edit Subject</h1><p>Update this subject's details.</p></div>
</div>

<div class="panel-card" style="max-width:640px;">
  <div class="panel-card-head"><span class="bar"></span><h2>Subject Details</h2></div>
  <div class="panel-card-body">
    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="subject_edit.php?id=<?= $id ?>">
      <input type="hidden" name="id" value="<?= $id ?>">

      <div class="field">
        <label for="COURSE">Course <span class="req">*</span></label>
        <select id="COURSE" name="COURSE" required>
          <option value="">-- Select Course --</option>
          <?php foreach ($courseOptions as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $form['COURSE'] === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field-row-2">
        <div class="field">
          <label for="YEARLEVEL">Year Level <span class="req">*</span></label>
          <select id="YEARLEVEL" name="YEARLEVEL" required>
            <option value="">-- Select --</option>
            <?php foreach ($yearlevelOptions as $y): ?>
              <option value="<?= htmlspecialchars($y) ?>" <?= $form['YEARLEVEL'] === $y ? 'selected' : '' ?>><?= htmlspecialchars($y) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="TERM">Term <span class="req">*</span></label>
          <select id="TERM" name="TERM" required>
            <option value="">-- Select --</option>
            <?php foreach ($termOptions as $t): ?>
              <option value="<?= htmlspecialchars($t) ?>" <?= $form['TERM'] === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field-row-2">
        <div class="field">
          <label for="CODE">Subject Code <span class="req">*</span></label>
          <input type="text" id="CODE" name="CODE" required value="<?= htmlspecialchars($form['CODE']) ?>">
        </div>
        <div class="field">
          <label for="GRADE">Grade</label>
          <input type="number" step="0.01" id="GRADE" name="GRADE" value="<?= htmlspecialchars($form['GRADE']) ?>">
        </div>
      </div>

      <div class="field">
        <label for="SUBJECTTITLE">Subject Title <span class="req">*</span></label>
        <input type="text" id="SUBJECTTITLE" name="SUBJECTTITLE" required value="<?= htmlspecialchars($form['SUBJECTTITLE']) ?>">
      </div>

      <div class="field-row-3">
        <div class="field">
          <label for="LECTURE">Lecture <span class="req">*</span></label>
          <input type="number" step="0.5" id="LECTURE" name="LECTURE" required value="<?= htmlspecialchars($form['LECTURE']) ?>">
        </div>
        <div class="field">
          <label for="LABORATORY">Laboratory <span class="req">*</span></label>
          <input type="number" step="0.5" id="LABORATORY" name="LABORATORY" required value="<?= htmlspecialchars($form['LABORATORY']) ?>">
        </div>
        <div class="field">
          <label for="CREDIT">Credit <span class="req">*</span></label>
          <input type="number" step="0.5" id="CREDIT" name="CREDIT" required value="<?= htmlspecialchars($form['CREDIT']) ?>">
        </div>
      </div>

      <div class="field">
        <label for="PREREQUISITE">Pre-requisite</label>
        <input type="text" id="PREREQUISITE" name="PREREQUISITE" value="<?= htmlspecialchars($form['PREREQUISITE']) ?>">
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Subject</button>
        <a href="subjects.php" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
