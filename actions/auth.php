<?php
// actions/auth.php - Authentication Processor
require_once __DIR__ . '/../config/init.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../login.php');
        exit;
    }

    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        setFlashMessage('error', 'Please enter both email and password.');
        header('Location: ../login.php');
        exit;
    }

    try {
        $pdo = getDBConnection(true);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar'];

            logActivity($pdo, $user['id'], 'user_login', "{$user['name']} signed in.");

            setFlashMessage('success', "Welcome back, {$user['name']}!");
            header('Location: ../index.php');
            exit;
        } else {
            setFlashMessage('error', 'Invalid email or password. Please try again.');
            header('Location: ../login.php');
            exit;
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Login error: ' . $e->getMessage());
        header('Location: ../login.php');
        exit;
    }
}

if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../register.php');
        exit;
    }

    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $title = sanitize($_POST['title'] ?? 'Team Member');

    if (empty($name) || empty($email) || empty($password)) {
        setFlashMessage('error', 'All required fields must be filled.');
        header('Location: ../register.php');
        exit;
    }

    if (strlen($password) < 6) {
        setFlashMessage('error', 'Password must be at least 6 characters long.');
        header('Location: ../register.php');
        exit;
    }

    try {
        $pdo = getDBConnection(true);
        
        // Check if email already registered
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            setFlashMessage('error', 'An account with this email address already exists.');
            header('Location: ../register.php');
            exit;
        }

        // Hash password securely
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $role = 'member'; // Default new registrations to member

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, title, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$name, $email, $hashed, $role, $title]);
        $newUserId = $pdo->lastInsertId();

        // Auto login
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_avatar'] = null;

        logActivity($pdo, $newUserId, 'user_registered', "$name registered a new account.");

        setFlashMessage('success', "Welcome to TaskFlow Studio, {$name}!");
        header('Location: ../index.php');
        exit;
    } catch (Exception $e) {
        setFlashMessage('error', 'Registration failed: ' . $e->getMessage());
        header('Location: ../register.php');
        exit;
    }
}

if ($action === 'logout') {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    header('Location: ../login.php');
    exit;
}

header('Location: ../login.php');
exit;
