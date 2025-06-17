<?php
session_start();
include '../db-config.php';

// ✅ Optional debug line (remove after testing)
// echo "Logged in as: " . ($_SESSION['role'] ?? 'none');

if (!isset($_SESSION['user_id'])) {
    // Not logged in at all
    header("Location: ../auth/login.php");
    exit;
}

// ✅ Restrict to admin role
if ($_SESSION['role'] !== 'admin') {
    echo "Access denied.";
    exit;
}

// ✅ Approve instructor
if (isset($_GET['approve'])) {
    $id = (int) $_GET['approve'];
    $stmt = $conn->prepare("UPDATE users SET is_approved = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage-users.php");
    exit;
}

// ✅ Delete user
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
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
</body>
</html>
