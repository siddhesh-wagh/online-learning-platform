<?php
include '../includes/auth.php';
include '../db-config.php';

$role = $_SESSION['role'] ?? '';
$is_admin = ($role === 'admin') && isset($_GET['admin']);
$is_learner = $role === 'learner';

if (!$is_learner && !$is_admin) {
    echo "Access denied.";
    exit;
}

$user_id = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6;
$offset = ($page - 1) * $limit;
$search_sql = "%$search%";

// Total count
$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM courses c 
    JOIN users u ON c.instructor_id = u.id 
    WHERE c.title LIKE ? OR u.name LIKE ?
");
$count_stmt->bind_param("ss", $search_sql, $search_sql);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total / $limit);

// Fetch courses
$stmt = $conn->prepare("
    SELECT c.id, c.title, c.description, c.thumbnail_path, u.name AS instructor_name 
    FROM courses c
    JOIN users u ON c.instructor_id = u.id 
    WHERE c.title LIKE ? OR u.name LIKE ? 
    ORDER BY c.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ssii", $search_sql, $search_sql, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Available Courses | EduPlatform</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- 🔝 Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">🎓 EduPlatform</a>
    <div class="d-flex">
      <a href="dashboard.php" class="btn btn-outline-light btn-sm">← Back to Dashboard</a>
    </div>
  </div>
</nav>

<!-- 📚 Main Content -->
<div class="container py-5">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">📚 Available Courses</h2>
  </div>

  <!-- 🔍 Search Bar -->
  <form method="GET" class="mb-4">
    <div class="input-group">
      <input type="text" name="search" class="form-control" placeholder="Search by course or instructor..." value="<?= htmlspecialchars($search) ?>">
      <?php if ($is_admin): ?>
        <input type="hidden" name="admin" value="1">
      <?php endif; ?>
      <button class="btn btn-primary">Search</button>
    </div>
  </form>

  <?php if ($result->num_rows > 0): ?>
    <div class="row g-4">
      <?php while ($course = $result->fetch_assoc()): ?>
        <?php
          $cid = $course['id'];
          $thumbnail = $course['thumbnail_path'] ?? '';
          $thumbnail_src = ($thumbnail && file_exists("../$thumbnail")) ? "../$thumbnail" : "../assets/images/placeholder-course.png";

          // Get learner progress
          $progress = 0;
          if ($is_learner) {
              $progress_stmt = $conn->prepare("SELECT progress_percent FROM course_progress WHERE user_id = ? AND course_id = ?");
              $progress_stmt->bind_param("ii", $user_id, $cid);
              $progress_stmt->execute();
              $progress_data = $progress_stmt->get_result()->fetch_assoc();
              $progress = $progress_data['progress_percent'] ?? 0;
          }

          $progress_class = $progress === 100 ? 'success' : ($progress > 0 ? 'info' : 'secondary');
        ?>
        <div class="col-md-4">
          <div class="card shadow-sm h-100 border-0">
            <img src="<?= $thumbnail_src ?>" class="card-img-top" alt="Course Thumbnail" style="height: 200px; object-fit: cover;">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title"><?= htmlspecialchars($course['title']) ?></h5>
              <p class="text-muted mb-2">👤 <?= htmlspecialchars($course['instructor_name']) ?></p>
              <p class="card-text small mb-3"><?= nl2br(htmlspecialchars($course['description'])) ?></p>

              <?php if ($is_learner): ?>
                <div class="progress mb-3" style="height: 18px;">
                  <div class="progress-bar bg-<?= $progress_class ?>" role="progressbar"
                       style="width: <?= $progress ?>%;"
                       aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                    <?= $progress ?>%
                  </div>
                </div>
              <?php endif; ?>

              <a href="course-view.php?id=<?= $cid ?>" class="btn btn-sm btn-outline-primary mt-auto">🔎 View Course</a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

    <!-- 🔗 Pagination -->
    <?php if ($total_pages > 1): ?>
      <nav class="mt-5">
        <ul class="pagination justify-content-center">
          <?php if ($page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">« Prev</a>
            </li>
          <?php endif; ?>

          <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $total_pages ?></span></li>

          <?php if ($page < $total_pages): ?>
            <li class="page-item">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next »</a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>

  <?php else: ?>
    <div class="alert alert-info text-center">No courses found.</div>
  <?php endif; ?>
</div>

</body>
</html>
