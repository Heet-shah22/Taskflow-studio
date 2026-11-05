<?php
// project-detail.php - Project Hub with Kanban Board & Table Views
require_once __DIR__ . '/config/init.php';

$projectId = intval($_GET['id'] ?? 0);
if (!$projectId) {
    header('Location: projects.php');
    exit;
}

try {
    $pdo = getDBConnection(true);

    // Fetch Project Details
    $stmt = $pdo->prepare("
        SELECT p.*, u.name AS creator_name, u.email AS creator_email
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();

    if (!$project) {
        setFlashMessage('error', 'Project not found.');
        header('Location: projects.php');
        exit;
    }

    $pageTitle = $project['title'];
    require_once __DIR__ . '/includes/header.php';

    // Fetch Project Members
    $stmtM = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.role, u.title
        FROM project_members pm
        JOIN users u ON pm.user_id = u.id
        WHERE pm.project_id = ?
    ");
    $stmtM->execute([$projectId]);
    $members = $stmtM->fetchAll();

    // Fetch Non-members for add-member dropdown
    $nonMembers = $pdo->prepare("
        SELECT id, name, role FROM users 
        WHERE id NOT IN (SELECT user_id FROM project_members WHERE project_id = ?)
    ");
    $nonMembers->execute([$projectId]);
    $availableUsers = $nonMembers->fetchAll();

    // Fetch Tasks for this project
    $tasksSql = "
        SELECT t.*, u.name AS assigned_name,
            (SELECT COUNT(*) FROM task_comments WHERE task_id = t.id) AS comment_count
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.project_id = ?
    ";
    $tasksParams = [$projectId];
    if ($currentUser['role'] === 'member') {
        $tasksSql .= " AND t.assigned_to = ?";
        $tasksParams[] = $currentUser['id'];
    }
    $tasksSql .= " ORDER BY t.created_at DESC";

    $stmtT = $pdo->prepare($tasksSql);
    $stmtT->execute($tasksParams);
    $allTasks = $stmtT->fetchAll();

    // Group tasks by status for Kanban
    $kanbanTasks = [
        'todo' => [],
        'in_progress' => [],
        'review' => [],
        'done' => []
    ];
    $doneCount = 0;
    foreach ($allTasks as $t) {
        if ($t['status'] === 'done') $doneCount++;
        if (isset($kanbanTasks[$t['status']])) {
            $kanbanTasks[$t['status']][] = $t;
        } else {
            $kanbanTasks['todo'][] = $t;
        }
    }

    $totalTasksCount = count($allTasks);
    $completionRate = $totalTasksCount > 0 ? round(($doneCount / $totalTasksCount) * 100) : 0;
    $currentTab = $_GET['view'] ?? 'board';

} catch (Exception $e) {
    echo "<div class='alert alert-error'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="page-content">
    <!-- Project Banner & Info Header -->
    <div class="card" style="margin-bottom: 2rem; border-top: 3px solid <?= htmlspecialchars($project['color']) ?>;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                    <a href="projects.php" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem;" title="Back to Projects">
                        &larr; Back
                    </a>
                    <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0;">
                        <?= htmlspecialchars($project['title']) ?>
                    </h1>
                    <span class="badge priority-<?= $project['priority'] ?>"><?= ucfirst($project['priority']) ?></span>
                    <span class="badge badge-<?= $project['status'] === 'completed' ? 'success' : ($project['status'] === 'in_progress' ? 'indigo' : 'gray') ?>">
                        <?= ucwords(str_replace('_', ' ', $project['status'])) ?>
                    </span>
                </div>
                <p style="color: var(--text-secondary); max-width: 800px; font-size: 0.95rem; line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($project['description'] ?: 'No description provided.')) ?>
                </p>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button class="btn btn-primary btn-sm" onclick="openModal('newTaskModal')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Add Task</span>
                </button>
                <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'manager'): ?>
                    <button class="btn btn-secondary btn-sm" onclick="openModal('editProjectModal')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        <span>Edit</span>
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="openModal('addMemberModal')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                        <span>Add Member</span>
                    </button>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <form action="actions/project_actions.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this project? All associated tasks will be removed.')" style="margin: 0;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete Project">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Meta statistics row -->
        <div style="display: flex; gap: 2rem; align-items: center; border-top: 1px solid var(--border); padding-top: 1rem; flex-wrap: wrap;">
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Lead Architect</span>
                <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($project['creator_name']) ?></div>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Timeline</span>
                <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);">
                    <?= $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : 'TBD' ?> &ndash; 
                    <?= $project['due_date'] ? date('M j, Y', strtotime($project['due_date'])) : 'TBD' ?>
                </div>
            </div>
            <?php if ($project['budget'] > 0): ?>
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Allocated Budget</span>
                    <div style="font-size: 0.9rem; font-weight: 600; color: var(--success);">₹<?= number_format($project['budget']) ?></div>
                </div>
            <?php endif; ?>
            <div style="flex: 1; min-width: 200px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.35rem; color: var(--text-muted);">
                    <span>SPRINT COMPLETION</span>
                    <span style="font-weight: 700; color: var(--text-main);"><?= $completionRate ?>% (<?= $doneCount ?>/<?= $totalTasksCount ?>)</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: <?= $completionRate ?>%;"></div>
                </div>
            </div>

            <!-- Team Avatars -->
            <div style="display: flex; align-items: center; gap: -8px;">
                <?php foreach (array_slice($members, 0, 5) as $m): ?>
                    <div class="avatar avatar-sm" title="<?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['role']) ?>)" style="margin-left: -6px; border: 2px solid var(--bg-surface);">
                        <?= getInitials($m['name']) ?>
                    </div>
                <?php endforeach; ?>
                <?php if (count($members) > 5): ?>
                    <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 0.5rem;">+<?= count($members) - 5 ?> more</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View Switcher & Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; gap: 0.5rem; background: var(--bg-surface); padding: 0.25rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
            <a href="project-detail.php?id=<?= $projectId ?>&view=board" class="btn btn-sm <?= ($currentTab === 'board') ? 'btn-primary' : 'btn-secondary' ?>" style="border: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="3" width="7" height="12"/></svg>
                <span>Kanban Board</span>
            </a>
            <a href="project-detail.php?id=<?= $projectId ?>&view=list" class="btn btn-sm <?= ($currentTab === 'list') ? 'btn-primary' : 'btn-secondary' ?>" style="border: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                <span>Table View</span>
            </a>
        </div>
        <div style="font-size: 0.88rem; color: var(--text-muted);">
            💡 Tip: Drag and drop cards across columns to update task progress in real time.
        </div>
    </div>

    <?php if ($currentTab === 'board'): ?>
        <!-- Interactive Kanban Board View -->
        <div class="kanban-board">
            
            <!-- Column 1: To Do -->
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

            <!-- Column 2: In Progress -->
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

            <!-- Column 3: In Review -->
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

            <!-- Column 4: Done -->
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
        <!-- List / Table View -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="table-responsive">
                <table class="app-table" id="mainTasksTable">
                    <thead>
                        <tr>
                            <th>Task Title</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assignee</th>
                            <th>Due Date</th>
                            <th>Est. Hours</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allTasks)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No tasks created in this project yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allTasks as $t): ?>
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
                                    <td><?= $t['estimated_hours'] ?> hrs</td>
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

