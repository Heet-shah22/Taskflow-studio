<?php
// activity.php - Workspace Audit Log and Activity Stream
$pageTitle = 'Activity Log';
require_once __DIR__ . '/includes/header.php';

try {
    $pdo = getDBConnection(true);

    $activitiesStmt = $pdo->query("
        SELECT a.*, u.name AS user_name, u.role AS user_role, p.title AS project_title, t.title AS task_title
        FROM activity_logs a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN projects p ON a.project_id = p.id
        LEFT JOIN tasks t ON a.task_id = t.id
        ORDER BY a.created_at DESC
        LIMIT 50
    ");
    $activities = $activitiesStmt->fetchAll();

} catch (Exception $e) {
    echo "<div class='alert alert-error'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="page-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Activity Trail</h1>
            <p class="page-subtitle">Chronological record of updates, task transitions, discussions, and project lifecycle events.</p>
        </div>
    </div>

    <div class="card" style="max-width: 900px;">
        <?php if (empty($activities)): ?>
            <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No activities recorded yet.</p>
        <?php else: ?>
            <div class="activity-timeline">
                <?php foreach ($activities as $act): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot" style="<?= ($act['action'] === 'completed_task') ? 'border-color: var(--success);' : (($act['action'] === 'task_created' || $act['action'] === 'project_created') ? 'border-color: var(--primary);' : '') ?>"></div>
                        <div class="timeline-content">
                            <div class="timeline-meta">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div class="avatar avatar-sm"><?= getInitials($act['user_name']) ?></div>
                                    <span style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($act['user_name']) ?></span>
                                    <span class="badge badge-<?= ($act['user_role'] === 'admin') ? 'indigo' : (($act['user_role'] === 'manager') ? 'purple' : 'gray') ?>" style="font-size: 0.7rem; padding: 0.1rem 0.4rem;">
                                        <?= ucfirst($act['user_role']) ?>
                                    </span>
                                </div>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= timeAgo($act['created_at']) ?> &bull; <?= date('H:i', strtotime($act['created_at'])) ?></span>
                            </div>

                            <div style="font-size: 0.92rem; color: var(--text-main); margin-top: 0.4rem;">
                                <?= htmlspecialchars($act['details']) ?>
                            </div>

                            <div style="display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.78rem; color: var(--text-muted);">
                                <?php if ($act['project_title']): ?>
                                    <span>Project: <strong style="color: var(--primary);"><?= htmlspecialchars($act['project_title']) ?></strong></span>
                                <?php endif; ?>
                                <?php if ($act['task_title']): ?>
                                    <span>Task: <strong style="color: var(--text-secondary);"><?= htmlspecialchars($act['task_title']) ?></strong></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
