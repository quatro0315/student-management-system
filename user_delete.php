<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';
require_once 'includes/user_photo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid delete request.'];
    } elseif ($id === (int)$_SESSION['user_id']) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => "You can't delete your own account while logged in."];
    } else {
        $u = $pdo->prepare("SELECT USERNAME FROM tbl_users WHERE ID = ?");
        $u->execute([$id]);
        $victim = $u->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM tbl_users WHERE ID = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() && $victim) deleteUserPhoto($victim);
        $_SESSION['flash'] = $stmt->rowCount()
            ? ['type' => 'success', 'message' => 'User deleted.']
            : ['type' => 'error', 'message' => 'User was not found.'];
    }
}
header("Location: users.php");
exit;
