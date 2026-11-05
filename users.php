<?php
// users.php - Team & User Management
$pageTitle = 'Team Members';
require_once __DIR__ . '/includes/header.php';

try {
    $pdo = getDBConnection(true);

    $usersStmt = $pdo->query("
        SELECT u.*,
            (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id) AS assigned_tasks_count,
            (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id AND status = 'done') AS completed_tasks_count,
            (SELECT COUNT(*) FROM project_members WHERE user_id = u.id) AS project_count
        FROM users u
        ORDER BY 
            CASE WHEN u.role = 'admin' THEN 1 WHEN u.role = 'manager' THEN 2 ELSE 3 END,
            u.name ASC
    ");
    $teamMembers = $usersStmt->fetchAll();

} catch (Exception $e) {
    echo "<div class='alert alert-error'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="page-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Team Directory</h1>
            <p class="page-subtitle">Manage workspace collaborators, access privileges, and workload distributions.</p>
        </div>
        <div>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <button class="btn btn-primary" onclick="openModal('addUserModal')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    <span>Add Member</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Team Members Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
        <?php foreach ($teamMembers as $m): ?>
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div class="avatar avatar-lg">
                            <?= getInitials($m['name']) ?>
                        </div>
                        <span class="badge badge-<?= ($m['role'] === 'admin') ? 'indigo' : (($m['role'] === 'manager') ? 'purple' : 'teal') ?>">
                            <?= ucfirst($m['role']) ?>
                        </span>
                    </div>

                    <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.2rem;">
                        <?= htmlspecialchars($m['name']) ?>
                    </h2>
                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.25rem;">
                        <?= htmlspecialchars($m['title'] ?: 'Team Member') ?>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                        <?= htmlspecialchars($m['email']) ?>
                    </div>
                </div>

                <div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; background: var(--bg-input); padding: 0.75rem; border-radius: var(--radius-md); text-align: center; margin-bottom: 1rem; border: 1px solid var(--border-subtle);">
                        <div>
                            <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-main);"><?= $m['project_count'] ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Projects</div>
                        </div>
                        <div>
                            <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary);"><?= $m['assigned_tasks_count'] ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Assigned</div>
                        </div>
                        <div>
                            <div style="font-size: 1.1rem; font-weight: 700; color: var(--success);"><?= $m['completed_tasks_count'] ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Resolved</div>
                        </div>
                    </div>

                    <?php if ($currentUser['role'] === 'admin' && $m['id'] !== $currentUser['id']): ?>
                        <form action="actions/user_actions.php" method="POST" style="display: flex; gap: 0.5rem; align-items: center; margin: 0;">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                            <label style="font-size: 0.75rem; color: var(--text-muted);">Role:</label>
                            <select name="role" class="form-control" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onchange="this.form.submit()">
                                <option value="member" <?= ($m['role'] === 'member') ? 'selected' : '' ?>>Member</option>
                                <option value="manager" <?= ($m['role'] === 'manager') ? 'selected' : '' ?>>Manager</option>
                                <option value="admin" <?= ($m['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </form>
                    <?php else: ?>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-align: right;">
                            Joined <?= date('M Y', strtotime($m['created_at'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal: Add New Team Member (Admin Only) -->
<div class="modal-backdrop" id="addUserModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">Invite New Team Member</div>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form action="actions/user_actions.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="Jane Doe" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Job Title / Specialty</label>
                    <input type="text" name="title" class="form-control" placeholder="DevOps Engineer / Frontend Specialist">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" placeholder="jane@company.com" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Default Password</label>
                        <input type="password" name="password" class="form-control" value="password123" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Access Role</label>
                        <select name="role" class="form-control">
                            <option value="member" selected>Member</option>
                            <option value="manager">Project Manager</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Member</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
