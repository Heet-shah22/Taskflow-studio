<?php
// logout.php - Sign out redirector
require_once __DIR__ . '/config/init.php';

if (isLoggedIn()) {
    try {
        $pdo = getDBConnection(true);
        $u = getCurrentUser();
        logActivity($pdo, $u['id'], 'user_logout', "{$u['name']} signed out.");
    } catch (Exception $e) {}
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

header('Location: login.php');
exit;
