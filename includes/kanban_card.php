<?php
// includes/kanban_card.php - Reusable Kanban Task Card Component
$isOverdue = (!empty($task['due_date']) && strtotime($task['due_date']) < strtotime('today') && $task['status'] !== 'done');
?>
<div class="task-card" draggable="true" data-task-id="<?= $task['id'] ?>" data-status="<?= $task['status'] ?>">
    <div class="task-card-header">
        <span class="badge priority-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span>
        <?php if (!empty($task['project_title'])): ?>
            <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 500; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; max-width: 130px;">
                <?= htmlspecialchars($task['project_title']) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="task-card-title" onclick="viewTaskDetails(<?= $task['id'] ?>)">
        <?= htmlspecialchars($task['title']) ?>
    </div>

    <div class="task-card-footer">
        <div style="display: flex; align-items: center; gap: 0.35rem;" title="<?= htmlspecialchars($task['assigned_name'] ?: 'Unassigned') ?>">
            <div class="avatar avatar-sm"><?= getInitials($task['assigned_name'] ?: 'U') ?></div>
            <span style="font-size: 0.75rem; color: var(--text-secondary); max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <?= htmlspecialchars($task['assigned_name'] ?: 'Unassigned') ?>
            </span>
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <?php if (!empty($task['comment_count']) && $task['comment_count'] > 0): ?>
                <span style="font-size: 0.72rem; color: var(--text-muted); display: flex; align-items: center; gap: 2px;">
                    💬 <?= $task['comment_count'] ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($task['due_date'])): ?>
                <span style="font-size: 0.72rem; font-weight: 600; color: <?= $isOverdue ? '#f87171' : 'var(--text-muted)' ?>;" title="<?= $isOverdue ? 'Overdue!' : 'Due date' ?>">
                    <?= date('M j', strtotime($task['due_date'])) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>
