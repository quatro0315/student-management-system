<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Courses';
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ----- Add new course -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $course = trim($_POST['COURSE'] ?? '');
    $description = trim($_POST['DESCRIPTION'] ?? '');

    if ($course === '') $errors[] = "Course code is required.";
    if ($description === '') $errors[] = "Description is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_course WHERE COURSE = ?");
        $check->execute([$course]);
        if ($check->fetch()) {
            $errors[] = "That course code already exists.";
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO tbl_course (COURSE, DESCRIPTION) VALUES (?, ?)");
        $stmt->execute([$course, $description]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Course added successfully.'];
        header("Location: courses.php");
        exit;
    }
}

// ----- Search -----
$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM tbl_course";
$params = [];
if ($q !== '') {
    $sql .= " WHERE COURSE LIKE ? OR DESCRIPTION LIKE ?";
    $params = ["%$q%", "%$q%"];
}
$sql .= " ORDER BY COURSE ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$total = $pdo->query("SELECT COUNT(*) c FROM tbl_course")->fetch()['c'];

require_once 'includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Courses</h1>
    <p>Manage the list of academic courses offered.</p>
  </div>
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
    <div class="panel-card-head"><span class="bar"></span><h2>Add New Course</h2></div>
    <div class="panel-card-body">
      <form method="POST" action="courses.php">
        <input type="hidden" name="action" value="add">
        <div class="field">
          <label for="COURSE">Course Code <span class="req">*</span></label>
          <input type="text" id="COURSE" name="COURSE" placeholder="e.g. BSIS" required
                 value="<?= htmlspecialchars($_POST['COURSE'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="DESCRIPTION">Description <span class="req">*</span></label>
          <input type="text" id="DESCRIPTION" name="DESCRIPTION" placeholder="Full course title" required
                 value="<?= htmlspecialchars($_POST['DESCRIPTION'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-block">+ Add Course</button>
      </form>
    </div>
  </div>

  <div class="panel-card">
    <div class="panel-card-head"><span class="bar"></span><h2>All Courses</h2></div>
    <div class="panel-card-body">
      <form method="GET" action="courses.php" class="search-row">
        <input type="text" name="q" placeholder="Search course..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn btn-primary">🔍 Search</button>
        <a href="courses.php" class="btn btn-secondary">↺ Clear</a>
      </form>
      <table class="table">
        <thead>
          <tr><th class="col-num">#</th><th>Course</th><th>Description</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="empty-state">No courses found.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>

              <td><?= htmlspecialchars($r['COURSE']) ?></td>
              <td><?= htmlspecialchars($r['DESCRIPTION']) ?></td>
              <td class="actions">
                <a href="course_edit.php?id=<?= (int)$r['ID'] ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                <form method="POST" action="course_delete.php" style="display:inline"
                      onsubmit="return confirm('Delete this course? Students assigned to it will be unlinked.');">
                  <input type="hidden" name="id" value="<?= (int)$r['ID'] ?>">
                  <button type="submit" class="btn btn-small btn-danger">🗑️ Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>
