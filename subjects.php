<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Subjects';
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    blockIfReadOnly('subjects.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $course      = trim($_POST['COURSE'] ?? '');
    $yearlevel   = trim($_POST['YEARLEVEL'] ?? '');
    $term        = trim($_POST['TERM'] ?? '');
    $code        = trim($_POST['CODE'] ?? '');
    $title       = trim($_POST['SUBJECTTITLE'] ?? '');
    $lecture     = $_POST['LECTURE'] ?? '';
    $laboratory  = $_POST['LABORATORY'] ?? '';
    $credit      = $_POST['CREDIT'] ?? '';
    $grade       = $_POST['GRADE'] ?? '';
    $prereq      = trim($_POST['PREREQUISITE'] ?? '') ?: 'NONE';

    if ($course === '')    $errors[] = "Course is required.";
    if ($yearlevel === '') $errors[] = "Year level is required.";
    if ($term === '')      $errors[] = "Term is required.";
    if ($code === '')      $errors[] = "Subject code is required.";
    if ($title === '')     $errors[] = "Subject title is required.";
    if ($lecture === '' || !is_numeric($lecture))    $errors[] = "Lecture units must be a number.";
    if ($laboratory === '' || !is_numeric($laboratory)) $errors[] = "Laboratory units must be a number.";
    if ($credit === '' || !is_numeric($credit))      $errors[] = "Credit units must be a number.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_subject WHERE CODE = ?");
        $check->execute([$code]);
        if ($check->fetch()) $errors[] = "That subject code already exists.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO tbl_subject
              (COURSE, YEARLEVEL, TERM, CODE, SUBJECTTITLE, LECTURE, LABORATORY, CREDIT, GRADE, `PRE-REQUISITE`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $course, $yearlevel, $term, $code, $title,
            $lecture, $laboratory, $credit, ($grade !== '' ? $grade : 0), $prereq,
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Subject added successfully.'];
        header("Location: subjects.php");
        exit;
    }
}

$readOnly = isReadOnlyRole();
$searchEnabled = canSearch();
$q = $searchEnabled ? trim($_GET['q'] ?? '') : '';
$sql = "SELECT * FROM tbl_subject";
$params = [];
if ($q !== '') {
    $sql .= " WHERE CODE LIKE ? OR SUBJECTTITLE LIKE ? OR COURSE LIKE ?";
    $params = ["%$q%", "%$q%", "%$q%"];
}
$sql .= " ORDER BY COURSE ASC, YEARLEVEL ASC, TERM ASC, CODE ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$total = $pdo->query("SELECT COUNT(*) c FROM tbl_subject")->fetch()['c'];

// Dropdown sources: subject rows store the lookup tables' DESCRIPTION text
$courseOptions     = $pdo->query("SELECT DESCRIPTION FROM tbl_course ORDER BY DESCRIPTION")->fetchAll(PDO::FETCH_COLUMN);
$yearlevelOptions  = $pdo->query("SELECT DESCRIPTION FROM tbl_yearlevel ORDER BY ID")->fetchAll(PDO::FETCH_COLUMN);
$termOptions       = $pdo->query("SELECT DESCRIPTION FROM tbl_term ORDER BY ID")->fetchAll(PDO::FETCH_COLUMN);

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Subjects</h1><p>Manage the subjects offered per course, year level, and term.</p></div>
  <span class="records-pill"><?= (int)$total ?> Records</span>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="alert alert-error"><ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
<?php endif; ?>

