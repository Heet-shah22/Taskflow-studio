/**
 * TaskFlow Studio - Interactive UI & Kanban Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle
    const mobileToggle = document.getElementById('mobileSidebarToggle');
    const sidebar = document.querySelector('.app-sidebar');
    
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }

    // 2. Generic Modal Open/Close Handlers
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Close modal on backdrop click
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                backdrop.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // 3. Kanban Drag and Drop System
    const taskCards = document.querySelectorAll('.task-card[draggable="true"]');
    const columns = document.querySelectorAll('.kanban-cards-wrapper');

    taskCards.forEach(card => {
        card.addEventListener('dragstart', (e) => {
            card.classList.add('dragging');
            e.dataTransfer.setData('text/plain', card.dataset.taskId);
            e.dataTransfer.effectAllowed = 'move';
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
        });
    });

    columns.forEach(column => {
        column.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            column.style.backgroundColor = 'rgba(99, 102, 241, 0.08)';
        });

        column.addEventListener('dragleave', () => {
            column.style.backgroundColor = '';
        });

        column.addEventListener('drop', async (e) => {
            e.preventDefault();
            column.style.backgroundColor = '';
            const taskId = e.dataTransfer.getData('text/plain');
            const targetStatus = column.dataset.status;
            const draggedCard = document.querySelector(`.task-card[data-task-id="${taskId}"]`);

            if (draggedCard && targetStatus) {
                const currentStatus = draggedCard.dataset.status;
                if (currentStatus === targetStatus) return;

                // Move element in DOM optimistically
                column.appendChild(draggedCard);
                draggedCard.dataset.status = targetStatus;

                // Update counter badges
                updateColumnCounters();

                // Send update to server via AJAX
                try {
                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('task_id', taskId);
                    formData.append('status', targetStatus);

                    const res = await fetch('actions/task_actions.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        showToast('Task status updated to ' + targetStatus.replace('_', ' '), 'success');
                    } else {
                        showToast(data.message || 'Error updating status', 'error');
                    }
                } catch (err) {
                    showToast('Connection error updating task', 'error');
                }
            }
        });
    });

    function updateColumnCounters() {
        document.querySelectorAll('.kanban-column').forEach(col => {
            const countBadge = col.querySelector('.kanban-badge');
            const cards = col.querySelectorAll('.task-card');
            if (countBadge) {
                countBadge.textContent = cards.length;
            }
        });
    }

    // 4. Toast Notifications
    window.showToast = function(message, type = 'info') {
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.style.cssText = `
                position: fixed;
                bottom: 24px;
                right: 24px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                pointer-events: none;
            `;
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `alert alert-${type}`;
        toast.style.cssText = `
            pointer-events: auto;
            min-width: 280px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            animation: toastSlide 0.3s ease-out;
            margin-bottom: 0;
        `;
        toast.innerHTML = `<span>${message}</span>`;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    };

    // 5. Global Table Search Filter
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(input => {
        const tableId = input.dataset.tableSearch;
        const table = document.getElementById(tableId);
        if (!table) return;

        input.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    });

    // 6. View Task Modal Details via AJAX
    window.viewTaskDetails = async function(taskId) {
        try {
            const res = await fetch(`actions/task_actions.php?action=get_task&task_id=${taskId}`);
            const data = await res.json();
            
            if (data.success && data.task) {
                const t = data.task;
                document.getElementById('modalTaskTitle').textContent = t.title;
                document.getElementById('modalTaskProject').textContent = t.project_title || 'General';
                document.getElementById('modalTaskDesc').textContent = t.description || 'No description provided.';
                document.getElementById('modalTaskPriority').textContent = t.priority;
                document.getElementById('modalTaskPriority').className = `badge priority-${t.priority}`;
                document.getElementById('modalTaskStatus').value = t.status;
                document.getElementById('modalTaskId').value = t.id;
                document.getElementById('commentTaskId').value = t.id;
                document.getElementById('modalTaskDueDate').textContent = t.due_date ? t.due_date : 'No deadline';
                document.getElementById('modalTaskAssignee').textContent = t.assigned_name || 'Unassigned';

                // Render comments
                const commentsContainer = document.getElementById('modalTaskCommentsList');
                if (commentsContainer) {
                    if (data.comments && data.comments.length > 0) {
                        commentsContainer.innerHTML = data.comments.map(c => `
                            <div class="comment-card">
                                <div class="comment-header">
                                    <div class="comment-author">
                                        <div class="avatar avatar-sm">${c.initials || 'U'}</div>
                                        <span>${c.user_name}</span>
                                    </div>
                                    <span class="comment-date">${c.created_ago}</span>
                                </div>
                                <div class="comment-text">${c.comment}</div>
                            </div>
                        `).join('');
                    } else {
                        commentsContainer.innerHTML = `<p style="color: var(--text-muted); font-size: 0.85rem;">No comments yet. Start the discussion below!</p>`;
                    }
                }

                openModal('taskDetailModal');
            } else {
                showToast(data.message || 'Could not load task', 'error');
            }
        } catch (err) {
            showToast('Failed to load task details', 'error');
        }
    };
});
