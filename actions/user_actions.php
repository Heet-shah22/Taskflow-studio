<?php
// actions/user_actions.php - Team & User Management Actions
require_once __DIR__ . '/../config/init.php';

requireRole(['admin']);
$currentUser = getCurrentUser();
$action = $_REQUEST['action'] ?? '';

try {
    $pdo = getDBConnection(true);

    if ($action === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? 'password123';
        $role = in_array($_POST['role'] ?? '', ['admin', 'manager', 'member']) ? $_POST['role'] : 'member';
        $title = sanitize($_POST['title'] ?? 'Team Member');

        if (empty($name) || empty($email)) {
            setFlashMessage('error', 'Name and email are required.');
            header('Location: ../users.php');
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            setFlashMessage('error', 'A user with this email already exists.');
            header('Location: ../users.php');
            exit;
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, title, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $hashed, $role, $title]);

        logActivity($pdo, $currentUser['id'], 'user_created', "Added new team member \"$name\" ($email)");
        setFlashMessage('success', "Team member $name added successfully.");
        header('Location: ../users.php');
        exit;
    }

    if ($action === 'update_role') {
        $userId = intval($_POST['user_id'] ?? 0);
        $newRole = in_array($_POST['role'] ?? '', ['admin', 'manager', 'member']) ? $_POST['role'] : 'member';

        if ($userId === $currentUser['id']) {
            setFlashMessage('error', 'You cannot change your own role.');
            header('Location: ../users.php');
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);

        logActivity($pdo, $currentUser['id'], 'user_role_changed', "Changed user #$userId role to $newRole");
        setFlashMessage('success', "User role updated to " . ucfirst($newRole));
        header('Location: ../users.php');
        exit;
    }

} catch (Exception $e) {
    setFlashMessage('error', 'Action failed: ' . $e->getMessage());
    header('Location: ../users.php');
    exit;
}
