<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/instructor_upload.php';

$pageTitle = 'Instructor';
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$salutations = ['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Engr.', 'Prof.', 'Atty.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $salutation = trim($_POST['SALUTATION'] ?? '');
    $firstname  = trim($_POST['FIRSTNAME'] ?? '');
    $middlename = trim($_POST['MIDDLENAME'] ?? '');
    $lastname   = trim($_POST['LASTNAME'] ?? '');

    if ($salutation === '') $errors[] = "Salutation is required.";
    if ($firstname === '')  $errors[] = "First name is required.";
    if ($lastname === '')   $errors[] = "Last name is required.";

    [$photo, $photoError] = handleInstructorPhoto('PHOTO');
    if ($photoError) $errors[] = $photoError;

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO tbl_instructors (SALUTATION, FIRSTNAME, MIDDLENAME, LASTNAME, PHOTO) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$salutation, $firstname, $middlename !== '' ? $middlename : null, $lastname, $photo]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Instructor added successfully.'];
        header("Location: instructors.php");
        exit;
    }
    deleteInstructorPhoto($photo ?? null); // don't keep an upload if the form was rejected
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM tbl_instructors";
$params = [];
if ($q !== '') {
    $sql .= " WHERE FIRSTNAME LIKE ? OR MIDDLENAME LIKE ? OR LASTNAME LIKE ?";
    $params = ["%$q%", "%$q%", "%$q%"];
}
$sql .= " ORDER BY LASTNAME ASC, FIRSTNAME ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$total = $pdo->query("SELECT COUNT(*) c FROM tbl_instructors")->fetch()['c'];

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Instructor</h1><p>Manage instructors.</p></div>
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
    <div class="panel-card-head"><span class="bar"></span><h2>Add New Instructor</h2></div>
    <div class="panel-card-body">
      <form method="POST" action="instructors.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="field">
          <label for="SALUTATION">Salutation <span class="req">*</span></label>
          <select id="SALUTATION" name="SALUTATION" required>
            <option value="">-- Select --</option>
            <?php foreach ($salutations as $s): ?>
              <option value="<?= htmlspecialchars($s) ?>" <?= ($_POST['SALUTATION'] ?? '') === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="FIRSTNAME">First Name <span class="req">*</span></label>
          <input type="text" id="FIRSTNAME" name="FIRSTNAME" required maxlength="100"
                 value="<?= htmlspecialchars($_POST['FIRSTNAME'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="MIDDLENAME">Middle Name</label>
          <input type="text" id="MIDDLENAME" name="MIDDLENAME" maxlength="100"
                 value="<?= htmlspecialchars($_POST['MIDDLENAME'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="LASTNAME">Last Name <span class="req">*</span></label>
          <input type="text" id="LASTNAME" name="LASTNAME" required maxlength="100"
                 value="<?= htmlspecialchars($_POST['LASTNAME'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="PHOTO">Photo</label>
          <input type="file" id="PHOTO" name="PHOTO" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>
        <button type="submit" class="btn btn-primary btn-block">+ Add Instructor</button>
      </form>
    </div>
  </div>

  <div class="panel-card">
    <div class="panel-card-head"><span class="bar"></span><h2>All Instructors</h2></div>
    <div class="panel-card-body">
      <form method="GET" action="instructors.php" class="search-row">
        <input type="text" name="q" placeholder="Search name..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn btn-primary">🔍 Search</button>
        <a href="instructors.php" class="btn btn-secondary">↺ Clear</a>
      </form>
      <div style="overflow-x:auto;">
      <table class="table">
        <thead>
          <tr>
            <th class="col-num">#</th><th>Photo</th><th>Salutation</th>
            <th>First Name</th><th>Middle Name</th><th>Last Name</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="empty-state">No instructors found.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>
              <td><?= instructorPhotoHtml($r['PHOTO']) ?></td>
              <td><?= htmlspecialchars($r['SALUTATION']) ?></td>
              <td><?= htmlspecialchars($r['FIRSTNAME']) ?></td>
              <td><?= htmlspecialchars($r['MIDDLENAME'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['LASTNAME']) ?></td>
              <td class="actions">
                <a href="instructor_edit.php?id=<?= (int)$r['ID'] ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                <form method="POST" action="instructor_delete.php" style="display:inline"
                      onsubmit="return confirm('Delete this instructor?');">
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

</div>

<?php require_once 'includes/footer.php'; ?>
