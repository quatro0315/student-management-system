<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Year Level';
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $yearlevel = trim($_POST['YEARLEVEL'] ?? '');
    $description = trim($_POST['DESCRIPTION'] ?? '');

    if ($yearlevel === '') $errors[] = "Year level is required.";
    if ($description === '') $errors[] = "Description is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_yearlevel WHERE YEARLEVEL = ?");
        $check->execute([$yearlevel]);
        if ($check->fetch()) $errors[] = "That year level already exists.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO tbl_yearlevel (YEARLEVEL, DESCRIPTION) VALUES (?, ?)");
        $stmt->execute([$yearlevel, $description]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Year level added successfully.'];
        header("Location: yearlevels.php");
        exit;
    }
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM tbl_yearlevel";
$params = [];
if ($q !== '') {
    $sql .= " WHERE YEARLEVEL LIKE ? OR DESCRIPTION LIKE ?";
    $params = ["%$q%", "%$q%"];
}
$sql .= " ORDER BY ID ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$total = $pdo->query("SELECT COUNT(*) c FROM tbl_yearlevel")->fetch()['c'];

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Year Level</h1><p>Manage academic year levels.</p></div>
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
    <div class="panel-card-head"><span class="bar"></span><h2>Add New Year Level</h2></div>
    <div class="panel-card-body">
      <form method="POST" action="yearlevels.php">
        <input type="hidden" name="action" value="add">
        <div class="field">
          <label for="YEARLEVEL">Year Level <span class="req">*</span></label>
          <input type="text" id="YEARLEVEL" name="YEARLEVEL" placeholder="e.g. 1ST" required
                 value="<?= htmlspecialchars($_POST['YEARLEVEL'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="DESCRIPTION">Description <span class="req">*</span></label>
          <input type="text" id="DESCRIPTION" name="DESCRIPTION" placeholder="e.g. FIRST YEAR" required
                 value="<?= htmlspecialchars($_POST['DESCRIPTION'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-block">+ Add Year Level</button>
      </form>
    </div>
  </div>

  <div class="panel-card">
    <div class="panel-card-head"><span class="bar"></span><h2>All Year Levels</h2></div>
    <div class="panel-card-body">
      <form method="GET" action="yearlevels.php" class="search-row">
        <input type="text" name="q" placeholder="Search year level..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn btn-primary">🔍 Search</button>
        <a href="yearlevels.php" class="btn btn-secondary">↺ Clear</a>
      </form>
      <table class="table">
        <thead>
          <tr><th class="col-num">#</th><th>Year Level</th><th>Description</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="empty-state">No year levels found.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($r['YEARLEVEL']) ?></td>
              <td><?= htmlspecialchars($r['DESCRIPTION']) ?></td>
              <td class="actions">
                <a href="yearlevel_edit.php?id=<?= (int)$r['ID'] ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                <form method="POST" action="yearlevel_delete.php" style="display:inline"
                      onsubmit="return confirm('Delete this year level? Students assigned to it will be unlinked.');">
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
