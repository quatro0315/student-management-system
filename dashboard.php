<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Dashboard';

// Only Admin sees student/user statistics. Everyone else gets a simple page with their allowed links.
if (!isAdmin()) {
    require_once 'includes/header.php';
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    ?>
    <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <div class="welcome-card">
      <div style="margin-bottom:10px;"><?= userPhotoHtml($_SESSION['username'] ?? '', 'thumb thumb-lg') ?></div>
      <h2>Welcome, <?= htmlspecialchars($displayName) ?></h2>
      <p>Signed in as <strong><?= htmlspecialchars($_SESSION['usertype_name'] ?? 'User') ?></strong>. You can open:</p>
      <?php foreach ($navGroups as $nav): if ($nav[0] === 'dashboard.php') continue; ?>
        <a href="<?= $nav[0] ?>" class="btn btn-primary"><?= $nav[1] ?> <?= htmlspecialchars($nav[2]) ?></a>
      <?php endforeach; ?>
    </div>
    <?php
    require_once 'includes/footer.php';
    exit;
}

$totalStudents = $pdo->query("SELECT COUNT(*) c FROM tbl_student")->fetch()['c'];
$totalCourses  = $pdo->query("SELECT COUNT(*) c FROM tbl_course")->fetch()['c'];
$totalSections = $pdo->query("SELECT COUNT(*) c FROM tbl_section")->fetch()['c'];
$activeStudents = $pdo->query("SELECT COUNT(*) c FROM tbl_student WHERE STATUS = 'Active'")->fetch()['c'];
$totalUsers = $pdo->query("SELECT COUNT(*) c FROM tbl_users")->fetch()['c'];


$perCourse = $pdo->query("
    SELECT c.COURSE, COUNT(s.ID) AS total
    FROM tbl_course c
    LEFT JOIN tbl_student s ON s.COURSE_ID = c.ID
    GROUP BY c.ID, c.COURSE
    ORDER BY total DESC
")->fetchAll();


$recent = $pdo->query("
    SELECT s.ID, s.STUDENT_NO, s.LASTNAME, s.FIRSTNAME, c.COURSE, s.date_created
    FROM tbl_student s
    LEFT JOIN tbl_course c ON c.ID = s.COURSE_ID
    ORDER BY s.ID DESC
    LIMIT 5
")->fetchAll();

require_once 'includes/header.php';
?>

<div class="cards-grid">
  <div class="stat-card">
    <div class="stat-icon">🧑‍🎓</div>
    <div>
      <div class="stat-value"><?= (int)$totalStudents ?></div>
      <div class="stat-label">Total Students</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">✅</div>
    <div>
      <div class="stat-value"><?= (int)$activeStudents ?></div>
      <div class="stat-label">Active Students</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📚</div>
    <div>
      <div class="stat-value"><?= (int)$totalCourses ?></div>
      <div class="stat-label">Courses Offered</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🏷️</div>
    <div>
      <div class="stat-value"><?= (int)$totalSections ?></div>
      <div class="stat-label">Sections</div>
    </div>
  </div>
  <?php if (isAdmin()): ?>
  <div class="stat-card">
    <div class="stat-icon">👤</div>
    <div>
      <div class="stat-value"><?= (int)$totalUsers ?></div>
      <div class="stat-label">System Users</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<div class="panels-grid">
  <div class="panel">
    <h2>Students per Course</h2>
    <table class="table">
      <thead><tr><th>Course</th><th>Students</th></tr></thead>
      <tbody>
        <?php foreach ($perCourse as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['COURSE']) ?></td>
            <td><?= (int)$row['total'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="panel">
    <h2>Recently Added Students</h2>
    <table class="table">
      <thead><tr><th>Student No.</th><th>Name</th><th>Course</th><th>Date Added</th></tr></thead>
      <tbody>
        <?php if (empty($recent)): ?>
          <tr><td colspan="4" class="empty-state">No students yet. <a href="student_add.php">Add one</a>.</td></tr>
        <?php endif; ?>
        <?php foreach ($recent as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['STUDENT_NO']) ?></td>
            <td><?= htmlspecialchars($row['LASTNAME'] . ', ' . $row['FIRSTNAME']) ?></td>
            <td><?= htmlspecialchars($row['COURSE'] ?? '—') ?></td>
            <td><?= htmlspecialchars($row['date_created']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
