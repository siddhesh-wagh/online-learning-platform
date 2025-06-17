<?php include '../includes/auth.php'; ?>
<?php include '../db-config.php'; ?>

<?php
// Fetch notifications if user is an instructor
$unread_count = 0;
$notifs = [];

if ($_SESSION['role'] === 'instructor') {
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

    // Handle "mark as read"
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
    <p>Your role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>

    <nav class="mb-3">
        <a href="../auth/logout.php" class="btn btn-sm btn-danger">Logout</a>
        <a href="user-profile.php" class="btn btn-sm btn-secondary">👤 My Profile</a>
    </nav>

    <?php if ($_SESSION['role'] === 'instructor'): ?>
        <a href="add-course.php" class="btn btn-primary mb-3">➕ Add New Course</a>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>🔔 Notifications</span>
                <span class="badge bg-<?php echo $unread_count > 0 ? 'danger' : 'secondary'; ?>">
                    <?php echo $unread_count; ?> Unread
                </span>
            </div>
            <ul class="list-group list-group-flush">
                <?php if ($notifs && $notifs->num_rows > 0): ?>
                    <?php while ($n = $notifs->fetch_assoc()): ?>
                        <li class="list-group-item <?php echo $n['is_read'] ? '' : 'fw-bold'; ?>">
                            <?php echo htmlspecialchars($n['message']); ?>
                            <small class="text-muted float-end"><?php echo $n['created_at']; ?></small>
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

    <?php elseif ($_SESSION['role'] === 'learner'): ?>
        <a href="course-list.php" class="btn btn-primary">📚 Browse Courses</a>
    <?php endif; ?>
</div>

</body>
</html>
