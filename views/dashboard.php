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

<?php
$instructor_id = $_SESSION['user_id'];

// Stats
$course_count = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE instructor_id = $instructor_id")->fetch_assoc()['total'];
$upload_pdf = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE instructor_id = $instructor_id AND file_path LIKE '%.pdf'")->fetch_assoc()['total'];
$upload_mp4 = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE instructor_id = $instructor_id AND file_path LIKE '%.mp4'")->fetch_assoc()['total'];

// Completion chart data
$completion_data = $conn->query("
    SELECT c.title, COUNT(cp.id) AS completed 
    FROM courses c 
    LEFT JOIN course_progress cp ON cp.course_id = c.id AND cp.status = 'completed'
    WHERE c.instructor_id = $instructor_id 
    GROUP BY c.id
");
?>

<!-- 📊 Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3"><div class="card border-primary"><div class="card-body text-center">
        <h6>Total Courses</h6><p class="fs-4"><?= $course_count ?></p></div></div></div>
    <div class="col-md-3"><div class="card border-info"><div class="card-body text-center">
        <h6>PDFs Uploaded</h6><p class="fs-4"><?= $upload_pdf ?></p></div></div></div>
    <div class="col-md-3"><div class="card border-warning"><div class="card-body text-center">
        <h6>Videos Uploaded</h6><p class="fs-4"><?= $upload_mp4 ?></p></div></div></div>
</div>

<!-- 🔔 Notifications -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <span>🔔 Notifications</span>
        <span id="notif-count" class="badge bg-<?= $unread_count > 0 ? 'danger' : 'secondary' ?>">
            <?= $unread_count ?> Unread
        </span>
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

<!-- 💬 Recent Comments -->
<?php
$recent_comments = $conn->prepare("
    SELECT com.content, com.created_at, u.name, c.title 
    FROM comments com
    JOIN courses c ON com.course_id = c.id 
    JOIN users u ON com.user_id = u.id 
    WHERE c.instructor_id = ?
    ORDER BY com.created_at DESC LIMIT 5
");
$recent_comments->bind_param("i", $instructor_id);
$recent_comments->execute();
$comments_result = $recent_comments->get_result();
?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">💬 Recent Comments on Your Courses</div>
    <ul class="list-group list-group-flush">
        <?php if ($comments_result->num_rows > 0): ?>
            <?php while ($c = $comments_result->fetch_assoc()): ?>
                <li class="list-group-item">
                    <strong><?= htmlspecialchars($c['name']) ?></strong> on 
                    <em><?= htmlspecialchars($c['title']) ?></em><br>
                    <?= htmlspecialchars($c['content']) ?>
                    <small class="text-muted float-end"><?= $c['created_at'] ?></small>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li class="list-group-item">No recent comments yet.</li>
        <?php endif; ?>
    </ul>
</div>

<!-- 📈 Completion Chart -->
<div class="card mb-5">
    <div class="card-header bg-secondary text-white">📈 Course Completion Stats</div>
    <div class="card-body">
        <canvas id="completionChart" height="200"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('completionChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?= implode(',', array_map(fn($r) => "'" . addslashes($r['title']) . "'", $completion_data->fetch_all(MYSQLI_ASSOC))) ?>],
        datasets: [{
            label: 'Completions',
            data: [<?= implode(',', array_map(fn($r) => $r['completed'], $completion_data->fetch_all(MYSQLI_ASSOC))) ?>],
            backgroundColor: '#2ecc71'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php elseif ($role === 'learner'): ?>

        <a href="course-list.php" class="btn btn-primary">📚 Browse Courses</a>



<!-- admin role -->

<?php elseif ($role === 'admin'): ?>
<?php

$search = $_GET['search'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

// Escape for safety
$search = $conn->real_escape_string($search);
$from = $conn->real_escape_string($from);
$to = $conn->real_escape_string($to);

// Build WHERE conditions
$conditions = [];
if ($search) {
    $conditions[] = "(u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR c.title LIKE '%$search%')";
}
if ($from) $conditions[] = "DATE(u.created_at) >= '$from'";
if ($to) $conditions[] = "DATE(u.created_at) <= '$to'";

$where_users = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';
$where_courses = $search ? "WHERE title LIKE '%$search%'" : '';

$today = date('Y-m-d');
$last7 = date('Y-m-d', strtotime('-7 days'));
$search = $_GET['search'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$user_count = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$course_count = $conn->query("SELECT COUNT(*) AS total FROM courses")->fetch_assoc()['total'];
$instructor_count = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'instructor'")->fetch_assoc()['total'];
$learner_count = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'learner'")->fetch_assoc()['total'];
$pending_count = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'instructor' AND is_approved = 0")->fetch_assoc()['total'];

$today_users = $conn->query("SELECT COUNT(*) AS total FROM users WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'];
$week_users = $conn->query("SELECT COUNT(*) AS total FROM users WHERE DATE(created_at) >= '$last7'")->fetch_assoc()['total'];
$today_courses = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'];
$week_courses = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE DATE(created_at) >= '$last7'")->fetch_assoc()['total'];
$today_comments = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'];
$week_comments = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE DATE(created_at) >= '$last7'")->fetch_assoc()['total'];

// Filter queries
$where_user = $search ? "WHERE name LIKE '%$search%' OR email LIKE '%$search%'" : "";
$where_course = $search ? "WHERE title LIKE '%$search%'" : "";

if ($from && $to) {
    $where_user = "WHERE DATE(created_at) BETWEEN '$from' AND '$to'";
    $where_course = "WHERE DATE(created_at) BETWEEN '$from' AND '$to'";
}

$recent_users = $conn->query("SELECT name, email, created_at FROM users $where_user ORDER BY created_at DESC LIMIT 5");
$recent_courses = $conn->query("SELECT title, created_at FROM courses $where_course ORDER BY created_at DESC LIMIT 5");
$recent_comments = $conn->query("SELECT c.content, c.created_at, u.name, co.title 
                                  FROM comments c 
                                  JOIN users u ON c.user_id = u.id 
                                  JOIN courses co ON c.course_id = co.id 
                                  ORDER BY c.created_at DESC LIMIT 5");
$security_logs = $conn->query("SELECT l.action, l.created_at, u.name 
                                FROM logs l 
                                JOIN users u ON l.user_id = u.id 
                                ORDER BY l.created_at DESC LIMIT 5");
?>

<!-- 🔍 Search + Filter -->
<form method="GET" class="row g-2 mb-4 align-items-end">
  <div class="col-md-3">
    <label for="search" class="form-label">🔍 Search</label>
    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="User or course name" value="<?= htmlspecialchars($search) ?>">
  </div>
  <div class="col-md-3">
    <label for="from" class="form-label">📅 Start Date</label>
    <input type="date" name="from" id="from" class="form-control form-control-sm" value="<?= $from ?>">
  </div>
  <div class="col-md-3">
    <label for="to" class="form-label">📅 End Date</label>
    <input type="date" name="to" id="to" class="form-control form-control-sm" value="<?= $to ?>">
  </div>
  <div class="col-md-3">
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-sm btn-outline-primary w-50">Apply</button>
      <a href="dashboard.php" class="btn btn-sm btn-outline-secondary w-50">Clear</a>
    </div>
  </div>
</form>



<!-- 📊 Stat Cards -->
<div class="row">
  <?php foreach ([
    ['title' => 'Total Users', 'count' => $user_count, 'class' => 'primary'],
    ['title' => 'Total Courses', 'count' => $course_count, 'class' => 'success'],
    ['title' => 'Instructors', 'count' => $instructor_count, 'class' => 'info'],
    ['title' => 'Pending Approvals', 'count' => $pending_count, 'class' => 'danger']
  ] as $stat): ?>
    <div class="col-md-3">
      <div class="card border-<?= $stat['class'] ?>"><div class="card-body">
        <h5><?= $stat['title'] ?></h5>
        <p class="fs-4"><?= $stat['count'] ?></p>
      </div></div>
    </div>
  <?php endforeach; ?>
</div>

<!-- 🗓 Today & Week -->
<div class="row mt-4">
  <div class="col-md-6">
    <div class="card border-success">
      <div class="card-header bg-success text-white">📅 Today</div>
      <div class="card-body">
        <p>👤 New Users: <strong><?= $today_users ?></strong></p>
        <p>📘 New Courses: <strong><?= $today_courses ?></strong></p>
        <p>💬 New Comments: <strong><?= $today_comments ?></strong></p>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card border-info">
      <div class="card-header bg-info text-white">🗓 Last 7 Days</div>
      <div class="card-body">
        <p>👤 Users: <strong><?= $week_users ?></strong></p>
        <p>📘 Courses: <strong><?= $week_courses ?></strong></p>
        <p>💬 Comments: <strong><?= $week_comments ?></strong></p>
      </div>
    </div>
  </div>
</div>

<!-- 📈 Chart & ⚡ Quick Actions -->
<div class="row mt-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-secondary text-white">📊 User & Course Chart</div>
      <div class="card-body"><canvas id="adminChart" height="200"></canvas></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-dark text-white">⚡ Quick Actions</div>
      <div class="card-body d-grid gap-2">
        <a href="../admin/manage-users.php" class="btn btn-outline-primary">🔐 Manage Users</a>
        <a href="course-list.php?admin=1" class="btn btn-outline-success">📚 View All Courses</a>
        <a href="../auth/register.php" class="btn btn-outline-secondary">➕ Register New User</a>
        <form method="POST" action="../admin/export-users.php"><button class="btn btn-outline-dark">⬇️ Export Users CSV</button></form>
        <form method="POST" action="../admin/export-courses.php"><button class="btn btn-outline-dark">⬇️ Export Courses CSV</button></form>
      </div>
    </div>
  </div>
</div>

<!-- 🧑‍🤝‍🧑 & 📘 Recent -->
<div class="row mt-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-dark text-white">🧑‍🤝‍🧑 Recent Users</div>
      <ul class="list-group list-group-flush">
        <?php while ($u = $recent_users->fetch_assoc()): ?>
          <li class="list-group-item">
            <?= htmlspecialchars($u['name']) ?> - <?= htmlspecialchars($u['email']) ?>
            <small class="text-muted float-end"><?= $u['created_at'] ?></small>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-primary text-white">📘 Recent Courses</div>
      <ul class="list-group list-group-flush">
        <?php while ($c = $recent_courses->fetch_assoc()): ?>
          <li class="list-group-item">
            <?= htmlspecialchars($c['title']) ?>
            <small class="text-muted float-end"><?= $c['created_at'] ?></small>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>
  </div>
</div>

<!-- 💬 Comments -->
<div class="card mt-4">
  <div class="card-header bg-info text-white">💬 Recent Comments</div>
  <ul class="list-group list-group-flush">
    <?php while ($com = $recent_comments->fetch_assoc()): ?>
      <li class="list-group-item">
        <?= htmlspecialchars($com['name']) ?> on <strong><?= htmlspecialchars($com['title']) ?></strong><br>
        <em><?= htmlspecialchars($com['content']) ?></em>
        <small class="text-muted float-end"><?= $com['created_at'] ?></small>
      </li>
    <?php endwhile; ?>
  </ul>
</div>

<!-- 🔐 Logs -->
<div class="card mt-4 mb-5">
  <div class="card-header bg-dark text-white">🔐 Security Logs</div>
  <ul class="list-group list-group-flush">
    <?php while ($log = $security_logs->fetch_assoc()): ?>
      <li class="list-group-item">
        <?= htmlspecialchars($log['name']) ?> - <?= $log['action'] ?>
        <small class="text-muted float-end"><?= $log['created_at'] ?></small>
      </li>
    <?php endwhile; ?>
  </ul>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('adminChart').getContext('2d');
new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: ['Learners', 'Instructors', 'Courses'],
    datasets: [{
      data: [<?= $learner_count ?>, <?= $instructor_count ?>, <?= $course_count ?>],
      backgroundColor: ['#3498db', '#9b59b6', '#f1c40f'],
    }]
  }
});
</script>
<?php endif; ?>




</div>
</body>
</html>
