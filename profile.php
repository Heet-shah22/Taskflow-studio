<?php
// profile.php - User Account Profile & Security Settings
$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';

try {
    $pdo = getDBConnection(true);
    $userId = $currentUser['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            $name = sanitize($_POST['name'] ?? '');
            $title = sanitize($_POST['title'] ?? '');

            if (!empty($name)) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, title = ? WHERE id = ?");
                $stmt->execute([$name, $title, $userId]);
                $_SESSION['user_name'] = $name;
                setFlashMessage('success', 'Profile details updated.');
                header('Location: profile.php');
                exit;
            }
        } elseif ($_POST['action'] === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';

            if (strlen($newPass) < 6) {
                setFlashMessage('error', 'New password must be at least 6 characters.');
            } else {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

                if ($user && password_verify($currentPass, $user['password'])) {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                    $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->execute([$newHash, $userId]);
                    setFlashMessage('success', 'Password changed successfully.');
                } else {
                    setFlashMessage('error', 'Current password was incorrect.');
                }
            }
            header('Location: profile.php');
            exit;
        }
    }

    $stmt = $pdo->prepare("
        SELECT u.*,
            (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id) AS assigned_tasks_count,
            (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id AND status = 'done') AS completed_tasks_count
        FROM users u WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

} catch (Exception $e) {
    echo "<div class='alert alert-error'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="page-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Account Profile</h1>
            <p class="page-subtitle">Manage personal details, role credentials, and password security.</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; max-width: 1000px;">
        <!-- Left: Summary Card -->
        <div class="card" style="text-align: center; height: fit-content;">
            <div class="avatar avatar-lg" style="margin: 0 auto 1.25rem;">
                <?= getInitials($user['name']) ?>
            </div>
            <h2 style="font-size: 1.3rem; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($user['name']) ?></h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.2rem;"><?= htmlspecialchars($user['title'] ?: 'Team Member') ?></p>
            <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.2rem;"><?= htmlspecialchars($user['email']) ?></p>

            <div style="margin-top: 1rem;">
                <span class="badge badge-<?= $user['role'] === 'admin' ? 'indigo' : ($user['role'] === 'manager' ? 'purple' : 'teal') ?>">
                    <?= ucfirst($user['role']) ?> Privileges
                </span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                <div style="background: var(--bg-input); padding: 0.75rem; border-radius: var(--radius-md);">
                    <div style="font-size: 1.3rem; font-weight: 700; color: var(--primary);"><?= $user['assigned_tasks_count'] ?></div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Assigned Tasks</div>
                </div>
                <div style="background: var(--bg-input); padding: 0.75rem; border-radius: var(--radius-md);">
                    <div style="font-size: 1.3rem; font-weight: 700; color: var(--success);"><?= $user['completed_tasks_count'] ?></div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Completed</div>
                </div>
            </div>
        </div>

        <!-- Right: Settings Forms -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Profile Info Form -->
            <div class="card">
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--text-main);">Personal Information</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Job Title</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($user['title'] ?: '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address (Managed by System)</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity: 0.6; cursor: not-allowed;">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">Save Profile Details</button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="card">
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--text-main);">Security & Password</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Min. 6 chars" required minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-secondary btn-sm">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
