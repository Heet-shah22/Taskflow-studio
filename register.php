<?php
// register.php - New Account Registration
require_once __DIR__ . '/config/init.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - TaskFlow Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-brand">
            <div class="brand-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                    <polyline points="2 17 12 22 22 17"/>
                    <polyline points="2 12 12 17 22 12"/>
                </svg>
            </div>
            <h1>Join TaskFlow Studio</h1>
            <p>Collaborate with your team across projects seamlessly</p>
        </div>

        <div class="card auth-card">
            <?php if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <form action="actions/auth.php" method="POST">
                <input type="hidden" name="action" value="register">

                <div class="form-group">
                    <label class="form-label" for="regName">Full Name *</label>
                    <input type="text" id="regName" name="name" class="form-control" placeholder="Alex Morgan" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="regTitle">Role / Job Title</label>
                    <input type="text" id="regTitle" name="title" class="form-control" placeholder="Frontend Developer / Designer">
                </div>

                <div class="form-group">
                    <label class="form-label" for="regEmail">Work Email *</label>
                    <input type="email" id="regEmail" name="email" class="form-control" placeholder="alex@company.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="regPassword">Password *</label>
                    <input type="password" id="regPassword" name="password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                    Create Workspace Account &rarr;
                </button>
            </form>

            <div class="auth-footer">
                Already registered? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>
