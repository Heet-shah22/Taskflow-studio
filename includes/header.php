<?php
// includes/header.php - Global Header & Authentication Guard
require_once __DIR__ . '/../config/init.php';

// Ensure user is authenticated for app pages
requireAuth();

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - TaskFlow Studio' : 'TaskFlow Studio - Modern Task & Project Management' ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="app-main">
            <?php include __DIR__ . '/navbar.php'; ?>
            
            <?php if ($flash): ?>
                <div style="padding: 1.5rem 2rem 0;">
                    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                </div>
            <?php endif; ?>
