<?php
// includes/sidebar.php - Navigation Sidebar
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <div class="brand-badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                <polyline points="2 17 12 22 22 17"/>
                <polyline points="2 12 12 17 22 12"/>
            </svg>
        </div>
        <div class="brand-title">TaskFlow Studio</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Workspace</div>
        <a href="index.php" class="nav-link <?= ($currentPage === 'index') ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>Dashboard</span>
        </a>
        <a href="projects.php" class="nav-link <?= ($currentPage === 'projects' || $currentPage === 'project-detail') ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <span>Projects</span>
        </a>
        <a href="tasks.php" class="nav-link <?= ($currentPage === 'tasks') ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            <span>My Tasks & Board</span>
        </a>

        <div class="nav-section-title">Insights</div>
        <a href="activity.php" class="nav-link <?= ($currentPage === 'activity') ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <span>Activity Log</span>
        </a>

        <div class="nav-section-title">Administration</div>
        <a href="users.php" class="nav-link <?= ($currentPage === 'users') ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Team Members</span>
        </a>
        <a href="install.php" class="nav-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            <span>Database Setup</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-quick-profile">
            <a href="profile.php" style="display: flex; align-items: center; gap: 0.75rem; flex: 1; overflow: hidden; text-decoration: none;">
                <div class="avatar"><?= getInitials($currentUser['name'] ?? 'User') ?></div>
                <div class="user-quick-info">
                    <div class="name"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></div>
                    <div class="role"><?= htmlspecialchars($currentUser['role'] ?? 'Member') ?></div>
                </div>
            </a>
            <a href="logout.php" title="Sign out" style="color: var(--text-muted); padding: 0.25rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </a>
        </div>
    </div>
</aside>
