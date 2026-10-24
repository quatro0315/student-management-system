<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Section';
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $section = trim($_POST['SECTION'] ?? '');
    $description = trim($_POST['DESCRIPTION'] ?? '');

    if ($section === '') $errors[] = "Section name is required.";
    if ($description === '') $errors[] = "Description is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_section WHERE SECTION = ?");
        $check->execute([$section]);
        if ($check->fetch()) $errors[] = "That section already exists.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO tbl_section (SECTION, DESCRIPTION) VALUES (?, ?)");
        $stmt->execute([$section, $description]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Section added successfully.'];
        header("Location: sections.php");
        exit;
    }
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM tbl_section";
$params = [];
if ($q !== '') {
    $sql .= " WHERE SECTION LIKE ? OR DESCRIPTION LIKE ?";
    $params = ["%$q%", "%$q%"];
}
$sql .= " ORDER BY SECTION ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$total = $pdo->query("SELECT COUNT(*) c FROM tbl_section")->fetch()['c'];

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>Section</h1><p>Manage class sections.</p></div>
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
    <div class="panel-card-head"><span class="bar"></span><h2>Add New Section</h2></div>
    <div class="panel-card-body">
      <form method="POST" action="sections.php">
        <input type="hidden" name="action" value="add">
        <div class="field">
          <label for="SECTION">Section <span class="req">*</span></label>
          <input type="text" id="SECTION" name="SECTION" placeholder="e.g. A" required
                 value="<?= htmlspecialchars($_POST['SECTION'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="DESCRIPTION">Description <span class="req">*</span></label>
          <input type="text" id="DESCRIPTION" name="DESCRIPTION" placeholder="e.g. SECTION A" required
                 value="<?= htmlspecialchars($_POST['DESCRIPTION'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-block">+ Add Section</button>
      </form>
    </div>
  </div>

  <div class="panel-card">
    <div class="panel-card-head"><span class="bar"></span><h2>All Sections</h2></div>
    <div class="panel-card-body">
      <form method="GET" action="sections.php" class="search-row">
        <input type="text" name="q" placeholder="Search section..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn btn-primary">🔍 Search</button>
        <a href="sections.php" class="btn btn-secondary">↺ Clear</a>
      </form>
      <table class="table">
        <thead>
          <tr><th class="col-num">#</th><th>Section</th><th>Description</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="empty-state">No sections found.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>

              <td><?= htmlspecialchars($r['SECTION']) ?></td>
              <td><?= htmlspecialchars($r['DESCRIPTION']) ?></td>
              <td class="actions">
                <a href="section_edit.php?id=<?= (int)$r['ID'] ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                <form method="POST" action="section_delete.php" style="display:inline"
                      onsubmit="return confirm('Delete this section? Students assigned to it will be unlinked.');">
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
