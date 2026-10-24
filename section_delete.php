<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM tbl_section WHERE ID = ?");
        $stmt->execute([$id]);
        $_SESSION['flash'] = $stmt->rowCount()
            ? ['type' => 'success', 'message' => 'Section deleted.']
            : ['type' => 'error', 'message' => 'Section was not found.'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid delete request.'];
    }
}
header("Location: sections.php");
exit;
