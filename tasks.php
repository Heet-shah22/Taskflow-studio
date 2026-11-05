<?php
// tasks.php - Global Task Directory and Kanban Workspace
$pageTitle = 'Task Board & Backlog';
require_once __DIR__ . '/includes/header.php';

try {
    $pdo = getDBConnection(true);

    // Filters
    $filterProject = intval($_GET['project_id'] ?? 0);
    $filterStatus = $_GET['status'] ?? 'all';
    $filterPriority = $_GET['priority'] ?? 'all';
    $filterAssignee = $_GET['assignee'] ?? 'all';
    $viewMode = $_GET['view'] ?? 'board';

    // Base query
    $sql = "
        SELECT t.*, p.title AS project_title, p.color AS project_color, u.name AS assigned_name,
            (SELECT COUNT(*) FROM task_comments WHERE task_id = t.id) AS comment_count
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE 1=1
    ";
    $params = [];

    if ($filterProject > 0) {
        $sql .= " AND t.project_id = ?";
        $params[] = $filterProject;
    }
    if ($filterStatus !== 'all' && in_array($filterStatus, ['todo', 'in_progress', 'review', 'done'])) {
        $sql .= " AND t.status = ?";
        $params[] = $filterStatus;
    }
    if ($filterPriority !== 'all' && in_array($filterPriority, ['low', 'medium', 'high', 'urgent'])) {
        $sql .= " AND t.priority = ?";
        $params[] = $filterPriority;
    }
    if ($currentUser['role'] === 'member') {
        $sql .= " AND t.assigned_to = ?";
        $params[] = $currentUser['id'];
    } else {
        if ($filterAssignee === 'mine') {
            $sql .= " AND t.assigned_to = ?";
            $params[] = $currentUser['id'];
        } elseif ($filterAssignee === 'unassigned') {
            $sql .= " AND t.assigned_to IS NULL";
        } elseif (is_numeric($filterAssignee) && intval($filterAssignee) > 0) {
            $sql .= " AND t.assigned_to = ?";
            $params[] = intval($filterAssignee);
        }
    }

    $sql .= " ORDER BY 
        CASE WHEN t.priority = 'urgent' THEN 1 WHEN t.priority = 'high' THEN 2 WHEN t.priority = 'medium' THEN 3 ELSE 4 END,
        t.due_date ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();

    // Group tasks for Kanban
    $kanbanTasks = ['todo' => [], 'in_progress' => [], 'review' => [], 'done' => []];
    foreach ($tasks as $t) {
        if (isset($kanbanTasks[$t['status']])) {
            $kanbanTasks[$t['status']][] = $t;
        } else {
            $kanbanTasks['todo'][] = $t;
        }
    }

    // Projects and Users for filters
    $allProjects = $pdo->query("SELECT id, title FROM projects ORDER BY title ASC")->fetchAll();
    $allUsers = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll();

} catch (Exception $e) {
    echo "<div class='alert alert-error'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="page-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Tasks & Backlog</h1>
            <p class="page-subtitle">Unified backlog across all active projects. Filter by assignee, priority, or status.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button class="btn btn-primary" onclick="openModal('newTaskModal')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>New Task</span>
            </button>
        </div>
    </div>

    <!-- Filters Strip -->
    <div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
        <form method="GET" action="tasks.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">

            <div style="min-width: 180px;">
                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase;">Project</label>
                <select name="project_id" class="form-control" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;" onchange="this.form.submit()">
                    <option value="0">All Projects</option>
                    <?php foreach ($allProjects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($filterProject == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($currentUser['role'] !== 'member'): ?>
                <div style="min-width: 150px;">
                    <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase;">Assignee</label>
                    <select name="assignee" class="form-control" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;" onchange="this.form.submit()">
                        <option value="all">Everyone</option>
                        <option value="mine" <?= ($filterAssignee === 'mine') ? 'selected' : '' ?>>Assigned to Me</option>
                        <option value="unassigned" <?= ($filterAssignee === 'unassigned') ? 'selected' : '' ?>>Unassigned</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filterAssignee == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div style="min-width: 140px;">
                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase;">Priority</label>
                <select name="priority" class="form-control" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;" onchange="this.form.submit()">
                    <option value="all">All Priorities</option>
                    <option value="urgent" <?= ($filterPriority === 'urgent') ? 'selected' : '' ?>>Urgent</option>
                    <option value="high" <?= ($filterPriority === 'high') ? 'selected' : '' ?>>High</option>
                    <option value="medium" <?= ($filterPriority === 'medium') ? 'selected' : '' ?>>Medium</option>
                    <option value="low" <?= ($filterPriority === 'low') ? 'selected' : '' ?>>Low</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem; margin-left: auto;">
                <a href="tasks.php" class="btn btn-secondary btn-sm" style="height: 38px;">Reset Filters</a>
                <div style="display: flex; background: var(--bg-input); padding: 0.2rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <a href="tasks.php?<?= http_build_query(array_merge($_GET, ['view' => 'board'])) ?>" class="btn btn-sm <?= ($viewMode === 'board') ? 'btn-primary' : 'btn-secondary' ?>" style="border: none;">Board</a>
                    <a href="tasks.php?<?= http_build_query(array_merge($_GET, ['view' => 'list'])) ?>" class="btn btn-sm <?= ($viewMode === 'list') ? 'btn-primary' : 'btn-secondary' ?>" style="border: none;">List</a>
                </div>
            </div>
        </form>
    </div>

    <?php if ($viewMode === 'board'): ?>
        <!-- Global Kanban Board -->
        <div class="kanban-board">
            <div class="kanban-column">
                <div class="kanban-col-header">
                    <div class="kanban-col-title">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #94a3b8;"></span>
                        <span>To Do</span>
                    </div>
                    <span class="kanban-badge"><?= count($kanbanTasks['todo']) ?></span>
                </div>
                <div class="kanban-cards-wrapper" data-status="todo">
                    <?php foreach ($kanbanTasks['todo'] as $task): ?>
                        <?php include __DIR__ . '/includes/kanban_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="kanban-column">
                <div class="kanban-col-header">
                    <div class="kanban-col-title">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #6366f1;"></span>
                        <span>In Progress</span>
                    </div>
                    <span class="kanban-badge"><?= count($kanbanTasks['in_progress']) ?></span>
                </div>
                <div class="kanban-cards-wrapper" data-status="in_progress">
                    <?php foreach ($kanbanTasks['in_progress'] as $task): ?>
                        <?php include __DIR__ . '/includes/kanban_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="kanban-column">
                <div class="kanban-col-header">
                    <div class="kanban-col-title">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b;"></span>
                        <span>In Review</span>
                    </div>
                    <span class="kanban-badge"><?= count($kanbanTasks['review']) ?></span>
                </div>
                <div class="kanban-cards-wrapper" data-status="review">
                    <?php foreach ($kanbanTasks['review'] as $task): ?>
                        <?php include __DIR__ . '/includes/kanban_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="kanban-column">
                <div class="kanban-col-header">
                    <div class="kanban-col-title">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                        <span>Completed</span>
                    </div>
                    <span class="kanban-badge"><?= count($kanbanTasks['done']) ?></span>
                </div>
                <div class="kanban-cards-wrapper" data-status="done">
                    <?php foreach ($kanbanTasks['done'] as $task): ?>
                        <?php include __DIR__ . '/includes/kanban_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- List View -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="table-responsive">
                <table class="app-table" id="mainTasksTable">
                    <thead>
                        <tr>
                            <th>Task Title</th>
                            <th>Project</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assignee</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">No tasks match the selected filter criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $t): ?>
                                <tr>
                                    <td>
                                        <a href="javascript:void(0)" onclick="viewTaskDetails(<?= $t['id'] ?>)" style="font-weight: 600; color: var(--text-main);">
                                            <?= htmlspecialchars($t['title']) ?>
                                        </a>
                                        <?php if ($t['comment_count'] > 0): ?>
                                            <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 0.4rem;">💬 <?= $t['comment_count'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="project-detail.php?id=<?= $t['project_id'] ?>" style="color: var(--primary); font-weight: 500;">
                                            <?= htmlspecialchars($t['project_title']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <form action="actions/task_actions.php" method="POST" style="margin: 0;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                                            <select name="status" class="form-control" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; width: auto;" onchange="this.form.submit()">
                                                <option value="todo" <?= ($t['status'] === 'todo') ? 'selected' : '' ?>>To Do</option>
                                                <option value="in_progress" <?= ($t['status'] === 'in_progress') ? 'selected' : '' ?>>In Progress</option>
                                                <option value="review" <?= ($t['status'] === 'review') ? 'selected' : '' ?>>In Review</option>
                                                <option value="done" <?= ($t['status'] === 'done') ? 'selected' : '' ?>>Done</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td><span class="badge priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                                            <div class="avatar avatar-sm"><?= getInitials($t['assigned_name'] ?: 'U') ?></div>
                                            <span><?= htmlspecialchars($t['assigned_name'] ?: 'Unassigned') ?></span>
                                        </div>
                                    </td>
                                    <td><?= $t['due_date'] ? date('M j, Y', strtotime($t['due_date'])) : '&mdash;' ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.35rem;">
                                            <button class="btn btn-secondary btn-sm" onclick="viewTaskDetails(<?= $t['id'] ?>)">View</button>
                                            <form action="actions/task_actions.php" method="POST" onsubmit="return confirm('Delete task?')" style="margin: 0;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">&times;</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
