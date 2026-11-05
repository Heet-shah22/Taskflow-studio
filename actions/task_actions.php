<?php
// actions/task_actions.php - Task Management CRUD & AJAX Engine
require_once __DIR__ . '/../config/init.php';

$action = $_REQUEST['action'] ?? '';
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (isset($_POST['action']) && $_POST['action'] === 'update_status' && empty($_POST['csrf_token']));

try {
    $pdo = getDBConnection(true);

    // GET TASK DETAILS (AJAX)
    if ($action === 'get_task') {
        header('Content-Type: application/json');
        $taskId = intval($_GET['task_id'] ?? 0);

        if (!$taskId) {
            echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT t.*, p.title AS project_title, u.name AS assigned_name, u.role AS assigned_role, creator.name AS creator_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN users creator ON t.created_by = creator.id
            WHERE t.id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();

        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Task not found']);
            exit;
        }

        $currUser = getCurrentUser();
        if ($currUser && $currUser['role'] === 'member' && $task['assigned_to'] != $currUser['id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied: You can only view tasks assigned to you.']);
            exit;
        }

        // Fetch comments
        $stmtC = $pdo->prepare("
            SELECT c.*, u.name AS user_name, u.role AS user_role
            FROM task_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.task_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmtC->execute([$taskId]);
        $comments = $stmtC->fetchAll();

        foreach ($comments as &$c) {
            $c['created_ago'] = timeAgo($c['created_at']);
            $c['initials'] = getInitials($c['user_name']);
        }

        echo json_encode([
            'success' => true,
            'task' => $task,
            'comments' => $comments
        ]);
        exit;
    }

    // CREATE TASK
    if ($action === 'create') {
        requireAuth();
        $user = getCurrentUser();

        $title = sanitize($_POST['title'] ?? '');
        $projectId = intval($_POST['project_id'] ?? 0);
        $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high', 'urgent']) ? $_POST['priority'] : 'medium';
        $status = in_array($_POST['status'] ?? '', ['todo', 'in_progress', 'review', 'done']) ? $_POST['status'] : 'todo';
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $estimatedHours = floatval($_POST['estimated_hours'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');

        if (empty($title) || empty($projectId)) {
            setFlashMessage('error', 'Task title and project are required.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../tasks.php'));
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO tasks (project_id, assigned_to, title, description, status, priority, due_date, estimated_hours, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$projectId, $assignedTo, $title, $description, $status, $priority, $dueDate, $estimatedHours, $user['id']]);
        $taskId = $pdo->lastInsertId();

        logActivity($pdo, $user['id'], 'task_created', "Created task \"$title\"", $projectId, $taskId);

        setFlashMessage('success', "Task \"$title\" created successfully!");
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../tasks.php'));
        exit;
    }

    // UPDATE STATUS (Via AJAX drag & drop or Form submission)
    if ($action === 'update_status') {
        requireAuth();
        $user = getCurrentUser();

        $taskId = intval($_POST['task_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!in_array($status, ['todo', 'in_progress', 'review', 'done'])) {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            setFlashMessage('error', 'Invalid status selected.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../tasks.php'));
            exit;
        }

        // Get task info for logging
        $stmtInfo = $pdo->prepare("SELECT title, project_id, assigned_to FROM tasks WHERE id = ?");
        $stmtInfo->execute([$taskId]);
        $t = $stmtInfo->fetch();

        if ($t) {
            if ($user['role'] === 'member' && $t['assigned_to'] != $user['id']) {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => 'Access denied: You can only update your own tasks.']);
                    exit;
                }
                setFlashMessage('error', 'Access denied: You can only update your own tasks.');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../tasks.php'));
                exit;
            }

            $stmt = $pdo->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $taskId]);

            $statusLabel = ucwords(str_replace('_', ' ', $status));
            logActivity($pdo, $user['id'], 'status_change', "Changed status of \"{$t['title']}\" to $statusLabel", $t['project_id'], $taskId);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Status updated']);
                exit;
            }

            setFlashMessage('success', "Task status updated to $statusLabel.");
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../tasks.php'));
        exit;
    }

    // ADD COMMENT
    if ($action === 'add_comment') {
        requireAuth();
        $user = getCurrentUser();

        $taskId = intval($_POST['task_id'] ?? 0);
        $comment = sanitize($_POST['comment'] ?? '');

        if (!$taskId || empty($comment)) {
            setFlashMessage('error', 'Comment cannot be empty.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../tasks.php'));
            exit;
        }

        // Get project id
        $stmtT = $pdo->prepare("SELECT title, project_id FROM tasks WHERE id = ?");
        $stmtT->execute([$taskId]);
        $task = $stmtT->fetch();

        if ($task) {
            $stmt = $pdo->prepare("INSERT INTO task_comments (task_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$taskId, $user['id'], $comment]);

            logActivity($pdo, $user['id'], 'added_comment', "Commented on \"{$task['title']}\"", $task['project_id'], $taskId);
            setFlashMessage('success', 'Comment added.');
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../tasks.php'));
        exit;
    }

    // DELETE TASK
    if ($action === 'delete') {
        requireAuth();
        $user = getCurrentUser();
        $taskId = intval($_POST['task_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT title, project_id, created_by FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();

        if ($task) {
            // Check permission: admin, manager or creator
            if ($user['role'] === 'admin' || $user['role'] === 'manager' || $task['created_by'] == $user['id']) {
                $del = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                $del->execute([$taskId]);
                logActivity($pdo, $user['id'], 'task_deleted', "Deleted task \"{$task['title']}\"", $task['project_id']);
                setFlashMessage('success', 'Task removed.');
            } else {
                setFlashMessage('error', 'You do not have permission to delete this task.');
            }
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../tasks.php'));
        exit;
    }

} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
    setFlashMessage('error', 'Operation failed: ' . $e->getMessage());
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../tasks.php'));
    exit;
}