<!-- Modal: Edit Project -->
<div class="modal-backdrop" id="editProjectModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">Edit Project Details</div>
            <button class="modal-close" onclick="closeModal('editProjectModal')">&times;</button>
        </div>
        <form action="actions/project_actions.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($project['title']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($project['description']) ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-control">
                            <option value="low" <?= $project['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                            <option value="medium" <?= $project['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="high" <?= $project['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                            <option value="urgent" <?= $project['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="planning" <?= $project['status'] === 'planning' ? 'selected' : '' ?>>Planning</option>
                            <option value="in_progress" <?= $project['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="on_hold" <?= $project['status'] === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                            <option value="completed" <?= $project['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= $project['start_date'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="<?= $project['due_date'] ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Budget (₹ INR)</label>
                    <input type="number" step="100" name="budget" class="form-control" value="<?= $project['budget'] ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editProjectModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Team Member -->
<div class="modal-backdrop" id="addMemberModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">Add Team Member to Project</div>
            <button class="modal-close" onclick="closeModal('addMemberModal')">&times;</button>
        </div>
        <form action="actions/project_actions.php" method="POST">
            <input type="hidden" name="action" value="add_member">
            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select User</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">Choose user...</option>
                        <?php foreach ($availableUsers as $au): ?>
                            <option value="<?= $au['id'] ?>"><?= htmlspecialchars($au['name']) ?> (<?= ucfirst($au['role']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addMemberModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Member</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
