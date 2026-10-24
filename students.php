<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Students';

// ----- Flash message from add/edit/delete redirects -----
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ----- Search -----
$q = trim($_GET['q'] ?? '');

$sql = "
    SELECT s.*, c.COURSE, yl.YEARLEVEL, sec.SECTION, sy.SCHOOLYEAR
    FROM tbl_student s
    LEFT JOIN tbl_course c     ON c.ID = s.COURSE_ID
    LEFT JOIN tbl_yearlevel yl ON yl.ID = s.YEARLEVEL_ID
    LEFT JOIN tbl_section sec  ON sec.ID = s.SECTION_ID
    LEFT JOIN tbl_schoolyear sy ON sy.ID = s.SCHOOLYEAR_ID
";
$params = [];

if ($q !== '') {
    $sql .= " WHERE s.STUDENT_NO LIKE ? OR s.LASTNAME LIKE ? OR s.FIRSTNAME LIKE ?
              OR c.COURSE LIKE ? OR s.EMAIL LIKE ?";
    $like = "%$q%";
    $params = [$like, $like, $like, $like, $like];
}

$sql .= " ORDER BY s.LASTNAME ASC, s.FIRSTNAME ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<div class="toolbar">
  <form method="GET" action="students.php" class="search-form">
    <input type="text" name="q" placeholder="Search by name, student no., course, email..."
           value="<?= htmlspecialchars($q) ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
    <?php if ($q !== ''): ?>
      <a href="students.php" class="btn btn-link">Clear</a>
    <?php endif; ?>
  </form>
  <a href="student_add.php" class="btn btn-primary">+ Add Student</a>
</div>

<div class="panel">
  <table class="table">
    <thead>
      <tr>
        <th>Student No.</th>
        <th>Name</th>
        <th>Course</th>
        <th>Year</th>
        <th>Section</th>
        <th>School Year</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($students)): ?>
        <tr><td colspan="8" class="empty-state">No matching students found.</td></tr>
      <?php endif; ?>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['STUDENT_NO']) ?></td>
          <td><?= htmlspecialchars($s['LASTNAME'] . ', ' . $s['FIRSTNAME'] . ' ' . $s['MIDDLENAME']) ?></td>
          <td><?= htmlspecialchars($s['COURSE'] ?? '—') ?></td>
          <td><?= htmlspecialchars($s['YEARLEVEL'] ?? '—') ?></td>
          <td><?= htmlspecialchars($s['SECTION'] ?? '—') ?></td>
          <td><?= htmlspecialchars($s['SCHOOLYEAR'] ?? '—') ?></td>
          <td><span class="badge badge-<?= $s['STATUS'] === 'Active' ? 'success' : 'muted' ?>"><?= htmlspecialchars($s['STATUS']) ?></span></td>
          <td class="actions">
            <a href="student_edit.php?id=<?= (int)$s['ID'] ?>" class="btn btn-small btn-secondary">Edit</a>
            <form method="POST" action="student_delete.php" style="display:inline"
                  onsubmit="return confirm('Delete this student record? This cannot be undone.');">
              <input type="hidden" name="id" value="<?= (int)$s['ID'] ?>">
              <button type="submit" class="btn btn-small btn-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once 'includes/footer.php'; ?>
