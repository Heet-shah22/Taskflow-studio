<?php
// login.php - Modern Authentication Portal
require_once __DIR__ . '/config/init.php';

// If already logged in, redirect to dashboard
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
    <title>Sign In - TaskFlow Studio</title>
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
            <h1>TaskFlow Studio</h1>
            <p>High-Performance Project Management Platform</p>
        </div>

        <div class="card auth-card">
            <?php if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <div class="demo-credentials-box">
                <div class="title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    Quick Demo Credentials (Click to fill):
                </div>
                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; flex-wrap: wrap;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="fillCreds('admin@taskflow.com', 'password123')">
                        Heet (Admin)
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="fillCreds('rishikesh@taskflow.com', 'password123')">
                        Rishikesh (Manager)
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="fillCreds('sujal@taskflow.com', 'password123')">
                        Sujal (Member)
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="fillCreds('harshil@taskflow.com', 'password123')">
                        Harshil (Member)
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="fillCreds('ativeer@taskflow.com', 'password123')">
                        Ativeer (Member)
                    </button>
                </div>
            </div>

            <form action="actions/auth.php" method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label class="form-label" for="loginEmail">Email Address</label>
                    <input type="email" id="loginEmail" name="email" class="form-control" placeholder="name@company.com" required autofocus>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                        <label class="form-label" for="loginPassword" style="margin-bottom: 0;">Password</label>
                    </div>
                    <input type="password" id="loginPassword" name="password" class="form-control" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                    Sign In to Workspace &rarr;
                </button>
            </form>

            <div class="auth-footer">
                Don't have an account yet? <a href="register.php">Create Account</a>
                <div style="margin-top: 0.75rem; font-size: 0.8rem;">
                    <a href="install.php" style="color: var(--text-muted);">Database Setup / Reset Wizard &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillCreds(email, pass) {
            document.getElementById('loginEmail').value = email;
            document.getElementById('loginPassword').value = pass;
        }
    </script>
</body>
</html>
