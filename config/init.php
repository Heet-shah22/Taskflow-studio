<?php
// config/init.php - Application Bootstrap and Helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Authentication Helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'     => $_SESSION['user_id'],
        'name'   => $_SESSION['user_name'] ?? 'User',
        'email'  => $_SESSION['user_email'] ?? '',
        'role'   => $_SESSION['user_role'] ?? 'member',
        'avatar' => $_SESSION['user_avatar'] ?? null
    ];
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireRole($roles) {
    requireAuth();
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    $current = getCurrentUser();
    if (!in_array($current['role'], $roles)) {
        setFlashMessage('error', 'Access denied. You do not have permission to perform this action.');
        header('Location: index.php');
        exit;
    }
}

// Sanitization & Security
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Flash Messages
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

// Activity Logger
function logActivity($pdo, $userId, $action, $details, $projectId = null, $taskId = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (project_id, task_id, user_id, action, details, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$projectId, $taskId, $userId, $action, $details]);
    } catch (Exception $e) {
        // Silently log or ignore to not break main flow
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Relative Time Helper
function timeAgo($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $difference = time() - $timestamp;

    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $mins = round($difference / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = round($difference / 3600);
        return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = round($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

// Get user initials for avatar fallback
function getInitials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $w) {
        if (!empty($w)) {
            $initials .= strtoupper($w[0]);
        }
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: 'U';
}
