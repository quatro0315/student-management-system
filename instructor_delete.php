<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/instructor_upload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $sel = $pdo->prepare("SELECT PHOTO FROM tbl_instructors WHERE ID = ?");
        $sel->execute([$id]);
        $photo = $sel->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM tbl_instructors WHERE ID = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount()) {
            deleteInstructorPhoto($photo ?: null);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Instructor deleted.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Instructor was not found.'];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid delete request.'];
    }
}
header("Location: instructors.php");
exit;
