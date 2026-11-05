<?php
// includes/footer.php - Global Modals and Scripts
try {
    $db = getDBConnection(true);
    // Fetch active projects for dropdown
    $modalProjects = $db->query("SELECT id, title FROM projects ORDER BY title ASC")->fetchAll();
    // Fetch team members for dropdown
    $modalUsers = $db->query("SELECT id, name, role FROM users ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    $modalProjects = [];
    $modalUsers = [];
}
?>
        </div> <!-- End .app-main -->
    </div> <!-- End .app-container -->

    <!-- ============================================== -->
    <!-- Modal: New Task -->
    <!-- ============================================== -->
    <div class="modal-backdrop" id="newTaskModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <div class="modal-title">Create New Task</div>
                <button class="modal-close" onclick="closeModal('newTaskModal')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form action="actions/task_actions.php" method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Task Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Design Landing Page Wireframes" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Project *</label>
                            <select name="project_id" class="form-control" required>
                                <option value="">Select Project</option>
                                <?php foreach ($modalProjects as $proj): ?>
                                    <option value="<?= $proj['id'] ?>" <?= (isset($project) && $project['id'] == $proj['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($proj['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assign To</label>
                            <select name="assigned_to" class="form-control">
                                <option value="">Unassigned</option>
                                <?php foreach ($modalUsers as $u): ?>
                                    <option value="<?= $u['id'] ?>">
                                        <?= htmlspecialchars($u['name']) ?> (<?= ucfirst($u['role']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-control">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="todo" selected>To Do</option>
                                <option value="in_progress">In Progress</option>
                                <option value="review">In Review</option>
                                <option value="done">Completed</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estimated Hours</label>
                            <input type="number" step="0.5" name="estimated_hours" class="form-control" placeholder="e.g. 8.0" value="4.0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description & Acceptance Criteria</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Provide background details and technical notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('newTaskModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- Modal: New Project -->
    <!-- ============================================== -->
    <div class="modal-backdrop" id="newProjectModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <div class="modal-title">Create New Project</div>
                <button class="modal-close" onclick="closeModal('newProjectModal')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form action="actions/project_actions.php" method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Project Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Cloud Security Migration" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="High-level project goals and deliverables..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-control">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Initial Status</label>
                            <select name="status" class="form-control">
                                <option value="planning">Planning</option>
                                <option value="in_progress" selected>In Progress</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Target Completion</label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Budget (₹ INR)</label>
                            <input type="number" step="100" name="budget" class="form-control" placeholder="e.g. 25000">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Accent Theme Color</label>
                            <select name="color" class="form-control">
                                <option value="#6366f1" selected>Indigo Glow</option>
                                <option value="#8b5cf6">Purple Nebula</option>
                                <option value="#10b981">Emerald Green</option>
                                <option value="#06b6d4">Cyan Sky</option>
                                <option value="#ec4899">Rose Pink</option>
                                <option value="#f59e0b">Amber Gold</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('newProjectModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Launch Project</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- Modal: Task Detail & Comments Modal -->
    <!-- ============================================== -->
    <div class="modal-backdrop" id="taskDetailModal">
        <div class="modal-dialog" style="max-width: 640px;">
            <div class="modal-header">
                <div>
                    <span id="modalTaskProject" class="badge badge-gray" style="margin-bottom: 0.35rem;">Project</span>
                    <h2 class="modal-title" id="modalTaskTitle">Task Details</h2>
                </div>
                <button class="modal-close" onclick="closeModal('taskDetailModal')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div style="display: flex; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap; background: rgba(0,0,0,0.2); padding: 0.75rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Priority</div>
                        <span id="modalTaskPriority" class="badge">Medium</span>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Assignee</div>
                        <div id="modalTaskAssignee" style="font-size: 0.88rem; font-weight: 600; color: var(--text-main);">Unassigned</div>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Deadline</div>
                        <div id="modalTaskDueDate" style="font-size: 0.88rem; font-weight: 600; color: var(--text-main);">None</div>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Description</div>
                    <p id="modalTaskDesc" style="color: var(--text-secondary); font-size: 0.92rem; line-height: 1.6; background-color: var(--bg-input); padding: 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border);"></p>
                </div>

                <!-- Update Status Inline -->
                <form action="actions/task_actions.php" method="POST" style="margin-bottom: 1.75rem;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="task_id" id="modalTaskId" value="">
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <label class="form-label" style="margin-bottom: 0; white-space: nowrap;">Change Status:</label>
                        <select name="status" id="modalTaskStatus" class="form-control" style="flex: 1;" onchange="this.form.submit()">
                            <option value="todo">To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="review">In Review</option>
                            <option value="done">Completed</option>
                        </select>
                    </div>
                </form>

                <!-- Discussion & Comments -->
                <div style="border-top: 1px solid var(--border); padding-top: 1.25rem;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Discussion & Notes
                    </div>

                    <div class="comments-list" id="modalTaskCommentsList">
                        <!-- Filled by JS -->
                    </div>

                    <form action="actions/task_actions.php" method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="add_comment">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="task_id" id="commentTaskId" value="">
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" name="comment" class="form-control" placeholder="Add a comment or update..." required>
                            <button type="submit" class="btn btn-primary btn-sm">Post</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main JavaScript Bundle -->
    <script src="assets/js/app.js"></script>
</body>
</html>
