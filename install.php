<?php
// install.php - 1-Click Database Setup & Configuration Wizard
require_once __DIR__ . '/config/db.php';

$message = null;
$error = null;
$installed = false;

// Check if database and tables already exist
try {
    $pdo = getDBConnection(false);
    $dbCheck = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'")->fetch();
    if ($dbCheck) {
        $pdo->exec("USE " . DB_NAME);
        $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if ($check) {
            $installed = true;
        }
    }
} catch (Exception $e) {
    $installed = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    try {
        // Connect to MySQL server without selecting database
        $pdo = getDBConnection(false);
        
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("database.sql file not found!");
        }

        $sql = file_get_contents($sqlFile);
        
        // Execute queries
        $pdo->exec($sql);
        
        // Generate fresh bcrypt hash for demo accounts to guarantee 100% PHP compatibility
        $samplePassword = password_hash('password123', PASSWORD_BCRYPT);
        $pdo->exec("USE " . DB_NAME);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE password LIKE '$2y$%'");
        $updateStmt->execute([$samplePassword]);

        $message = "Database 'taskflow_db' and demo dataset successfully initialized!";
        $installed = true;
    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow Studio - Setup & Installation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container" style="max-width: 620px;">
        <div class="auth-brand">
            <div class="brand-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h1>TaskFlow Studio</h1>
            <p>Database & Environment Setup Wizard</p>
        </div>

        <div class="card auth-card">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <div class="setup-status-box">
                <div class="status-row">
                    <span>Database Host:</span>
                    <code><?= htmlspecialchars(DB_HOST) ?></code>
                </div>
                <div class="status-row">
                    <span>Database Name:</span>
                    <code><?= htmlspecialchars(DB_NAME) ?></code>
                </div>
                <div class="status-row">
                    <span>Database User:</span>
                    <code><?= htmlspecialchars(DB_USER) ?></code>
                </div>
                <div class="status-row">
                    <span>Schema Status:</span>
                    <span class="badge <?= $installed ? 'badge-success' : 'badge-warning' ?>">
                        <?= $installed ? 'Ready & Configured' : 'Needs Initialization' ?>
                    </span>
                </div>
            </div>

            <?php if (!$installed): ?>
                <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    TaskFlow Studio needs to create the <code><?= htmlspecialchars(DB_NAME) ?></code> database, relational tables, and sample data. Click the button below to initialize everything automatically.
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="install">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Run 1-Click Database Setup
                    </button>
                </form>
            <?php else: ?>
                <div class="setup-success-details">
                    <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--text-primary);">Demo Accounts Ready to Test:</h3>
                    <div class="account-table-wrapper">
                        <table class="account-table">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Password</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge badge-indigo">Admin</span> (Heet)</td>
                                    <td><code>admin@taskflow.com</code></td>
                                    <td><code>password123</code></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-purple">Manager</span> (Rishikesh)</td>
                                    <td><code>rishikesh@taskflow.com</code></td>
                                    <td><code>password123</code></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-teal">Member</span> (Sujal)</td>
                                    <td><code>sujal@taskflow.com</code></td>
                                    <td><code>password123</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <a href="login.php" class="btn btn-primary btn-block btn-lg" style="text-decoration: none;">
                        Proceed to Login &rarr;
                    </a>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="install">
                        <button type="submit" class="btn btn-secondary btn-lg" title="Re-creates tables and resets demo data">
                            Reset Data
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="auth-footer" style="margin-top: 1.5rem;">
                <p>Configured in <code>config/db.php</code> &bull; XAMPP default: user <code>root</code> with empty password.</p>
            </div>
        </div>
    </div>
</body>
</html>
