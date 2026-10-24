<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';

$pageTitle = 'User Type';
$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $usertype = trim($_POST['Usertype'] ?? '');

    if ($usertype === '') $errors[] = "User type name is required.";

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_usertype WHERE Usertype = ?");
        $check->execute([$usertype]);
        if ($check->fetch()) $errors[] = "That user type already exists.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO tbl_usertype (Usertype) VALUES (?)");
        $stmt->execute([$usertype]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User type added successfully.'];
        header("Location: usertypes.php");
        exit;
    }
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT ut.*, (SELECT COUNT(*) FROM tbl_users u WHERE u.tbl_usertype_id = ut.ID) AS user_count FROM tbl_usertype ut";
$params = [];
if ($q !== '') {
    $sql .= " WHERE ut.Usertype LIKE ?";
    $params = ["%$q%"];
}
$sql .= " ORDER BY ut.ID ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$total = $pdo->query("SELECT COUNT(*) c FROM tbl_usertype")->fetch()['c'];

require_once 'includes/header.php';
?>

<div class="page-head">
  <div><h1>User Types</h1><p>Manage system user types.</p></div>
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
    <div class="panel-card-head"><span class="bar"></span><h2>Add New User Type</h2></div>
    <div class="panel-card-body">
      <form method="POST" action="usertypes.php">
        <input type="hidden" name="action" value="add">
        <div class="field">
          <label for="Usertype">User Type <span class="req">*</span></label>
          <input type="text" id="Usertype" name="Usertype" placeholder="e.g. Registrar" required
                 value="<?= htmlspecialchars($_POST['Usertype'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-block">+ Add User Type</button>
      </form>
    </div>
  </div>

  <div class="panel-card">
    <div class="panel-card-head"><span class="bar"></span><h2>All User Types</h2></div>
    <div class="panel-card-body">
      <form method="GET" action="usertypes.php" class="search-row">
        <input type="text" name="q" placeholder="Search user type..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn btn-primary">🔍 Search</button>
        <a href="usertypes.php" class="btn btn-secondary">↺ Clear</a>
      </form>
      <table class="table">
        <thead>
          <tr><th class="col-num">#</th><th>Usertype</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="4" class="empty-state">No user types found.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($r['Usertype']) ?></td>
              <td class="actions">
                <a href="usertype_edit.php?id=<?= (int)$r['ID'] ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                <form method="POST" action="usertype_delete.php" style="display:inline"
                      onsubmit="return confirm('Delete this user type?');">
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
