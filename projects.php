<?php
// projects.php - All Workspace Projects
$pageTitle = 'Projects Directory';
require_once __DIR__ . '/includes/header.php';

try {
    $pdo = getDBConnection(true);

    $filterStatus = $_GET['status'] ?? 'all';
    $validStatuses = ['planning', 'in_progress', 'completed', 'on_hold'];

    $sql = "
        SELECT p.*, u.name AS creator_name,
            (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) AS task_count,
            (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'done') AS done_count,
            (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS member_count
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.id
    ";

    $params = [];
    if (in_array($filterStatus, $validStatuses)) {
        $sql .= " WHERE p.status = ?";
        $params[] = $filterStatus;
    }
    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();

} catch (Exception $e) {
    echo "<div class='alert alert-error'>Error fetching projects: " . htmlspecialchars($e->getMessage()) . "</div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="page-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Projects</h1>
            <p class="page-subtitle">Track roadmaps, sprint goals, and delivery schedules across all teams.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'manager'): ?>
                <button class="btn btn-primary" onclick="openModal('newProjectModal')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>New Project</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters Bar -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 2rem; overflow-x: auto; padding-bottom: 0.5rem;">
        <a href="projects.php" class="btn btn-sm <?= ($filterStatus === 'all') ? 'btn-primary' : 'btn-secondary' ?>">
            All Projects (<?= count($projects) ?>)
        </a>
        <a href="projects.php?status=in_progress" class="btn btn-sm <?= ($filterStatus === 'in_progress') ? 'btn-primary' : 'btn-secondary' ?>">
            In Progress
        </a>
        <a href="projects.php?status=planning" class="btn btn-sm <?= ($filterStatus === 'planning') ? 'btn-primary' : 'btn-secondary' ?>">
            Planning
        </a>
        <a href="projects.php?status=completed" class="btn btn-sm <?= ($filterStatus === 'completed') ? 'btn-primary' : 'btn-secondary' ?>">
            Completed
        </a>
        <a href="projects.php?status=on_hold" class="btn btn-sm <?= ($filterStatus === 'on_hold') ? 'btn-primary' : 'btn-secondary' ?>">
            On Hold
        </a>
    </div>

    <!-- Projects Grid -->
    <?php if (empty($projects)): ?>
        <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--text-muted); margin-bottom: 1rem;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem;">No projects found</h3>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Get started by initializing a new project roadmap.</p>
            <button class="btn btn-primary" onclick="openModal('newProjectModal')">Create Project</button>
        </div>
    <?php else: ?>
        <div class="projects-grid">
            <?php foreach ($projects as $proj): 
                $progress = $proj['task_count'] > 0 ? round(($proj['done_count'] / $proj['task_count']) * 100) : 0;
            ?>
                <div class="card project-card" onclick="window.location.href='project-detail.php?id=<?= $proj['id'] ?>'">
                    <div>
                        <div class="project-card-header">
                            <span class="badge priority-<?= $proj['priority'] ?>"><?= ucfirst($proj['priority']) ?></span>
                            <span class="badge badge-<?= $proj['status'] === 'completed' ? 'success' : ($proj['status'] === 'in_progress' ? 'indigo' : 'gray') ?>">
                                <?= ucwords(str_replace('_', ' ', $proj['status'])) ?>
                            </span>
                        </div>
                        <h2 class="project-card-title"><?= htmlspecialchars($proj['title']) ?></h2>
                        <p class="project-card-desc"><?= htmlspecialchars($proj['description'] ?: 'No description provided.') ?></p>
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 0.4rem; color: var(--text-secondary);">
                            <span>Progress (<?= $proj['done_count'] ?> of <?= $proj['task_count'] ?> tasks)</span>
                            <span style="font-weight: 700; color: var(--text-main);"><?= $progress ?>%</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?= $progress ?>%;"></div>
                        </div>

                        <div class="project-meta-row">
                            <div style="display: flex; align-items: center; gap: 0.35rem;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span><?= $proj['due_date'] ? date('M j, Y', strtotime($proj['due_date'])) : 'No due date' ?></span>
                            </div>
                            <?php if ($proj['budget'] > 0): ?>
                                <span style="color: var(--success); font-weight: 600;">₹<?= number_format($proj['budget']) ?></span>
                            <?php endif; ?>
                            <span><?= $proj['member_count'] ?> members</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
