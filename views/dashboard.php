<?php
include '../includes/auth.php';
include '../db-config.php';
include '../includes/functions.php'; // ✅ Add this if not already

$role = $_SESSION['role'];
$name = $_SESSION['name'];
$instructor_id = $_SESSION['user_id'];
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
// ✅ Handle instructor replies to comments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    $comment_id = (int) $_POST['comment_id'];
    $course_id = (int) $_POST['course_id'];
    $reply = trim($_POST['reply']);

    if ($reply !== '') {
        $stmt = $conn->prepare("INSERT INTO replies (comment_id, instructor_id, reply) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $comment_id, $instructor_id, $reply);
        $stmt->execute();

        // ✅ Log the reply action via helper
        logAction($instructor_id, "Replied to a comment on course ID $course_id");
    }

    header("Location: dashboard.php?course_id=$course_id");
    exit;
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

$total_enrolled = $conn->query("
  SELECT COUNT(DISTINCT cp.user_id) AS total 
  FROM course_progress cp 
  JOIN courses c ON cp.course_id = c.id 
  WHERE c.instructor_id = $instructor_id
")->fetch_assoc()['total'];

// Courses for dropdown
$courses = $conn->query("SELECT id, title FROM courses WHERE instructor_id = $instructor_id ORDER BY created_at DESC");

// Comments for selected course
$selected_course_id = $_GET['course_id'] ?? '';
$comments_result = null;
if ($selected_course_id && is_numeric($selected_course_id)) {
    $stmt = $conn->prepare("
    SELECT com.id, com.content, com.created_at, u.name 
    FROM comments com
    JOIN users u ON com.user_id = u.id 
    WHERE com.course_id = ? 
    ORDER BY com.created_at DESC
");

    $stmt->bind_param("i", $selected_course_id);
    $stmt->execute();
    $comments_result = $stmt->get_result();
}
?>

<!-- 📊 Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3"><div class="card border-primary"><div class="card-body text-center">
        <h6>Total Courses</h6><p class="fs-4"><?= $course_count ?></p></div></div></div>
    <div class="col-md-3"><div class="card border-info"><div class="card-body text-center">
        <h6>PDFs Uploaded</h6><p class="fs-4"><?= $upload_pdf ?></p></div></div></div>
    <div class="col-md-3"><div class="card border-warning"><div class="card-body text-center">
        <h6>Videos Uploaded</h6><p class="fs-4"><?= $upload_mp4 ?></p></div></div></div>
        <div class="col-md-3">
  <div class="card border-success">
    <div class="card-body text-center">
      <h6>Students Enrolled</h6>
      <p class="fs-4"><?= $total_enrolled ?></p>
    </div>
  </div>
</div>

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

<!-- 💬 Course Comments Viewer -->
<div class="row mb-5">
  <!-- 💬 Comments Section -->
  <div class="col-md-7">
    <div class="card h-100">
      <div class="card-header bg-primary text-white">💬 View & Reply to Comments</div>
      <div class="card-body">
        <form method="GET" class="mb-3">
          <label for="course_id">Select Course:</label>
          <div class="input-group">
            <select name="course_id" id="course_id" class="form-select" onchange="this.form.submit()">
              <option value="">-- Choose a Course --</option>
              <?php mysqli_data_seek($courses, 0); while ($course = $courses->fetch_assoc()): ?>
                <option value="<?= $course['id'] ?>" <?= $selected_course_id == $course['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($course['title']) ?>
                </option>
              <?php endwhile; ?>
            </select>
            <?php if ($selected_course_id): ?>
              <a href="dashboard.php" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
          </div>
        </form>

        <?php if ($selected_course_id): ?>
          <?php if ($comments_result && $comments_result->num_rows > 0): ?>
            <ul class="list-group">
              <?php while ($com = $comments_result->fetch_assoc()): ?>
                <li class="list-group-item">
                  <strong><?= htmlspecialchars($com['name']) ?></strong><br>
                  <?= htmlspecialchars($com['content']) ?>
                  <small class="text-muted float-end"><?= $com['created_at'] ?></small>
                  <br>

                  <?php
                  $comment_id = $com['id'];
                  $reply_stmt = $conn->prepare("SELECT reply, created_at FROM replies WHERE comment_id = ?");
                  $reply_stmt->bind_param("i", $comment_id);
                  $reply_stmt->execute();
                  $reply = $reply_stmt->get_result()->fetch_assoc();
                  ?>

                  <?php if ($reply): ?>
                    <div class="mt-2 p-2 bg-light border rounded small">
                      <strong>You replied:</strong><br>
                      <?= htmlspecialchars($reply['reply']) ?>
                      <small class="text-muted float-end"><?= $reply['created_at'] ?></small>
                    </div>
                  <?php else: ?>
                    <form method="POST" class="mt-2">
                      <input type="hidden" name="comment_id" value="<?= $comment_id ?>">
                      <input type="hidden" name="course_id" value="<?= $selected_course_id ?>">
                      <textarea name="reply" class="form-control form-control-sm mb-2" rows="2" placeholder="Write a reply..."></textarea>
                      <button type="submit" name="submit_reply" class="btn btn-sm btn-primary">Reply</button>
                    </form>
                  <?php endif; ?>
                </li>
              <?php endwhile; ?>
            </ul>
          <?php else: ?>
            <p class="text-muted">No comments yet for this course.</p>
          <?php endif; ?>
        <?php else: ?>
          <p class="text-muted">Select a course above to view comments.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- 👥 Enrolled Students Section -->
  <div class="col-md-5">
    <div class="card h-100">
      <div class="card-header bg-dark text-white">👥 Enrolled Students</div>
      <div class="card-body">
        <?php if ($selected_course_id): ?>
          <?php
          $enroll_stmt = $conn->prepare("SELECT u.name, u.email FROM course_progress cp JOIN users u ON cp.user_id = u.id WHERE cp.course_id = ?");
          $enroll_stmt->bind_param("i", $selected_course_id);
          $enroll_stmt->execute();
          $students_result = $enroll_stmt->get_result();
          $student_count = $students_result->num_rows;
          ?>
          <p><strong>Total Enrolled:</strong> <?= $student_count ?></p>

          <?php if ($student_count > 0): ?>
            <ul class="list-group small">
              <?php while ($stu = $students_result->fetch_assoc()): ?>
                <li class="list-group-item"><?= htmlspecialchars($stu['name']) ?> <small class="text-muted float-end"><?= htmlspecialchars($stu['email']) ?></small></li>
              <?php endwhile; ?>
            </ul>
          <?php else: ?>
            <p class="text-muted">No students enrolled yet.</p>
          <?php endif; ?>
        <?php else: ?>
          <p class="text-muted">Select a course to view enrolled students.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>



<!-- learner -->
<?php elseif ($role === 'learner'): ?>

<?php
$learner_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';

$all_courses = $conn->query("
    SELECT cp.course_id, c.title, cp.status, c.created_at, cp.updated_at 
    FROM course_progress cp 
    JOIN courses c ON cp.course_id = c.id 
    WHERE cp.user_id = $learner_id 
    ORDER BY cp.updated_at DESC
");

$filtered_courses = array_filter(iterator_to_array($all_courses), function ($row) use ($filter) {
    return $filter === 'all' || $row['status'] === $filter;
});

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    elseif ($diff < 3600) return floor($diff / 60) . ' minute' . (floor($diff / 60) === 1 ? '' : 's') . ' ago';
    elseif ($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff / 3600) === 1 ? '' : 's') . ' ago';
    elseif ($diff < 172800) return 'Yesterday';
    else return floor($diff / 86400) . ' day' . (floor($diff / 86400) === 1 ? '' : 's') . ' ago';
}

?>

<div class="mb-4">
  <h3 class="mb-3 fw-bold">👋 Welcome, <?= htmlspecialchars($name) ?>!</h3>

  <div class="btn-group mb-4" role="group">
    <a href="?filter=all" class="btn btn-outline-primary <?= $filter === 'all' ? 'active' : '' ?>">📘 All Enrolled</a>
    <a href="?filter=in_progress" class="btn btn-outline-warning <?= $filter === 'in_progress' ? 'active' : '' ?>">⏳ In Progress</a>
    <a href="?filter=completed" class="btn btn-outline-success <?= $filter === 'completed' ? 'active' : '' ?>">✅ Completed</a>
  </div>

  <?php if (count($filtered_courses) > 0): ?>
    <div class="list-group shadow-sm">
      <?php foreach ($filtered_courses as $course): ?>
        <?php
          $status = $course['status'];
          $progress = ($status === 'completed') ? 100 : 60; // Optional fixed progress
        ?>
        <div class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center">
          <div class="me-md-3">
            <div class="fw-semibold fs-5"><?= htmlspecialchars($course['title']) ?></div>
            <div class="text-muted small">
              Enrolled on <?= date('M d, Y', strtotime($course['created_at'])) ?> · 
              Last accessed <?= timeAgo($course['updated_at']) ?>
            </div>
            <div class="progress mt-2" style="height: 8px;">
              <div class="progress-bar bg-<?= $progress === 100 ? 'success' : 'info' ?>" 
                   style="width: <?= $progress ?>%" role="progressbar" 
                   aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
          <div class="mt-3 mt-md-0 text-md-end">
            <span class="badge bg-<?= $status === 'completed' ? 'success' : 'warning' ?> mb-2">
              <?= ucfirst(str_replace('_', ' ', $status)) ?>
            </span><br>
            <a href="course-view.php?id=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-primary mt-1">▶️ View</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="alert alert-info mt-3">No courses found for this filter.</div>
  <?php endif; ?>

  <div class="mt-4 text-end">
    <a href="course-list.php" class="btn btn-secondary">📚 Browse More Courses</a>
  </div>
</div>



<!-- admin role -->

<?php elseif ($role === 'admin'): ?>
<?php
include '../includes/functions.php';

// Filters
$search = $_GET['search'] ?? '';
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';
$search = $conn->real_escape_string($search);
$from   = $conn->real_escape_string($from);
$to     = $conn->real_escape_string($to);

// SQL conditions
$conditions = [];
if ($search) $conditions[] = "(u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR c.title LIKE '%$search%')";
if ($from)   $conditions[] = "DATE(u.created_at) >= '$from'";
if ($to)     $conditions[] = "DATE(u.created_at) <= '$to'";
$where_users   = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';
$where_courses = $search ? "WHERE title LIKE '%$search%'" : '';

// Stats
$today = date('Y-m-d');
$last7 = date('Y-m-d', strtotime('-7 days'));

$user_count       = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$instructor_count = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'instructor'")->fetch_assoc()['total'];
$learner_count    = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'learner'")->fetch_assoc()['total'];
$pending_count    = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'instructor' AND is_approved = 0")->fetch_assoc()['total'];

$course_count     = $conn->query("SELECT COUNT(*) AS total FROM courses")->fetch_assoc()['total'];
$pdf_count        = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE file_path LIKE '%.pdf'")->fetch_assoc()['total'];
$video_count      = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE file_path LIKE '%.mp4'")->fetch_assoc()['total'];

$today_users      = $conn->query("SELECT COUNT(*) AS total FROM users WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'];
$week_users       = $conn->query("SELECT COUNT(*) AS total FROM users WHERE DATE(created_at) >= '$last7'")->fetch_assoc()['total'];
$today_courses    = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'];
$week_courses     = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE DATE(created_at) >= '$last7'")->fetch_assoc()['total'];
$today_comments   = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'];
$week_comments    = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE DATE(created_at) >= '$last7'")->fetch_assoc()['total'];

// Recent activity
$where_user = $search ? "WHERE name LIKE '%$search%' OR email LIKE '%$search%'" : "";
$where_course = $search ? "WHERE title LIKE '%$search%'" : "";
if ($from && $to) {
  $where_user = "WHERE DATE(created_at) BETWEEN '$from' AND '$to'";
  $where_course = "WHERE DATE(created_at) BETWEEN '$from' AND '$to'";
}

$recent_users    = $conn->query("SELECT name, email, created_at FROM users $where_user ORDER BY created_at DESC LIMIT 5");
$recent_courses  = $conn->query("SELECT title, created_at FROM courses $where_course ORDER BY created_at DESC LIMIT 5");
$recent_comments = $conn->query("SELECT c.content, c.created_at, u.name, co.title 
                                 FROM comments c 
                                 JOIN users u ON c.user_id = u.id 
                                 JOIN courses co ON c.course_id = co.id 
                                 ORDER BY c.created_at DESC LIMIT 5");
$security_logs   = $conn->query("SELECT l.action, l.created_at, u.name 
                                 FROM logs l 
                                 JOIN users u ON l.user_id = u.id 
                                 ORDER BY l.created_at DESC LIMIT 10");
?>

<!-- 📊 Stat Cards -->
<div class="row text-center mb-4">
  <?php foreach ([
    ['Total Users', $user_count, 'primary'],
    ['Instructors', $instructor_count, 'info'],
    ['Learners', $learner_count, 'secondary'],
    ['Courses', $course_count, 'success'],
    ['PDF Uploads', $pdf_count, 'warning'],
    ['Video Uploads', $video_count, 'dark'],
    ['Pending Approvals', $pending_count, 'danger']
  ] as [$label, $count, $color]): ?>
  <div class="col-md-3 mb-3">
    <div class="card border-<?= $color ?>">
      <div class="card-body">
        <h6 class="text-<?= $color ?>"><?= $label ?></h6>
        <p class="fs-4"><?= $count ?></p>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- 📈 User Chart -->
<div class="row mb-4">
  <div class="col-md-6 mx-auto">
    <div class="card">
      <div class="card-header bg-secondary text-white text-center">📊 User Breakdown</div>
      <div class="card-body text-center">
        <canvas id="adminChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- 🗓 Summary -->
<div class="row mb-4">
  <div class="col-md-6">
    <div class="card border-success">
      <div class="card-header bg-success text-white">📅 Today</div>
      <div class="card-body">
        <p>👤 New Users: <strong><?= $today_users ?></strong></p>
        <p>📘 New Courses: <strong><?= $today_courses ?></strong></p>
        <p>💬 Comments: <strong><?= $today_comments ?></strong></p>
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

<!-- 🧭 Tabs for Users, Courses, Comments, Logs -->
<ul class="nav nav-tabs mt-4" role="tablist">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabUsers">👥 Users</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabCourses">📚 Courses</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabComments">💬 Comments</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabLogs">🔐 Logs</a></li>
</ul>

<div class="tab-content p-3 border border-top-0">
  <div class="tab-pane fade show active" id="tabUsers">
    <ul class="list-group">
      <?php while ($u = $recent_users->fetch_assoc()): ?>
        <li class="list-group-item">
          <?= htmlspecialchars($u['name']) ?> - <?= htmlspecialchars($u['email']) ?>
          <small class="float-end text-muted"><?= $u['created_at'] ?></small>
        </li>
      <?php endwhile; ?>
    </ul>
  </div>
  <div class="tab-pane fade" id="tabCourses">
    <ul class="list-group">
      <?php while ($c = $recent_courses->fetch_assoc()): ?>
        <li class="list-group-item">
          <?= htmlspecialchars($c['title']) ?>
          <small class="float-end text-muted"><?= $c['created_at'] ?></small>
        </li>
      <?php endwhile; ?>
    </ul>
  </div>
  <div class="tab-pane fade" id="tabComments">
    <ul class="list-group">
      <?php while ($com = $recent_comments->fetch_assoc()): ?>
        <li class="list-group-item">
          <?= htmlspecialchars($com['name']) ?> on <strong><?= htmlspecialchars($com['title']) ?></strong><br>
          <em><?= htmlspecialchars($com['content']) ?></em>
          <small class="text-muted float-end"><?= $com['created_at'] ?></small>
        </li>
      <?php endwhile; ?>
    </ul>
  </div>
  <div class="tab-pane fade" id="tabLogs">
    <button onclick="printLogs()" class="btn btn-sm btn-outline-dark mb-3 float-end">🖨️ Print Logs</button>
    <div id="logsTable">
      <ul class="list-group">
        <?php while ($log = $security_logs->fetch_assoc()): ?>
          <li class="list-group-item">
            <?= htmlspecialchars($log['name']) ?> - <?= htmlspecialchars($log['action']) ?>
            <small class="float-end text-muted"><?= $log['created_at'] ?></small>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>
  </div>
</div>

<!-- Chart Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('adminChart'), {
  type: 'doughnut',
  data: {
    labels: ['Learners', 'Instructors', 'Courses'],
    datasets: [{
      data: [<?= $learner_count ?>, <?= $instructor_count ?>, <?= $course_count ?>],
      backgroundColor: ['#3498db', '#9b59b6', '#f1c40f']
    }]
  },
  options: {
    plugins: {
      legend: { position: 'bottom' }
    }
  }
});

function printLogs() {
  const content = document.getElementById('logsTable').innerHTML;
  const printWin = window.open('', '', 'width=800,height=600');
  printWin.document.write('<html><head><title>Logs</title></head><body>');
  printWin.document.write('<h3>Activity Logs</h3>');
  printWin.document.write(content);
  printWin.document.write('</body></html>');
  printWin.document.close();
  printWin.print();
}
</script>
<?php endif; ?>






</div>
</body>
</html>
