<?php
/**
 * logout.php
 * Fully destroys the session (variables, cookie, and session data)
 * then sends the user back to the login page.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear session variables
$_SESSION = [];

// Destroy the session cookie itself
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session data on the server
session_destroy();

header("Location: login.php");
exit;
