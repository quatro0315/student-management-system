<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Term';
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $term = trim($_POST['TERM'] ?? '');
    $description = trim($_POST['DESCRIPTION'] ?? '');

    if ($term === '') $errors[] = "Term is required.";
    if ($description === '') $errors[] = "Description is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_term WHERE TERM = ?");
        $check->execute([$term]);
        if ($check->fetch()) $errors[] = "That term already exists.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO tbl_term (TERM, DESCRIPTION) VALUES (?, ?)");
        $stmt->execute([$term, $description]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Term added successfully.'];
        header("Location: terms.php");
        exit;
    }
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM tbl_term";
$params = [];
if ($q !== '') {
    $sql .= " WHERE TERM LIKE ? OR DESCRIPTION LIKE ?";
    $params = ["%$q%", "%$q%"];
}
$sql .= " ORDER BY ID ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$total = $pdo->query("SELECT COUNT(*) c FROM tbl_term")->fetch()['c'];

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Term</h1><p>Manage academic terms/semesters.</p></div>
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
    <div class="panel-card-head"><span class="bar"></span><h2>Add New Term</h2></div>
    <div class="panel-card-body">
      <form method="POST" action="terms.php">
        <input type="hidden" name="action" value="add">
        <div class="field">
          <label for="TERM">Term <span class="req">*</span></label>
          <input type="text" id="TERM" name="TERM" placeholder="e.g. 1ST SEM" required
                 value="<?= htmlspecialchars($_POST['TERM'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="DESCRIPTION">Description <span class="req">*</span></label>
          <input type="text" id="DESCRIPTION" name="DESCRIPTION" placeholder="e.g. FIRST SEMESTER" required
                 value="<?= htmlspecialchars($_POST['DESCRIPTION'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-block">+ Add Term</button>
      </form>
    </div>
  </div>

  <div class="panel-card">
    <div class="panel-card-head"><span class="bar"></span><h2>All Terms</h2></div>
    <div class="panel-card-body">
      <form method="GET" action="terms.php" class="search-row">
        <input type="text" name="q" placeholder="Search term..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn btn-primary">🔍 Search</button>
        <a href="terms.php" class="btn btn-secondary">↺ Clear</a>
      </form>
      <table class="table">
        <thead>
          <tr><th class="col-num">#</th><th>Term</th><th>Description</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="empty-state">No terms found.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>

              <td><?= htmlspecialchars($r['TERM']) ?></td>
              <td><?= htmlspecialchars($r['DESCRIPTION']) ?></td>
              <td class="actions">
                <a href="term_edit.php?id=<?= (int)$r['ID'] ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                <form method="POST" action="term_delete.php" style="display:inline"
                      onsubmit="return confirm('Delete this term?');">
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
