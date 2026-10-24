<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        $inUse = $pdo->prepare("SELECT COUNT(*) c FROM tbl_users WHERE tbl_usertype_id = ?");
        $inUse->execute([$id]);
        $count = $inUse->fetch()['c'];

        if ($count > 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => "Can't delete: $count user(s) are still assigned to this user type."];
        } else {
            $stmt = $pdo->prepare("DELETE FROM tbl_usertype WHERE ID = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = $stmt->rowCount()
                ? ['type' => 'success', 'message' => 'User type deleted.']
                : ['type' => 'error', 'message' => 'User type was not found.'];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid delete request.'];
    }
}
header("Location: usertypes.php");
exit;
