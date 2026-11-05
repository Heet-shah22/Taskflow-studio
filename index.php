<?php
// index.php - Main Dashboard
$pageTitle = 'Workspace Dashboard';
require_once __DIR__ . '/includes/header.php';

try {
    $pdo = getDBConnection(true);

    $isMember = ($currentUser['role'] === 'member');
    $userId = $currentUser['id'];

    if ($isMember) {
        // --- MEMBER SPECIFIC DATA ---
        // 1. Metric: Member's Active Projects
        $stmtP = $pdo->prepare("SELECT COUNT(DISTINCT project_id) FROM project_members WHERE user_id = ?");
        $stmtP->execute([$userId]);
        $totalProjects = $stmtP->fetchColumn();

        // 2. Metric: Member's Tasks Stats
        $stmtT = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ?");
        $stmtT->execute([$userId]);
        $totalTasks = $stmtT->fetchColumn();

        $stmtD = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'done'");
        $stmtD->execute([$userId]);
        $completedTasks = $stmtD->fetchColumn();

        $stmtIP = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'in_progress'");
        $stmtIP->execute([$userId]);
        $inProgressTasks = $stmtIP->fetchColumn();

        $stmtU = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND priority = 'urgent' AND status != 'done'");
        $stmtU->execute([$userId]);
        $urgentTasks = $stmtU->fetchColumn();

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        // 3. Member's Projects
        $projectsStmt = $pdo->prepare("
            SELECT p.*, u.name AS creator_name,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND assigned_to = ?) AS task_count,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND assigned_to = ? AND status = 'done') AS done_count
            FROM projects p
            JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE pm.user_id = ?
            ORDER BY p.updated_at DESC
            LIMIT 4
        ");
        $projectsStmt->execute([$userId, $userId, $userId]);
        $recentProjects = $projectsStmt->fetchAll();

        // 4. Member's Priority Tasks ONLY
        $urgentTasksStmt = $pdo->prepare("
            SELECT t.*, p.title AS project_title, u.name AS assigned_name
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.status != 'done' AND t.assigned_to = ?
            ORDER BY 
                CASE WHEN t.priority = 'urgent' THEN 1 WHEN t.priority = 'high' THEN 2 ELSE 3 END,
                t.due_date ASC
            LIMIT 6
        ");
        $urgentTasksStmt->execute([$userId]);
        $priorityTasks = $urgentTasksStmt->fetchAll();

        // 5. Activity Stream: Only relevant activities
        $activityStmt = $pdo->prepare("
            SELECT a.*, u.name AS user_name, p.title AS project_title
            FROM activity_logs a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN projects p ON a.project_id = p.id
            WHERE a.user_id = ? OR a.project_id IN (SELECT project_id FROM project_members WHERE user_id = ?)
            ORDER BY a.created_at DESC
            LIMIT 6
        ");
        $activityStmt->execute([$userId, $userId]);
        $recentActivities = $activityStmt->fetchAll();

    } else {
        // --- ADMIN & MANAGER ORGANIZATION-WIDE DATA ---
        // 1. Metric: Total Projects
        $totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();

        // 2. Metric: Tasks Stats
        $totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
        $completedTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'done'")->fetchColumn();
        $inProgressTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'in_progress'")->fetchColumn();
        $urgentTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE priority = 'urgent' AND status != 'done'")->fetchColumn();

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        // 3. Active Projects with calculated progress
        $projectsStmt = $pdo->query("
            SELECT p.*, u.name AS creator_name,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) AS task_count,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'done') AS done_count
            FROM projects p
            LEFT JOIN users u ON p.created_by = u.id
            ORDER BY p.updated_at DESC
            LIMIT 4
        ");
        $recentProjects = $projectsStmt->fetchAll();

        // 4. Upcoming / Urgent Tasks
        $urgentTasksStmt = $pdo->query("
            SELECT t.*, p.title AS project_title, u.name AS assigned_name
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.status != 'done'
            ORDER BY 
                CASE WHEN t.priority = 'urgent' THEN 1 WHEN t.priority = 'high' THEN 2 ELSE 3 END,
                t.due_date ASC
            LIMIT 6
        ");
        $priorityTasks = $urgentTasksStmt->fetchAll();

        // 5. Recent Activity Stream
        $activityStmt = $pdo->query("
            SELECT a.*, u.name AS user_name, p.title AS project_title
            FROM activity_logs a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN projects p ON a.project_id = p.id
            ORDER BY a.created_at DESC
            LIMIT 6
        ");
        $recentActivities = $activityStmt->fetchAll();
    }

    // --- DRILL-DOWN DETAIL LISTS FOR STAT CARDS ---
    if ($isMember) {
        $spStmt = $pdo->prepare("
            SELECT p.*,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND assigned_to = ?) AS my_tasks_count,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND assigned_to = ? AND status = 'done') AS my_done_count
            FROM projects p
            JOIN project_members pm ON p.id = pm.project_id
            WHERE pm.user_id = ?
            ORDER BY p.title ASC
        ");
        $spStmt->execute([$userId, $userId, $userId]);
        $statProjectsList = $spStmt->fetchAll();

        $scStmt = $pdo->prepare("
            SELECT t.*, p.title AS project_title
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            WHERE t.assigned_to = ? AND t.status = 'done'
            ORDER BY t.updated_at DESC
        ");
        $scStmt->execute([$userId]);
        $statCompletedList = $scStmt->fetchAll();

        $sipStmt = $pdo->prepare("
            SELECT t.*, p.title AS project_title
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            WHERE t.assigned_to = ? AND t.status = 'in_progress'
            ORDER BY t.due_date ASC
        ");
        $sipStmt->execute([$userId]);
        $statInProgressList = $sipStmt->fetchAll();

        $suStmt = $pdo->prepare("
            SELECT t.*, p.title AS project_title
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            WHERE t.assigned_to = ? AND t.priority = 'urgent' AND t.status != 'done'
            ORDER BY t.due_date ASC
        ");
        $suStmt->execute([$userId]);
        $statUrgentList = $suStmt->fetchAll();
    } else {
        $statProjectsList = $pdo->query("
            SELECT p.*,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) AS my_tasks_count,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'done') AS my_done_count
            FROM projects p
            ORDER BY p.title ASC
        ")->fetchAll();

        $statCompletedList = $pdo->query("
            SELECT t.*, p.title AS project_title, u.name AS assigned_name
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.status = 'done'
            ORDER BY t.updated_at DESC
            LIMIT 50
        ")->fetchAll();

        $statInProgressList = $pdo->query("
            SELECT t.*, p.title AS project_title, u.name AS assigned_name
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.status = 'in_progress'
            ORDER BY t.due_date ASC
        ")->fetchAll();

        $statUrgentList = $pdo->query("
            SELECT t.*, p.title AS project_title, u.name AS assigned_name
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.priority = 'urgent' AND t.status != 'done'
            ORDER BY t.due_date ASC
        ")->fetchAll();
    }

    // --- TEAM MEMBERS PROGRESS DATA ---
    $teamProgressStmt = $pdo->query("
        SELECT u.id, u.name, u.role, u.title,
            (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id) AS total_tasks,
            (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id AND status = 'done') AS completed_tasks,
            (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id AND status = 'in_progress') AS in_progress_tasks
        FROM users u
        ORDER BY 
            CASE WHEN u.role = 'admin' THEN 1 WHEN u.role = 'manager' THEN 2 ELSE 3 END,
            u.name ASC
    ");
    $teamProgressList = $teamProgressStmt->fetchAll();

} catch (Exception $e) {
    echo "<div class='alert alert-error' style='margin: 2rem;'>Error loading dashboard: " . htmlspecialchars($e->getMessage()) . "</div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="page-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Welcome back, <?= htmlspecialchars($currentUser['name']) ?></h1>
            <p class="page-subtitle">Here is a snapshot of your workspace activity, team progress, and upcoming deadlines.</p>
        </div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <button class="btn btn-secondary" onclick="openModal('teamProgressModal')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Team Progress</span>
            </button>
            <button class="btn btn-primary" onclick="openModal('newTaskModal')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Quick Task</span>
            </button>
        </div>
    </div>

    <!-- Stats Grid (Clickable Drill-down Cards) -->
    <div class="stats-grid">
        <div class="card stat-card" style="--accent-color: var(--primary);" onclick="openModal('statProjectsModal')" title="Click to view projects list">
            <div class="stat-header">
                <span class="stat-title"><?= $isMember ? 'My Projects' : 'Active Projects' ?></span>
                <div class="stat-icon" style="--icon-bg: rgba(99, 102, 241, 0.15); --icon-color: #818cf8;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                </div>
            </div>
            <div class="stat-value"><?= $totalProjects ?></div>
            <div class="stat-footer" style="justify-content: space-between;">
                <span style="color: var(--info);"><?= $isMember ? 'Assigned to your roadmap' : 'Across all departments' ?></span>
                <span style="color: var(--primary); font-size: 0.75rem; font-weight: 600;">View list &rarr;</span>
            </div>
        </div>

        <div class="card stat-card" style="--accent-color: var(--success);" onclick="openModal('statCompletedModal')" title="Click to view completed tasks">
            <div class="stat-header">
                <span class="stat-title"><?= $isMember ? 'My Completion' : 'Task Completion' ?></span>
                <div class="stat-icon" style="--icon-bg: rgba(16, 185, 129, 0.15); --icon-color: #34d399;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
            </div>
            <div class="stat-value"><?= $completionRate ?>%</div>
            <div class="stat-footer" style="justify-content: space-between;">
                <span><?= $completedTasks ?> of <?= $totalTasks ?> <?= $isMember ? 'your tasks' : 'tasks' ?> finalized</span>
                <span style="color: var(--success); font-size: 0.75rem; font-weight: 600;">View list &rarr;</span>
            </div>
        </div>

        <div class="card stat-card" style="--accent-color: var(--warning);" onclick="openModal('statInProgressModal')" title="Click to view in-progress tasks">
            <div class="stat-header">
                <span class="stat-title"><?= $isMember ? 'My In Progress' : 'In Progress' ?></span>
                <div class="stat-icon" style="--icon-bg: rgba(245, 158, 11, 0.15); --icon-color: #fbbf24;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <div class="stat-value"><?= $inProgressTasks ?></div>
            <div class="stat-footer" style="justify-content: space-between;">
                <span><?= $isMember ? 'Your active sprint tasks' : 'Active sprint items' ?></span>
                <span style="color: var(--warning); font-size: 0.75rem; font-weight: 600;">View list &rarr;</span>
            </div>
        </div>

        <div class="card stat-card" style="--accent-color: var(--danger);" onclick="openModal('statUrgentModal')" title="Click to view urgent tasks">
            <div class="stat-header">
                <span class="stat-title"><?= $isMember ? 'My Urgent Tasks' : 'Urgent Tasks' ?></span>
                <div class="stat-icon" style="--icon-bg: rgba(239, 68, 68, 0.15); --icon-color: #f87171;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
            </div>
            <div class="stat-value"><?= $urgentTasks ?></div>
            <div class="stat-footer" style="justify-content: space-between;">
                <span style="color: #f87171;"><?= $isMember ? 'Your urgent deadlines' : 'Requires immediate focus' ?></span>
                <span style="color: #f87171; font-size: 0.75rem; font-weight: 600;">View list &rarr;</span>
            </div>
        </div>
    </div>

    <!-- Middle Split: Active Projects & Priority Tasks -->
    <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Left: Recent Projects -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Featured Projects</h2>
                <a href="projects.php" class="btn btn-secondary btn-sm">View All &rarr;</a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                <?php foreach ($recentProjects as $proj): 
                    $prog = $proj['task_count'] > 0 ? round(($proj['done_count'] / $proj['task_count']) * 100) : 0;
                ?>
                    <div class="card project-card" onclick="window.location.href='project-detail.php?id=<?= $proj['id'] ?>'">
                        <div>
                            <div class="project-card-header">
                                <span class="badge priority-<?= $proj['priority'] ?>"><?= ucfirst($proj['priority']) ?></span>
                                <span class="badge badge-<?= $proj['status'] === 'completed' ? 'success' : ($proj['status'] === 'in_progress' ? 'primary' : 'gray') ?>">
                                    <?= ucwords(str_replace('_', ' ', $proj['status'])) ?>
                                </span>
                            </div>
                            <h3 class="project-card-title"><?= htmlspecialchars($proj['title']) ?></h3>
                            <p class="project-card-desc"><?= htmlspecialchars($proj['description'] ?: 'No description provided.') ?></p>
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.4rem; color: var(--text-secondary);">
                                <span>Progress (<?= $proj['done_count'] ?>/<?= $proj['task_count'] ?> tasks)</span>
                                <span style="font-weight: 700; color: var(--text-main);"><?= $prog ?>%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: <?= $prog ?>%;"></div>
                            </div>

                            <div class="project-meta-row">
                                <span>Lead: <?= htmlspecialchars($proj['creator_name']) ?></span>
                                <span>Due: <?= $proj['due_date'] ? date('M j', strtotime($proj['due_date'])) : 'Open' ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right: Urgent / High Priority Tasks -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Priority Queue</h2>
                <a href="tasks.php" class="btn btn-secondary btn-sm">All Tasks &rarr;</a>
            </div>

            <div class="card" style="padding: 1rem;">
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php if (empty($priorityTasks)): ?>
                        <p style="color: var(--text-muted); font-size: 0.9rem; padding: 1rem; text-align: center;">All clear! No urgent pending tasks.</p>
                    <?php else: ?>
                        <?php foreach ($priorityTasks as $t): ?>
                            <div class="task-card" onclick="viewTaskDetails(<?= $t['id'] ?>)" style="margin: 0;">
                                <div class="task-card-header">
                                    <span style="font-size: 0.75rem; color: var(--primary); font-weight: 600;"><?= htmlspecialchars($t['project_title']) ?></span>
                                    <span class="badge priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span>
                                </div>
                                <div class="task-card-title"><?= htmlspecialchars($t['title']) ?></div>
                                <div class="task-card-footer">
                                    <div style="display: flex; align-items: center; gap: 0.4rem;">
                                        <div class="avatar avatar-sm"><?= getInitials($t['assigned_name'] ?: 'U') ?></div>
                                        <span><?= htmlspecialchars($t['assigned_name'] ?: 'Unassigned') ?></span>
                                    </div>
                                    <span style="color: <?= (strtotime($t['due_date']) < time()) ? '#f87171' : 'var(--text-muted)' ?>;">
                                        <?= $t['due_date'] ? date('M j', strtotime($t['due_date'])) : 'No date' ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Bottom Row: Recent Workspace Activity Feed -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Real-time Activity Feed</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Audit log of tasks updated, comments added, and projects created.</p>
            </div>
            <a href="activity.php" class="btn btn-secondary btn-sm">Full Audit Trail &rarr;</a>
        </div>

        <div class="activity-timeline">
            <?php foreach ($recentActivities as $act): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-meta">
                            <span style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($act['user_name']) ?></span>
                            <span><?= timeAgo($act['created_at']) ?></span>
                        </div>
                        <div style="font-size: 0.9rem; color: var(--text-secondary);">
                            <?= htmlspecialchars($act['details']) ?>
                        </div>
                        <?php if ($act['project_title']): ?>
                            <div style="margin-top: 0.35rem; font-size: 0.75rem; color: var(--primary);">
                                in <?= htmlspecialchars($act['project_title']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- Modal: Projects Drill-Down -->
<!-- ============================================== -->
<div class="modal-backdrop" id="statProjectsModal">
    <div class="modal-dialog" style="max-width: 640px;">
        <div class="modal-header">
            <div class="modal-title"><?= $isMember ? 'My Assigned Projects' : 'Active Projects Overview' ?></div>
            <button class="modal-close" onclick="closeModal('statProjectsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (empty($statProjectsList)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No projects found.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                    <?php foreach ($statProjectsList as $sp): 
                        $spProg = $sp['my_tasks_count'] > 0 ? round(($sp['my_done_count'] / $sp['my_tasks_count']) * 100) : 0;
                    ?>
                        <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                                    <span class="badge priority-<?= $sp['priority'] ?>"><?= ucfirst($sp['priority']) ?></span>
                                    <span class="badge badge-<?= $sp['status'] === 'completed' ? 'success' : 'indigo' ?>"><?= ucwords(str_replace('_', ' ', $sp['status'])) ?></span>
                                </div>
                                <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">
                                    <?= htmlspecialchars($sp['title']) ?>
                                </h4>
                                <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                                    <span><?= $isMember ? 'Your Tasks:' : 'Total Tasks:' ?> <?= $sp['my_done_count'] ?> / <?= $sp['my_tasks_count'] ?> (<?= $spProg ?>%)</span>
                                    <?php if ($sp['due_date']): ?>
                                        <span>&bull; Due: <?= date('M j, Y', strtotime($sp['due_date'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="progress-bar-container" style="height: 5px;">
                                    <div class="progress-bar-fill" style="width: <?= $spProg ?>%;"></div>
                                </div>
                            </div>
                            <a href="project-detail.php?id=<?= $sp['id'] ?>" class="btn btn-secondary btn-sm" style="white-space: nowrap;">
                                Open Board &rarr;
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('statProjectsModal')">Close</button>
            <a href="projects.php" class="btn btn-primary btn-sm">All Projects Directory &rarr;</a>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- Modal: Completed Tasks Drill-Down -->
<!-- ============================================== -->
<div class="modal-backdrop" id="statCompletedModal">
    <div class="modal-dialog" style="max-width: 640px;">
        <div class="modal-header">
            <div class="modal-title"><?= $isMember ? 'My Completed Tasks' : 'Finalized Tasks' ?></div>
            <button class="modal-close" onclick="closeModal('statCompletedModal')">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (empty($statCompletedList)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No completed tasks yet. Finish a task to see it here!</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($statCompletedList as $st): ?>
                        <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 0.85rem 1rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--success-bg); color: var(--success); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div>
                                    <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.2rem;">
                                        <?= htmlspecialchars($st['title']) ?>
                                    </h4>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        Project: <span style="color: var(--primary);"><?= htmlspecialchars($st['project_title']) ?></span>
                                        <?php if (!empty($st['assigned_name']) && !$isMember): ?>
                                            &bull; Completed by: <?= htmlspecialchars($st['assigned_name']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-secondary btn-sm" onclick="closeModal('statCompletedModal'); viewTaskDetails(<?= $st['id'] ?>)">
                                Details
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('statCompletedModal')">Close</button>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- Modal: In-Progress Tasks Drill-Down -->
<!-- ============================================== -->
<div class="modal-backdrop" id="statInProgressModal">
    <div class="modal-dialog" style="max-width: 640px;">
        <div class="modal-header">
            <div class="modal-title"><?= $isMember ? 'My In-Progress Sprint Items' : 'All In-Progress Tasks' ?></div>
            <button class="modal-close" onclick="closeModal('statInProgressModal')">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (empty($statInProgressList)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No active in-progress tasks right now.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($statInProgressList as $st): ?>
                        <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 0.85rem 1rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.25rem;">
                                    <span class="badge priority-<?= $st['priority'] ?>"><?= ucfirst($st['priority']) ?></span>
                                    <span style="font-size: 0.75rem; color: var(--primary);"><?= htmlspecialchars($st['project_title']) ?></span>
                                </div>
                                <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.2rem;">
                                    <?= htmlspecialchars($st['title']) ?>
                                </h4>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    Due: <?= $st['due_date'] ? date('M j, Y', strtotime($st['due_date'])) : 'No deadline' ?>
                                    <?php if (!empty($st['assigned_name']) && !$isMember): ?>
                                        &bull; Assignee: <?= htmlspecialchars($st['assigned_name']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button class="btn btn-secondary btn-sm" onclick="closeModal('statInProgressModal'); viewTaskDetails(<?= $st['id'] ?>)">
                                Update &rarr;
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('statInProgressModal')">Close</button>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- Modal: Urgent Tasks Drill-Down -->
<!-- ============================================== -->
<div class="modal-backdrop" id="statUrgentModal">
    <div class="modal-dialog" style="max-width: 640px;">
        <div class="modal-header">
            <div class="modal-title" style="color: #f87171; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <?= $isMember ? 'My Urgent Deadlines' : 'All Urgent Priority Tasks' ?>
            </div>
            <button class="modal-close" onclick="closeModal('statUrgentModal')">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (empty($statUrgentList)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No pending urgent tasks. Great work!</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($statUrgentList as $st): ?>
                        <div style="background: var(--bg-surface-elevated); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md); padding: 0.85rem 1rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.25rem;">
                                    <span class="badge priority-urgent">URGENT</span>
                                    <span style="font-size: 0.75rem; color: var(--primary);"><?= htmlspecialchars($st['project_title']) ?></span>
                                </div>
                                <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.2rem;">
                                    <?= htmlspecialchars($st['title']) ?>
                                </h4>
                                <div style="font-size: 0.75rem; color: #f87171; font-weight: 600;">
                                    Target Date: <?= $st['due_date'] ? date('M j, Y', strtotime($st['due_date'])) : 'Immediate' ?>
                                    <?php if (!empty($st['assigned_name']) && !$isMember): ?>
                                        <span style="color: var(--text-muted); font-weight: normal;">&bull; Assignee: <?= htmlspecialchars($st['assigned_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button class="btn btn-danger btn-sm" onclick="closeModal('statUrgentModal'); viewTaskDetails(<?= $st['id'] ?>)">
                                Focus &rarr;
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('statUrgentModal')">Close</button>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- Modal: Team Members Progress Overview -->
<!-- ============================================== -->
<div class="modal-backdrop" id="teamProgressModal">
    <div class="modal-dialog" style="max-width: 680px;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Team Members' Progress & Workload
                </h3>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">Live velocity and task resolution rates across all team members.</p>
            </div>
            <button class="modal-close" onclick="closeModal('teamProgressModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($teamProgressList as $tm): 
                    $tmProg = $tm['total_tasks'] > 0 ? round(($tm['completed_tasks'] / $tm['total_tasks']) * 100) : 0;
                    $isSelf = ($tm['id'] == $currentUser['id']);
                ?>
                    <div style="background: var(--bg-surface-elevated); border: 1px solid <?= $isSelf ? 'var(--primary)' : 'var(--border)' ?>; border-radius: var(--radius-md); padding: 1.15rem; transition: var(--transition);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; gap: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div class="avatar avatar-md" style="width: 44px; height: 44px; font-size: 0.95rem;">
                                    <?= getInitials($tm['name']) ?>
                                </div>
                                <div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-main);">
                                            <?= htmlspecialchars($tm['name']) ?>
                                        </h4>
                                        <?php if ($isSelf): ?>
                                            <span class="badge badge-primary" style="font-size: 0.68rem; padding: 0.1rem 0.4rem;">You</span>
                                        <?php endif; ?>
                                        <span class="badge badge-<?= ($tm['role'] === 'admin') ? 'indigo' : (($tm['role'] === 'manager') ? 'purple' : 'teal') ?>" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">
                                            <?= ucfirst($tm['role']) ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                                        <?= htmlspecialchars($tm['title'] ?: 'Team Member') ?>
                                    </div>
                                </div>
                            </div>

                            <div style="text-align: right;">
                                <span style="font-size: 1.25rem; font-weight: 800; color: <?= $tmProg == 100 ? 'var(--success)' : ($tmProg > 0 ? 'var(--primary)' : 'var(--text-muted)') ?>;">
                                    <?= $tmProg ?>%
                                </span>
                                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Completion</div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress-bar-container" style="height: 7px; margin-bottom: 0.85rem;">
                            <div class="progress-bar-fill" style="width: <?= $tmProg ?>%; <?= $tmProg == 100 ? 'background: var(--success);' : '' ?>"></div>
                        </div>

                        <!-- Stats Metrics Strip -->
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--text-secondary); background: rgba(0,0,0,0.25); padding: 0.4rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                            <span>Resolved: <strong style="color: var(--success);"><?= $tm['completed_tasks'] ?></strong></span>
                            <span>In Progress: <strong style="color: var(--warning);"><?= $tm['in_progress_tasks'] ?></strong></span>
                            <span>Total Assigned: <strong style="color: var(--text-main);"><?= $tm['total_tasks'] ?></strong></span>
                            <?php if ($tm['total_tasks'] == 0): ?>
                                <span class="badge badge-gray" style="font-size: 0.7rem;">Available</span>
                            <?php elseif ($tmProg == 100): ?>
                                <span class="badge badge-success" style="font-size: 0.7rem;">Sprint Complete</span>
                            <?php else: ?>
                                <span class="badge badge-indigo" style="font-size: 0.7rem;">Active</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('teamProgressModal')">Close</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
