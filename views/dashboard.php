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

// Pagination Setup
$limit = 3;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Total Courses Count
$total_result = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE instructor_id = ?");
$total_result->bind_param("i", $instructor_id);
$total_result->execute();
$total_courses = $total_result->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_courses / $limit);

// Stats
$upload_pdf = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE instructor_id = $instructor_id AND file_path LIKE '%.pdf'")->fetch_assoc()['total'];
$upload_mp4 = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE instructor_id = $instructor_id AND file_path LIKE '%.mp4'")->fetch_assoc()['total'];
$total_enrolled = $conn->query("
  SELECT COUNT(DISTINCT cp.user_id) AS total 
  FROM course_progress cp 
  JOIN courses c ON cp.course_id = c.id 
  WHERE c.instructor_id = $instructor_id
")->fetch_assoc()['total'];

// Paginated Courses
$stmt = $conn->prepare("SELECT id, title, created_at FROM courses WHERE instructor_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $instructor_id, $limit, $offset);
$stmt->execute();
$paginated_courses = $stmt->get_result();

// Dropdown Courses for Comments Viewer
$courses_dropdown = $conn->prepare("SELECT id, title FROM courses WHERE instructor_id = ? ORDER BY created_at DESC");
$courses_dropdown->bind_param("i", $instructor_id);
$courses_dropdown->execute();
$courses_list = $courses_dropdown->get_result();

// Selected Course Comments
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
      <h6>Total Courses</h6><p class="fs-4"><?= $total_courses ?></p></div></div></div>
  <div class="col-md-3"><div class="card border-info"><div class="card-body text-center">
      <h6>PDFs Uploaded</h6><p class="fs-4"><?= $upload_pdf ?></p></div></div></div>
  <div class="col-md-3"><div class="card border-warning"><div class="card-body text-center">
      <h6>Videos Uploaded</h6><p class="fs-4"><?= $upload_mp4 ?></p></div></div></div>
  <div class="col-md-3"><div class="card border-success"><div class="card-body text-center">
      <h6>Students Enrolled</h6><p class="fs-4"><?= $total_enrolled ?></p></div></div></div>
</div>

<!-- 📚 Manage Courses Table -->
<div class="card mb-4">
  <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0">📋 Manage Your Courses</h5>
    <a href="add-course.php" class="btn btn-sm btn-light">➕ Add New Course</a>
  </div>
  <div class="card-body p-0">
    <?php if ($paginated_courses->num_rows > 0): ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>📘 Course Title</th>
              <th>📅 Created On</th>
              <th>💬 Comments</th>
              <th>👥 Enrolled</th>
              <th style="width: 180px;">⚙️ Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = $offset + 1; while ($course = $paginated_courses->fetch_assoc()): ?>
              <?php
                $course_id = $course['id'];
                $comment_count = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE course_id = $course_id")->fetch_assoc()['total'] ?? 0;
                $student_count = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM course_progress WHERE course_id = $course_id")->fetch_assoc()['total'] ?? 0;
              ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($course['title']) ?></td>
                <td><?= date("M d, Y", strtotime($course['created_at'])) ?></td>
                <td><span class="badge bg-info"><?= $comment_count ?></span></td>
                <td><span class="badge bg-success"><?= $student_count ?></span></td>
                <td>
                  <div class="d-flex flex-wrap gap-2">
                    <a href="course-preview.php?id=<?= $course_id ?>" 
                      class="btn btn-sm btn-outline-primary d-flex align-items-center">
                      👁️ <span class="ms-1">Preview</span>
                    </a>

                    <a href="delete-course.php?id=<?= $course_id ?>" 
                      class="btn btn-sm btn-outline-danger d-flex align-items-center"
                      onclick="return confirm('Are you sure you want to delete this course?')">
                      🗑️ <span class="ms-1">Delete</span>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- 📄 Pagination -->
      <?php if ($total_pages > 1): ?>
        <nav class="mt-3">
          <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
              <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">« Prev</a></li>
            <?php endif; ?>
            <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $total_pages ?></span></li>
            <?php if ($page < $total_pages): ?>
              <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Next »</a></li>
            <?php endif; ?>
          </ul>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <p class="p-3 text-muted mb-0">You haven't created any courses yet.</p>
    <?php endif; ?>
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

<!-- 💬 Comments + Students -->
<div class="row mb-5">
  <!-- 💬 Comments -->
  <div class="col-md-7">
    <div class="card h-100">
      <div class="card-header bg-primary text-white">💬 View & Reply to Comments</div>
      <div class="card-body">
        <form method="GET" class="mb-3">
          <label for="course_id">Select Course:</label>
          <div class="input-group">
            <select name="course_id" id="course_id" class="form-select" onchange="this.form.submit()">
              <option value="">-- Choose a Course --</option>
              <?php mysqli_data_seek($courses_list, 0); while ($course = $courses_list->fetch_assoc()): ?>
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
                  <?= nl2br(htmlspecialchars($com['content'])) ?>
                  <small class="text-muted float-end"><?= $com['created_at'] ?></small><br>
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
                      <?= nl2br(htmlspecialchars($reply['reply'])) ?>
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

  <!-- 👥 Students -->
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



<?php elseif ($role === 'learner'): ?>
<?php
$learner_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$filter = $_GET['filter'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$comments_per_page = 10;
$offset = ($page - 1) * $comments_per_page;

// Fetch all enrolled courses and progress
$stmt = $conn->prepare("
    SELECT cp.course_id, cp.status, cp.progress_percent, cp.updated_at, 
           c.title, c.created_at 
    FROM course_progress cp
    JOIN courses c ON cp.course_id = c.id
    WHERE cp.user_id = ?
    ORDER BY cp.updated_at DESC
");
$stmt->bind_param("i", $learner_id);
$stmt->execute();
$all_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Filter logic
$filtered_courses = array_filter($all_courses, fn($row) => $filter === 'all' || $row['status'] === $filter);

// Stats for Chart
$completed = $in_progress = $not_started = 0;
foreach ($all_courses as $c) {
    match ($c['status']) {
        'completed'    => $completed++,
        'in_progress'  => $in_progress++,
        default        => $not_started++
    };
}

// Paginated Comments
$comment_stmt = $conn->prepare("
  SELECT c.content, c.created_at, co.title 
  FROM comments c
  JOIN courses co ON c.course_id = co.id
  WHERE c.user_id = ?
  ORDER BY c.created_at DESC
  LIMIT ? OFFSET ?
");
$comment_stmt->bind_param("iii", $learner_id, $comments_per_page, $offset);
$comment_stmt->execute();
$recent_comments = $comment_stmt->get_result();

// Count total for pagination
$count_result = $conn->prepare("SELECT COUNT(*) as total FROM comments WHERE user_id = ?");
$count_result->bind_param("i", $learner_id);
$count_result->execute();
$total_comment = $count_result->get_result()->fetch_assoc()['total'] ?? 0;
$total_comment_pages = ceil($total_comment / $comments_per_page);

// Suggested Courses
$suggest = $conn->prepare("
  SELECT c.id, c.title, u.name AS instructor 
  FROM courses c 
  JOIN users u ON c.instructor_id = u.id
  WHERE c.id NOT IN (
    SELECT course_id FROM course_progress WHERE user_id = ?
  )
  ORDER BY c.created_at DESC
  LIMIT 3
");
$suggest->bind_param("i", $learner_id);
$suggest->execute();
$suggestions = $suggest->get_result();

function getCourseIdByTitle($conn, $title) {
  $stmt = $conn->prepare("SELECT id FROM courses WHERE title = ? LIMIT 1");
  $stmt->bind_param("s", $title);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  return $result['id'] ?? '#';
}

// Time ago helper
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    return match (true) {
        $diff < 60      => 'Just now',
        $diff < 3600    => floor($diff / 60) . ' min ago',
        $diff < 86400   => floor($diff / 3600) . ' hour ago',
        $diff < 172800  => 'Yesterday',
        default         => floor($diff / 86400) . ' days ago'
    };
}
?>

<div class="mb-4">
  <h3 class="mb-3 fw-bold">👋 Welcome, <?= htmlspecialchars($name) ?>!</h3>

  <!-- 🧭 Tabs -->
  <ul class="nav nav-tabs mb-4" id="learnerTabs" role="tablist">
    <li class="nav-item">
      <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>" href="?filter=all">📘 All Enrolled</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $filter === 'completed' ? 'active' : '' ?>" href="?filter=completed">✅ Completed</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $filter === 'chart' ? 'active' : '' ?>" href="?filter=chart">📊 Progress Chart</a>
    </li>
  </ul>

  <!-- 📘 All Enrolled -->
  <?php if ($filter === 'all'): ?>
    <?php if (count($filtered_courses) > 0): ?>
      <div class="list-group shadow-sm mb-4">
        <?php foreach ($filtered_courses as $course): ?>
          <?php
            $progress = (int)$course['progress_percent'];
            $status = $course['status'];
          ?>
          <div class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div class="me-md-3">
              <div class="fw-semibold fs-5"><?= htmlspecialchars($course['title']) ?></div>
              <div class="text-muted small">
                Enrolled on <?= date('M d, Y', strtotime($course['created_at'])) ?> · 
                Last accessed <?= timeAgo($course['updated_at']) ?>
              </div>
              <div class="progress mt-2" style="height: 8px;">
                <div class="progress-bar bg-<?= $progress === 100 ? 'success' : ($progress > 0 ? 'info' : 'secondary') ?>" 
                     style="width: <?= $progress ?>%;"></div>
              </div>
            </div>
            <div class="mt-3 mt-md-0 text-md-end">
              <span class="badge bg-<?= $status === 'completed' ? 'success' : 'warning' ?> mb-2">
                <?= ucfirst(str_replace('_', ' ', $status)) ?>
              </span><br>
              <a href="course-view.php?id=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-primary">▶️ View</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="alert alert-info">No enrolled courses found.</div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- ✅ Completed -->
  <?php if ($filter === 'completed'): ?>
    <?php
      $completed_courses = array_filter($filtered_courses, fn($c) => $c['status'] === 'completed');
    ?>
    <?php if (count($completed_courses) > 0): ?>
      <div class="list-group shadow-sm mb-4">
        <?php foreach ($completed_courses as $course): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><?= htmlspecialchars($course['title']) ?></div>
              <small class="text-muted">Completed on <?= date('M d, Y', strtotime($course['updated_at'])) ?></small>
            </div>
            <a href="course-view.php?id=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-success">🎓 Review</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-muted">No completed courses yet.</p>
    <?php endif; ?>
  <?php endif; ?>

  <!-- 📊 Chart -->
  <?php if ($filter === 'chart'): ?>
    <div class="text-center mt-4">
      <h5>📈 Course Progress Overview</h5>
      <div style="max-width: 300px; margin: auto;">
        <canvas id="progressChart" height="100"></canvas>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    new Chart(document.getElementById('progressChart'), {
      type: 'doughnut',
      data: {
        labels: ['Completed', 'In Progress', 'Not Started'],
        datasets: [{
          data: [<?= $completed ?>, <?= $in_progress ?>, <?= $not_started ?>],
          backgroundColor: ['#28a745', '#ffc107', '#adb5bd']
        }]
      },
      options: {
        cutout: '65%',
        plugins: { legend: { position: 'bottom' } }
      }
    });
    </script>
  <?php endif; ?>

  <!-- 💬 Recent Comments -->
<div class="mt-5">
  <h5>💬 Your Recent Comments</h5>
  <?php if ($recent_comments->num_rows > 0): ?>
    <ul class="list-group mb-3">
      <?php
        // Fetch instructor names for each comment
        while ($c = $recent_comments->fetch_assoc()):
          // Get instructor name from the course
          $instructor_stmt = $conn->prepare("
            SELECT u.name 
            FROM courses c 
            JOIN users u ON c.instructor_id = u.id 
            WHERE c.title = ?
            LIMIT 1
          ");
          $instructor_stmt->bind_param("s", $c['title']);
          $instructor_stmt->execute();
          $instructor = $instructor_stmt->get_result()->fetch_assoc()['name'] ?? 'N/A';
      ?>
      <li class="list-group-item">
        <div class="fw-bold">Course: <?= htmlspecialchars($c['title']) ?></div>
        <div class="text-muted small mb-2">Instructor: <?= htmlspecialchars($instructor) ?></div>
        <div><?= nl2br(htmlspecialchars($c['content'])) ?></div>
        <div class="text-muted small mt-1"><?= date("M d, Y h:i A", strtotime($c['created_at'])) ?></div>
        <div class="mt-2">
          <a href="course-view.php?id=<?= $course_id = getCourseIdByTitle($conn, $c['title']) ?>" class="btn btn-sm btn-outline-primary">🔗 View Course</a>
        </div>
      </li>
      <?php endwhile; ?>
    </ul>

    <!-- Pagination -->
    <?php if ($total_comment_pages > 1): ?>
      <nav>
        <ul class="pagination justify-content-center">
          <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">« Prev</a></li>
          <?php endif; ?>
          <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $total_comment_pages ?></span></li>
          <?php if ($page < $total_comment_pages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Next »</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <p class="text-muted">No comments yet.</p>
  <?php endif; ?>
</div>


  <!-- 🎯 Suggested Courses -->
  <div class="mt-5">
    <h5>🎯 Suggested Courses for You</h5>
    <?php if ($suggestions->num_rows): ?>
      <div class="row g-3">
        <?php while ($s = $suggestions->fetch_assoc()): ?>
          <div class="col-md-4">
            <div class="card h-100 shadow-sm">
              <div class="card-body d-flex flex-column">
                <h6 class="card-title"><?= htmlspecialchars($s['title']) ?></h6>
                <p class="text-muted small">Instructor: <?= htmlspecialchars($s['instructor']) ?></p>
                <a href="course-view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary mt-auto">Explore</a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p class="text-muted">You're enrolled in all available courses.</p>
    <?php endif; ?>
  </div>

  <div class="mt-4 text-end">
    <a href="course-list.php" class="btn btn-secondary">📚 Browse More Courses</a>
  </div>
</div>

<!-- Chart Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('progressChart'), {
  type: 'doughnut',
  data: {
    labels: ['Completed', 'In Progress', 'Not Started'],
    datasets: [{
      data: [<?= $completed ?>, <?= $in_progress ?>, <?= $not_started ?>],
      backgroundColor: ['#28a745', '#ffc107', '#adb5bd']
    }]
  },
  options: {
    cutout: '65%',
    responsive: true,
    plugins: {
      legend: { position: 'bottom' }
    }
  }
});
</script>


<!-- admin role -->

<?php elseif ($role === 'admin'): ?>
<?php
include '../includes/functions.php';

// 🧠 Filters
$search = $_GET['search'] ?? '';
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';

$search = $conn->real_escape_string($search);
$from   = $conn->real_escape_string($from);
$to     = $conn->real_escape_string($to);

// 🔍 Where Clauses for Pagination Lists
$where_user = "WHERE 1";
$where_course = "WHERE 1";

if ($search) {
    $where_user .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
    $where_course .= " AND title LIKE '%$search%'";
}
if ($from && $to) {
    $where_user .= " AND DATE(created_at) BETWEEN '$from' AND '$to'";
    $where_course .= " AND DATE(created_at) BETWEEN '$from' AND '$to'";
}

// 📆 Dates
$today = date('Y-m-d');
$last7 = date('Y-m-d', strtotime('-7 days'));

// 📊 Quick Stats
$user_count       = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$instructor_count = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'instructor'")->fetch_assoc()['total'];
$learner_count    = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'learner'")->fetch_assoc()['total'];
$pending_count    = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'instructor' AND is_approved = 0")->fetch_assoc()['total'];
$course_count     = $conn->query("SELECT COUNT(*) AS total FROM courses")->fetch_assoc()['total'];
$pdf_count        = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE file_path LIKE '%.pdf'")->fetch_assoc()['total'];
$video_count      = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE file_path LIKE '%.mp4'")->fetch_assoc()['total'];
$total_comments   = $conn->query("SELECT COUNT(*) AS total FROM comments")->fetch_assoc()['total'];

$today_users    = $conn->query("SELECT COUNT(*) AS total FROM users WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'];
$week_users     = $conn->query("SELECT COUNT(*) AS total FROM users WHERE DATE(created_at) >= '$last7'")->fetch_assoc()['total'];
$today_courses  = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'];
$week_courses   = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE DATE(created_at) >= '$last7'")->fetch_assoc()['total'];
$today_comments = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'];
$week_comments  = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE DATE(created_at) >= '$last7'")->fetch_assoc()['total'];

$today_pdf = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE file_path LIKE '%.pdf' AND DATE(created_at) = '$today'")->fetch_assoc()['total'];
$today_video = $conn->query("SELECT COUNT(*) AS total FROM courses WHERE file_path LIKE '%.mp4' AND DATE(created_at) = '$today'")->fetch_assoc()['total'];

// 📄 Pagination Setup
$users_per_page = 5;
$courses_per_page = 5;
$comments_per_page = 5;

$user_page    = max(1, (int)($_GET['user_page'] ?? 1));
$course_page  = max(1, (int)($_GET['course_page'] ?? 1));
$comment_page = max(1, (int)($_GET['comment_page'] ?? 1));

$user_offset    = ($user_page - 1) * $users_per_page;
$course_offset  = ($course_page - 1) * $courses_per_page;
$comment_offset = ($comment_page - 1) * $comments_per_page;

// 🧮 Total Counts for Pagination
$total_users    = $conn->query("SELECT COUNT(*) AS total FROM users $where_user")->fetch_assoc()['total'] ?? 0;
$total_courses  = $conn->query("SELECT COUNT(*) AS total FROM courses $where_course")->fetch_assoc()['total'] ?? 0;
$total_comments = $conn->query("SELECT COUNT(*) AS total FROM comments")->fetch_assoc()['total'] ?? 0;

$total_user_pages    = ceil($total_users / $users_per_page);
$total_course_pages  = ceil($total_courses / $courses_per_page);
$total_comment_pages = ceil($total_comments / $comments_per_page);

// 📋 Fetch Paginated Lists
$recent_users = $conn->query("SELECT name, email, created_at FROM users $where_user ORDER BY created_at DESC LIMIT $users_per_page OFFSET $user_offset");
$recent_courses = $conn->query("SELECT title, created_at FROM courses $where_course ORDER BY created_at DESC LIMIT $courses_per_page OFFSET $course_offset");
$recent_comments = $conn->query("
    SELECT c.content, c.created_at, u.name, co.title 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    JOIN courses co ON c.course_id = co.id 
    ORDER BY c.created_at DESC 
    LIMIT $comments_per_page OFFSET $comment_offset
");

// 🔐 Logs Filter
$log_from = $_GET['log_from'] ?? '';
$log_to   = $_GET['log_to'] ?? '';
$log_role = $_GET['log_role'] ?? '';
$log_action = $_GET['log_action'] ?? '';
$log_conditions = [];

if ($log_from)     $log_conditions[] = "DATE(l.created_at) >= '$log_from'";
if ($log_to)       $log_conditions[] = "DATE(l.created_at) <= '$log_to'";
if ($log_role)     $log_conditions[] = "u.role = '$log_role'";
if ($log_action)   $log_conditions[] = "l.action LIKE '%$log_action%'";
$where_log = count($log_conditions) > 0 ? "WHERE " . implode(" AND ", $log_conditions) : "";

$security_logs = $conn->query("
  SELECT l.action, l.created_at, u.name, u.role 
  FROM logs l 
  JOIN users u ON l.user_id = u.id 
  $where_log 
  ORDER BY l.created_at DESC
  LIMIT 100
");
?>

<!-- 📊 Summary Cards -->
<div class="row text-center mb-4">
  <?php foreach ([
    ['Total Users', $user_count, 'primary'],
    ['Instructors', $instructor_count, 'info'],
    ['Learners', $learner_count, 'secondary'],
    ['Courses', $course_count, 'success'],
    ['PDF Uploads', $pdf_count, 'warning'],
    ['Video Uploads', $video_count, 'dark'],
    ['🗨️ Total Comments', $total_comments, 'secondary'],
    ['Pending Approvals', $pending_count, 'danger']
  ] as [$label, $count, $color]): ?>
    <div class="col-md-3 col-sm-6 mb-3">
      <div class="card border-<?= $color ?> shadow-sm">
        <div class="card-body">
          <h6 class="text-<?= $color ?>"><?= $label ?></h6>
          <p class="fs-4"><?= $count ?></p>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- 📈 Analytics Charts Row -->
<div class="row mb-4">
  <!-- 🧍 User Breakdown Chart -->
  <div class="col-md-6">
    <div class="card h-100 shadow-sm">
      <div class="card-header bg-secondary text-white text-center py-2">
        📊 User & Course Distribution
      </div>
      <div class="card-body text-center">
        <canvas id="adminChart" height="180" style="max-height: 220px;"></canvas>
      </div>
    </div>
  </div>

  <!-- 📘 Course Composition Chart -->
  <div class="col-md-6">
    <div class="card h-100 shadow-sm">
      <div class="card-header bg-dark text-white text-center py-2">
        📘 Course Asset Composition
      </div>
      <div class="card-body text-center">
        <canvas id="courseStatsChart" height="180" style="max-height: 220px;"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- 📅 Summary Section -->
<div class="row mb-4">
  <div class="col-md-6">
    <div class="card border-success shadow-sm">
      <div class="card-header bg-success text-white">📅 Today</div>
      <div class="card-body">
        <p>👤 New Users: <strong><?= $today_users ?></strong></p>
        <p>📘 New Courses: <strong><?= $today_courses ?></strong></p>
        <p>💬 Comments: <strong><?= $today_comments ?></strong></p>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card border-info shadow-sm">
      <div class="card-header bg-info text-white">🗓 Last 7 Days</div>
      <div class="card-body">
        <p>👤 Users: <strong><?= $week_users ?></strong></p>
        <p>📘 Courses: <strong><?= $week_courses ?></strong></p>
        <p>💬 Comments: <strong><?= $week_comments ?></strong></p>
      </div>
    </div>
  </div>
</div>


<!-- 🧭 Tabs -->
<ul class="nav nav-tabs mt-4" role="tablist">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabUsers">👥 Users</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabCourses">📚 Courses</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabComments">💬 Comments</a></li>
</ul>

<!-- 📁 Tab Content -->
<div class="tab-content p-3 border border-top-0">
  <!-- Users Tab -->
  <div class="tab-pane fade show active" id="tabUsers">
    <ul class="list-group">
      <?php while ($u = $recent_users->fetch_assoc()): ?>
        <li class="list-group-item">
          <?= htmlspecialchars($u['name']) ?> - <?= htmlspecialchars($u['email']) ?>
          <small class="float-end text-muted"><?= $u['created_at'] ?></small>
        </li>
      <?php endwhile; ?>
    </ul>
    <?php if ($total_user_pages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination justify-content-center">
          <?php for ($i = 1; $i <= $total_user_pages; $i++): ?>
            <li class="page-item <?= $i == $user_page ? 'active' : '' ?>">
              <a class="page-link" href="?user_page=<?= $i ?>#tabUsers"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>

  <!-- Courses Tab -->
  <div class="tab-pane fade" id="tabCourses">
    <ul class="list-group">
      <?php while ($c = $recent_courses->fetch_assoc()): ?>
        <li class="list-group-item">
          <?= htmlspecialchars($c['title']) ?>
          <small class="float-end text-muted"><?= $c['created_at'] ?></small>
        </li>
      <?php endwhile; ?>
    </ul>
    <?php if ($total_course_pages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination justify-content-center">
          <?php for ($i = 1; $i <= $total_course_pages; $i++): ?>
            <li class="page-item <?= $i == $course_page ? 'active' : '' ?>">
              <a class="page-link" href="?course_page=<?= $i ?>#tabCourses"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>

  <!-- Comments Tab -->
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
    <?php if ($total_comment_pages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination justify-content-center">
          <?php for ($i = 1; $i <= $total_comment_pages; $i++): ?>
            <li class="page-item <?= $i == $comment_page ? 'active' : '' ?>">
              <a class="page-link" href="?comment_page=<?= $i ?>#tabComments"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</div>

<!-- 🔐 Logs Section -->
<hr class="my-5">
<h4 class="mb-3">🔐 Activity Logs</h4>

<!-- 🔍 Filters for Logs -->
<form id="logFilterForm" class="row g-2 mb-3">
  <div class="col-md-2">
    <input type="date" name="log_from" class="form-control" value="<?= htmlspecialchars($log_from) ?>">
  </div>
  <div class="col-md-2">
    <input type="date" name="log_to" class="form-control" value="<?= htmlspecialchars($log_to) ?>">
  </div>
  <div class="col-md-2">
    <select name="log_role" class="form-select">
      <option value="">All Roles</option>
      <option value="admin" <?= $log_role === 'admin' ? 'selected' : '' ?>>Admin</option>
      <option value="instructor" <?= $log_role === 'instructor' ? 'selected' : '' ?>>Instructor</option>
      <option value="learner" <?= $log_role === 'learner' ? 'selected' : '' ?>>Learner</option>
    </select>
  </div>
  <div class="col-md-3">
    <input type="text" name="log_action" class="form-control" placeholder="Search action..." value="<?= htmlspecialchars($log_action) ?>">
  </div>
  <div class="col-md-3 d-flex gap-2">
    <button type="submit" class="btn btn-outline-primary w-50">Filter Logs</button>
    <button type="button" class="btn btn-outline-secondary w-50" id="clearLogsBtn">Clear</button>
  </div>
</form>

<!-- 🧾 Logs Controls -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <span id="logCountDisplay" class="text-muted small">Showing 0–0 of 0 logs</span>
  </div>
  <div class="btn-group">
    <button type="button" class="btn btn-sm btn-outline-success" onclick="exportLogs('csv')">⬇️ Export CSV</button>
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="exportLogs('pdf')">⬇️ Export PDF</button>
    <button type="button" onclick="printLogs()" class="btn btn-sm btn-outline-dark">🖨️ Print</button>
  </div>
</div>

<!-- 📋 Logs Table Placeholder -->
<div id="logsTable" class="table-responsive">
  <p class="text-muted">Loading logs...</p>
</div>



<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 📊 Doughnut Chart
new Chart(document.getElementById('adminChart'), {
  type: 'doughnut',
  data: {
    labels: ['Learners', 'Instructors', 'Courses'],
    datasets: [{
      label: 'User Distribution',
      data: [<?= $learner_count ?>, <?= $instructor_count ?>, <?= $course_count ?>],
      backgroundColor: ['#3498db', '#9b59b6', '#f1c40f'],
      borderColor: '#ffffff',
      borderWidth: 2,
      hoverOffset: 20
    }]
  },
  options: {
    responsive: true,
    cutout: '60%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          usePointStyle: true,
          padding: 20,
          color: '#333',
          font: {
            size: 14,
            weight: '500'
          }
        }
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            const label = context.label || '';
            const value = context.raw || 0;
            return `${label}: ${value}`;
          }
        },
        backgroundColor: '#333',
        titleFont: { size: 13 },
        bodyFont: { size: 14 },
        padding: 10
      },
      title: {
        display: true,
        text: '',
        font: {
          size: 16,
          weight: 'bold'
        },
        padding: {
          top: 10,
          bottom: 20
        }
      }
    }
  }
});



new Chart(document.getElementById('courseStatsChart'), {
  type: 'bar',
  data: {
    labels: ['Courses', 'PDFs', 'Videos', 'Comments'],
    datasets: [{
      label: 'Total Count',
      data: [<?= $course_count ?>, <?= $pdf_count ?>, <?= $video_count ?>, <?= $total_comments ?>],
      backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#6c757d'],
      borderColor: ['#1e7e34', '#d39e00', '#117a8b', '#495057'],
      borderWidth: 1,
      borderRadius: 6,
      barPercentage: 0.6,
      categoryPercentage: 0.5
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: true,
        position: 'top',
        labels: {
          boxWidth: 15,
          font: {
            size: 13,
            weight: 'bold'
          }
        }
      },
      title: {
        display: true,
        text: '',
        font: {
          size: 16,
          weight: 'bold'
        },
        padding: {
          top: 10,
          bottom: 20
        }
      },
      tooltip: {
        enabled: true,
        callbacks: {
          label: function(context) {
            return `${context.label}: ${context.raw}`;
          }
        },
        backgroundColor: '#222',
        titleColor: '#fff',
        bodyColor: '#fff',
        cornerRadius: 6,
        padding: 10
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          stepSize: 1,
          precision: 0
        },
        title: {
          display: true,
          text: 'Count',
          font: {
            size: 14,
            weight: '600'
          }
        }
      },
      x: {
        title: {
          display: true,
          text: 'Type of Content',
          font: {
            size: 14,
            weight: '600'
          }
        }
      }
    },
    animation: {
      duration: 1000,
      easing: 'easeOutBounce'
    }
  }
});




// 🖨 Print Logs
function printLogs() {
  const content = document.getElementById('logsTable').innerHTML;
  const printWin = window.open('', '', 'width=1000,height=600');
  printWin.document.write(`
    <html><head><title>Activity Logs</title>
    <style>
      body { font-family: Arial; margin: 20px; }
      h3 { text-align: center; }
      table { width: 100%; border-collapse: collapse; }
      th, td { padding: 8px; border: 1px solid #ccc; text-align: left; }
      th { background-color: #f9f9f9; }
    </style></head><body>
    <h3>Activity Logs</h3>
    ${content}
    </body></html>
  `);
  printWin.document.close();
  printWin.print();
}

// 📥 Export Logs (CSV/PDF)
function exportLogs(format) {
  const form = document.getElementById('logFilterForm');
  const data = new FormData(form);
  const url = new URL('load-logs.php', window.location.origin);

  url.searchParams.set('export', format);

  for (const [key, val] of data.entries()) {
    url.searchParams.set(key, val);
  }

  url.searchParams.delete('page'); // 🔥 important
  window.open(url.toString(), '_blank');
}


// 🔄 Load logs
function loadLogs(queryParams = '') {
  const url = 'load-logs.php' + (queryParams ? '?' + queryParams : '');
  fetch(url)
    .then(res => res.text())
    .then(html => {
      document.getElementById('logsTable').innerHTML = html;
    });
}

// 🔢 Update log count display
function updateLogCount(from, to, total) {
  const el = document.getElementById("logCountDisplay");
  if (el) {
    el.textContent = `Showing ${from}–${to} of ${total} logs`;
  }
}

// 📄 Handle filter submit
document.getElementById('logFilterForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  const query = new URLSearchParams(formData).toString();
  loadLogs(query);
});

// ❌ Clear filters
document.getElementById('clearLogsBtn').addEventListener('click', function () {
  document.getElementById('logFilterForm').reset();
  loadLogs(); // Reset to all logs
});

// 🔃 Initial load
document.addEventListener('DOMContentLoaded', () => {
  loadLogs();
});

function loadLogsPaginated(page = 1) {
  const form = document.getElementById('logFilterForm');
  const data = new FormData(form);
  data.set('page', page); // pagination
  const query = new URLSearchParams(data).toString();
  loadLogs(query);
}

</script>

<?php endif; ?>
</div>
<!-- REQUIRED for Bootstrap Tabs to work -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
