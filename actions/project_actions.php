<?php
// actions/project_actions.php - Project Management Actions
require_once __DIR__ . '/../config/init.php';

requireAuth();
$currentUser = getCurrentUser();
$action = $_REQUEST['action'] ?? '';

try {
    $pdo = getDBConnection(true);

    if ($action === 'create') {
        requireRole(['admin', 'manager']);

        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['planning', 'in_progress', 'completed', 'on_hold']) ? $_POST['status'] : 'in_progress';
        $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high', 'urgent']) ? $_POST['priority'] : 'medium';
        $color = sanitize($_POST['color'] ?? '#6366f1');
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $budget = floatval($_POST['budget'] ?? 0);

        if (empty($title)) {
            setFlashMessage('error', 'Project title is required.');
            header('Location: ../projects.php');
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO projects (title, description, status, priority, color, start_date, due_date, budget, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$title, $description, $status, $priority, $color, $startDate, $dueDate, $budget, $currentUser['id']]);
        $projectId = $pdo->lastInsertId();

        // Automatically add creator to project members
        $memberStmt = $pdo->prepare("INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)");
        $memberStmt->execute([$projectId, $currentUser['id']]);

        logActivity($pdo, $currentUser['id'], 'project_created', "Created project \"$title\"", $projectId);

        setFlashMessage('success', "Project \"$title\" created successfully!");
        header("Location: ../project-detail.php?id=$projectId");
        exit;
    }

    if ($action === 'update') {
        requireRole(['admin', 'manager']);

        $projectId = intval($_POST['project_id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['planning', 'in_progress', 'completed', 'on_hold']) ? $_POST['status'] : 'in_progress';
        $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high', 'urgent']) ? $_POST['priority'] : 'medium';
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $budget = floatval($_POST['budget'] ?? 0);

        if (!$projectId || empty($title)) {
            setFlashMessage('error', 'Project title is required.');
            header("Location: ../project-detail.php?id=$projectId");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE projects 
            SET title = ?, description = ?, status = ?, priority = ?, start_date = ?, due_date = ?, budget = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $status, $priority, $startDate, $dueDate, $budget, $projectId]);

        logActivity($pdo, $currentUser['id'], 'project_updated', "Updated details for project \"$title\"", $projectId);

        setFlashMessage('success', "Project updated successfully.");
        header("Location: ../project-detail.php?id=$projectId");
        exit;
    }

    if ($action === 'delete') {
        requireRole(['admin']);

        $projectId = intval($_POST['project_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT title FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $proj = $stmt->fetch();

        if ($proj) {
            $del = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $del->execute([$projectId]);

            logActivity($pdo, $currentUser['id'], 'project_deleted', "Deleted project \"{$proj['title']}\" and associated tasks");
            setFlashMessage('success', "Project \"{$proj['title']}\" deleted.");
        }

        header('Location: ../projects.php');
        exit;
    }

    if ($action === 'add_member') {
        $projectId = intval($_POST['project_id'] ?? 0);
        $userId = intval($_POST['user_id'] ?? 0);

        if ($projectId && $userId) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)");
            $stmt->execute([$projectId, $userId]);
            setFlashMessage('success', "Team member added to project.");
        }

        header("Location: ../project-detail.php?id=$projectId");
        exit;
    }

} catch (Exception $e) {
    setFlashMessage('error', 'Action failed: ' . $e->getMessage());
    header('Location: ../projects.php');
    exit;
}
