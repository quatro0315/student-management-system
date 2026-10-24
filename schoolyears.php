<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'School Year';
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $schoolyear = trim($_POST['SCHOOLYEAR'] ?? '');
    $description = trim($_POST['DESCRIPTION'] ?? '');

    if ($schoolyear === '') $errors[] = "School year is required.";
    if ($description === '') $errors[] = "Description is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_schoolyear WHERE SCHOOLYEAR = ?");
        $check->execute([$schoolyear]);
        if ($check->fetch()) $errors[] = "That school year already exists.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO tbl_schoolyear (SCHOOLYEAR, DESCRIPTION) VALUES (?, ?)");
        $stmt->execute([$schoolyear, $description]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'School year added successfully.'];
        header("Location: schoolyears.php");
        exit;
    }
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM tbl_schoolyear";
$params = [];
if ($q !== '') {
    $sql .= " WHERE SCHOOLYEAR LIKE ? OR DESCRIPTION LIKE ?";
    $params = ["%$q%", "%$q%"];
}
$sql .= " ORDER BY SCHOOLYEAR ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$total = $pdo->query("SELECT COUNT(*) c FROM tbl_schoolyear")->fetch()['c'];

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>School Year</h1><p>Manage academic school years.</p></div>
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
    <div class="panel-card-head"><span class="bar"></span><h2>Add New School Year</h2></div>
    <div class="panel-card-body">
      <form method="POST" action="schoolyears.php">
        <input type="hidden" name="action" value="add">
        <div class="field">
          <label for="SCHOOLYEAR">School Year <span class="req">*</span></label>
          <input type="text" id="SCHOOLYEAR" name="SCHOOLYEAR" placeholder="e.g. 2026-2027" required
                 value="<?= htmlspecialchars($_POST['SCHOOLYEAR'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="DESCRIPTION">Description <span class="req">*</span></label>
          <input type="text" id="DESCRIPTION" name="DESCRIPTION" placeholder="e.g. AY 2026-2027" required
                 value="<?= htmlspecialchars($_POST['DESCRIPTION'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-block">+ Add School Year</button>
      </form>
    </div>
  </div>

  <div class="panel-card">
    <div class="panel-card-head"><span class="bar"></span><h2>All School Years</h2></div>
    <div class="panel-card-body">
      <form method="GET" action="schoolyears.php" class="search-row">
        <input type="text" name="q" placeholder="Search school year..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn btn-primary">🔍 Search</button>
        <a href="schoolyears.php" class="btn btn-secondary">↺ Clear</a>
      </form>
      <table class="table">
        <thead>
          <tr><th class="col-num">#</th><th>School Year</th><th>Description</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="empty-state">No school years found.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>

              <td><?= htmlspecialchars($r['SCHOOLYEAR']) ?></td>
              <td><?= htmlspecialchars($r['DESCRIPTION']) ?></td>
              <td class="actions">
                <a href="schoolyear_edit.php?id=<?= (int)$r['ID'] ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                <form method="POST" action="schoolyear_delete.php" style="display:inline"
                      onsubmit="return confirm('Delete this school year? Students assigned to it will be unlinked.');">
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
