<?php
session_start();
include '../db-config.php';
include_once '../includes/functions.php'; // needed for logAction() if you want to log admin actions

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// ✅ Approve instructor
if (isset($_GET['approve'])) {
    $id = (int) $_GET['approve'];
    $stmt = $conn->prepare("UPDATE users SET is_approved = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['message'] = "✅ Instructor approved.";
    header("Location: manage-users.php");
    exit;
}

// ✅ Delete user with log check
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    // Get user info before deletion
    $getUser = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $getUser->bind_param("i", $id);
    $getUser->execute();
    $userRes = $getUser->get_result();
    $user = $userRes->fetch_assoc();

    if ($user) {
        $name = $user['name'];
        $email = $user['email'];

        // Log deletion BEFORE deleting
        $adminId = $_SESSION['user_id'];
        $action = "Deleted user: \"$name\" ($email, ID: $id)";

        $logStmt = $conn->prepare("INSERT INTO logs (user_id, action, user_name, user_email, created_at)
                                   VALUES (?, ?, ?, ?, NOW())");
        $logStmt->bind_param("isss", $adminId, $action, $name, $email);
        $logStmt->execute();

        // Delete user
        $delStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delStmt->bind_param("i", $id);
        $delStmt->execute();

        $_SESSION['message'] = "✅ User deleted and log recorded.";
    } else {
        $_SESSION['message'] = "❌ User not found.";
    }

    header("Location: manage-users.php");
    exit;
}

// ✅ Fetch all users
$result = $conn->query("SELECT id, name, email, role, is_approved FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2 class="mb-3">👤 Manage Users</h2>
    <a href="../views/dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Approved</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($user = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= $user['role'] ?></td>
                <td><?= $user['role'] === 'instructor' ? ($user['is_approved'] ? '✅' : '❌') : '—' ?></td>
                <td>
                    <?php if ($user['role'] === 'instructor' && !$user['is_approved']): ?>
                        <a href="?approve=<?= $user['id'] ?>" class="btn btn-sm btn-success">Approve</a>
                    <?php endif; ?>
                    <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