<div class="split-grid split-grid-wide"<?= $readOnly ? ' style="grid-template-columns:1fr;"' : '' ?>>

  <?php if (!$readOnly): ?>
  <div class="panel-card">
    <div class="panel-card-head"><span class="bar"></span><h2>Add New Subject</h2></div>
    <div class="panel-card-body">
      <form method="POST" action="subjects.php">
        <input type="hidden" name="action" value="add">

        <div class="field">
          <label for="COURSE">Course <span class="req">*</span></label>
          <select id="COURSE" name="COURSE" required>
            <option value="">-- Select Course --</option>
            <?php foreach ($courseOptions as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= ($_POST['COURSE'] ?? '') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field-row-2">
          <div class="field">
            <label for="YEARLEVEL">Year Level <span class="req">*</span></label>
            <select id="YEARLEVEL" name="YEARLEVEL" required>
              <option value="">-- Select --</option>
              <?php foreach ($yearlevelOptions as $y): ?>
                <option value="<?= htmlspecialchars($y) ?>" <?= ($_POST['YEARLEVEL'] ?? '') === $y ? 'selected' : '' ?>><?= htmlspecialchars($y) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="TERM">Term <span class="req">*</span></label>
            <select id="TERM" name="TERM" required>
              <option value="">-- Select --</option>
              <?php foreach ($termOptions as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= ($_POST['TERM'] ?? '') === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field-row-2">
          <div class="field">
            <label for="CODE">Subject Code <span class="req">*</span></label>
            <input type="text" id="CODE" name="CODE" placeholder="e.g. CC101" required
                   value="<?= htmlspecialchars($_POST['CODE'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="GRADE">Grade</label>
            <input type="number" step="0.01" id="GRADE" name="GRADE" placeholder="0"
                   value="<?= htmlspecialchars($_POST['GRADE'] ?? '0') ?>">
          </div>
        </div>

        <div class="field">
          <label for="SUBJECTTITLE">Subject Title <span class="req">*</span></label>
          <input type="text" id="SUBJECTTITLE" name="SUBJECTTITLE" placeholder="Full subject title" required
                 value="<?= htmlspecialchars($_POST['SUBJECTTITLE'] ?? '') ?>">
        </div>

        <div class="field-row-3">
          <div class="field">
            <label for="LECTURE">Lecture <span class="req">*</span></label>
            <input type="number" step="0.5" id="LECTURE" name="LECTURE" required
                   value="<?= htmlspecialchars($_POST['LECTURE'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="LABORATORY">Laboratory <span class="req">*</span></label>
            <input type="number" step="0.5" id="LABORATORY" name="LABORATORY" required
                   value="<?= htmlspecialchars($_POST['LABORATORY'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="CREDIT">Credit <span class="req">*</span></label>
            <input type="number" step="0.5" id="CREDIT" name="CREDIT" required
                   value="<?= htmlspecialchars($_POST['CREDIT'] ?? '') ?>">
          </div>
        </div>

        <div class="field">
          <label for="PREREQUISITE">Pre-requisite</label>
          <input type="text" id="PREREQUISITE" name="PREREQUISITE" placeholder="e.g. NONE"
                 value="<?= htmlspecialchars($_POST['PREREQUISITE'] ?? 'NONE') ?>">
        </div>

        <button type="submit" class="btn btn-primary btn-block">+ Add Subject</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="panel-card">
    <div class="panel-card-head"><span class="bar"></span><h2>All Subjects</h2></div>
    <div class="panel-card-body">
      <form method="GET" action="subjects.php" class="search-row">
        <input type="text" name="q" placeholder="<?= $searchEnabled ? 'Search code, title, or course...' : 'Search is disabled for your account' ?>" value="<?= htmlspecialchars($q) ?>" <?= $searchEnabled ? '' : 'disabled' ?>>
        <button type="submit" class="btn btn-primary" <?= $searchEnabled ? '' : 'disabled' ?>>🔍 Search</button>
        <a href="subjects.php" class="btn btn-secondary">↺ Clear</a>
      </form>
      <div style="overflow-x:auto;">
      <table class="table">
        <thead>
          <tr>
            <th class="col-num">#</th><th>Code</th><th>Title</th>
            <th>Year</th><th>Term</th><th>Units</th><?php if (!$readOnly): ?><th>Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="<?= $readOnly ? 7 : 8 ?>" class="empty-state">No subjects found.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($r['CODE']) ?></td>
              <td><?= htmlspecialchars($r['SUBJECTTITLE']) ?></td>
              <td><?= htmlspecialchars($r['YEARLEVEL']) ?></td>
              <td><?= htmlspecialchars($r['TERM']) ?></td>
              <td><?= htmlspecialchars($r['CREDIT']) ?></td>
              <?php if (!$readOnly): ?>
              <td class="actions">
                <a href="subject_edit.php?id=<?= (int)$r['ID'] ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                <form method="POST" action="subject_delete.php" style="display:inline"
                      onsubmit="return confirm('Delete this subject?');">
                  <input type="hidden" name="id" value="<?= (int)$r['ID'] ?>">
                  <button type="submit" class="btn btn-small btn-danger">🗑️ Delete</button>
                </form>
              </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>
