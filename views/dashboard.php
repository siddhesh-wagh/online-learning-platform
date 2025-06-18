<?php
include '../includes/auth.php';
include '../db-config.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'];
$unread_count = 0;
$notifs = [];

if ($role === 'instructor') {
    $uid = $_SESSION['user_id'];

    // Fetch notifications
    $notif_stmt = $conn->prepare("SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $notif_stmt->bind_param("i", $uid);
    $notif_stmt->execute();
    $notifs = $notif_stmt->get_result();

    // Count unread
    $unread_stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->bind_param("i", $uid);
    $unread_stmt->execute();
    $unread_result = $unread_stmt->get_result()->fetch_assoc();
    $unread_count = $unread_result['unread'];

    // Mark all as read
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['mark_read'])) {
        $mark_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $mark_stmt->bind_param("i", $uid);
        $mark_stmt->execute();
        header("Location: dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <meta charset="UTF-8" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if ($role === 'instructor'): ?>
    <script>
      function refreshNotifCount() {
        fetch("../includes/get-notif-count.php")
          .then(res => res.text())
          .then(count => {
            const badge = document.getElementById("notif-count");
            badge.innerText = count;
            badge.classList.remove("bg-danger", "bg-secondary");
            badge.classList.add(count > 0 ? "bg-danger" : "bg-secondary");
          });
      }
      window.onload = refreshNotifCount;
      setInterval(refreshNotifCount, 5000);
    </script>
    <?php endif; ?>
</head>

<body class="bg-light">
<div class="container py-4">

    <h2>Welcome, <?= htmlspecialchars($name); ?>!</h2>
    <p><strong>Your role:</strong>
        <span class="badge bg-<?= $role === 'admin' ? 'danger' : ($role === 'instructor' ? 'primary' : 'secondary'); ?>">
            <?= htmlspecialchars($role); ?>
        </span>
    </p>

    <nav class="mb-4">
        <a href="../auth/logout.php" class="btn btn-sm btn-danger">Logout</a>
        <a href="user-profile.php" class="btn btn-sm btn-secondary">👤 My Profile</a>
        <?php if ($role === 'admin'): ?>
            <a href="../admin/manage-users.php" class="btn btn-sm btn-dark">🔐 Manage Users</a>
        <?php endif; ?>
    </nav>

    <?php if ($role === 'instructor'): ?>
        <a href="add-course.php" class="btn btn-primary mb-3">➕ Add New Course</a>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>🔔 Notifications</span>
                <span id="notif-count" class="badge bg-secondary"><?= $unread_count ?></span>
            </div>
            <ul class="list-group list-group-flush">
                <?php if ($notifs && $notifs->num_rows > 0): ?>
                    <?php while ($n = $notifs->fetch_assoc()): ?>
                        <li class="list-group-item <?= $n['is_read'] ? '' : 'fw-bold'; ?>">
                            <?= htmlspecialchars($n['message']); ?>
                            <small class="text-muted float-end"><?= $n['created_at']; ?></small>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="list-group-item">No notifications yet.</li>
                <?php endif; ?>
            </ul>
            <form method="POST" class="p-3 text-end">
                <button name="mark_read" class="btn btn-sm btn-outline-secondary">Mark all as read</button>
            </form>
        </div>

    <?php elseif ($role === 'learner'): ?>
        <a href="course-list.php" class="btn btn-primary">📚 Browse Courses</a>

    <?php elseif ($role === 'admin'): ?>
        <?php
        $user_count = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
        $course_count = $conn->query("SELECT COUNT(*) AS total FROM courses")->fetch_assoc()['total'];
        $instructor_count = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'instructor'")->fetch_assoc()['total'];
        $pending_count = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'instructor' AND is_approved = 0")->fetch_assoc()['total'];
        ?>
        <div class="row mt-3">
            <div class="col-md-3"><div class="card border-primary"><div class="card-body">
                <h5>Total Users</h5><p class="fs-4"><?= $user_count ?></p></div></div></div>
            <div class="col-md-3"><div class="card border-success"><div class="card-body">
                <h5>Total Courses</h5><p class="fs-4"><?= $course_count ?></p></div></div></div>
            <div class="col-md-3"><div class="card border-info"><div class="card-body">
                <h5>Instructors</h5><p class="fs-4"><?= $instructor_count ?></p></div></div></div>
            <div class="col-md-3"><div class="card border-danger"><div class="card-body">
                <h5>Pending Approvals</h5><p class="fs-4 text-danger"><?= $pending_count ?></p></div></div></div>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
