<?php
// includes/navbar.php - Top Bar Navigation and Actions
?>
<header class="app-topbar">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <button class="mobile-toggle" id="mobileSidebarToggle" aria-label="Toggle menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <div class="topbar-search">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" placeholder="Search tasks, projects..." data-table-search="mainTasksTable">
        </div>
    </div>

    <div class="topbar-actions">
        <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'manager'): ?>
            <button class="btn btn-secondary btn-sm" onclick="openModal('newProjectModal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>New Project</span>
            </button>
        <?php endif; ?>

        <button class="btn btn-primary btn-sm" onclick="openModal('newTaskModal')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>New Task</span>
        </button>

        <div style="display: flex; align-items: center; gap: 0.5rem; border-left: 1px solid var(--border); padding-left: 1rem;">
            <span class="badge badge-<?= $currentUser['role'] === 'admin' ? 'indigo' : ($currentUser['role'] === 'manager' ? 'purple' : 'teal') ?>">
                <?= ucfirst(htmlspecialchars($currentUser['role'])) ?>
            </span>
        </div>
    </div>
</header>
