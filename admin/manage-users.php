<?php
session_start();
include '../db-config.php';
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Approve instructor
if (isset($_GET['approve'])) {
    $id = (int) $_GET['approve'];
    $stmt = $conn->prepare("UPDATE users SET is_approved = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['message'] = "✅ Instructor approved.";
    header("Location: manage-users.php");
    exit;
}

// Delete user
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $getUser = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $getUser->bind_param("i", $id);
    $getUser->execute();
    $user = $getUser->get_result()->fetch_assoc();

    if ($user) {
        $adminId = $_SESSION['user_id'];
        $action = "Deleted user: \"{$user['name']}\" ({$user['email']}, ID: $id)";
        $logStmt = $conn->prepare("INSERT INTO logs (user_id, action, user_name, user_email, created_at) VALUES (?, ?, ?, ?, NOW())");
        $logStmt->bind_param("isss", $adminId, $action, $user['name'], $user['email']);
        $logStmt->execute();

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

$result = $conn->query("SELECT id, name, email, role, is_approved FROM users ORDER BY id DESC");
$userCount = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .role-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.6em;
            border-radius: 1rem;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">👥 Manage Users <span class="badge bg-secondary"><?= $userCount ?></span></h2>
        <a href="../views/dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
    </div>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="table-responsive shadow rounded">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
            <tr>
                <th>#ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($user = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <?php
                        $roleClass = match ($user['role']) {
                            'admin' => 'bg-danger',
                            'instructor' => 'bg-primary',
                            default => 'bg-success'
                        };
                        ?>
                        <span class="badge <?= $roleClass ?> role-badge"><?= ucfirst($user['role']) ?></span>
                    </td>
                    <td>
                        <?php if ($user['role'] === 'instructor'): ?>
                            <span class="badge <?= $user['is_approved'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= $user['is_approved'] ? 'Approved' : 'Pending' ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <?php if ($user['role'] === 'instructor' && !$user['is_approved']): ?>
                                <a href="?approve=<?= $user['id'] ?>" class="btn btn-sm btn-outline-success" title="Approve Instructor">
                                    ✅
                                </a>
                            <?php endif; ?>
                            <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete User"
                               onclick="return confirm('Are you sure you want to delete this user?');">
                                🗑️
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
