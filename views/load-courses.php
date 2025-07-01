<?php
session_start();
include '../db-config.php';

$instructor_id = $_SESSION['user_id'];
$limit = 3;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$total = $conn->query("SELECT COUNT(*) as total FROM courses WHERE instructor_id = $instructor_id")
              ->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

$result = $conn->query("SELECT id, title, created_at FROM courses WHERE instructor_id = $instructor_id ORDER BY created_at DESC LIMIT $limit OFFSET $offset");

$i = $offset + 1;
?>

<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>📘 Course Title</th>
        <th>📅 Created On</th>
        <th>💬 Comments</th>
        <th>👥 Enrolled</th>
        <th style="width: 220px;">⚙️ Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($course = $result->fetch_assoc()): 
        $cid = $course['id'];
        $comments = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE course_id = $cid")->fetch_assoc()['total'] ?? 0;
        $students = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM course_progress WHERE course_id = $cid")->fetch_assoc()['total'] ?? 0;
      ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($course['title']) ?></td>
          <td><?= date("M d, Y", strtotime($course['created_at'])) ?></td>
          <td><span class="badge bg-info"><?= $comments ?></span></td>
          <td><span class="badge bg-success"><?= $students ?></span></td>
          <td>
            <div class="d-flex align-items-center text-nowrap">
              <a href="course-preview.php?id=<?= $cid ?>" class="btn btn-sm btn-outline-primary me-1">
                👁️ Preview
              </a>
              <a href="edit-course.php?id=<?= $cid ?>" class="btn btn-sm btn-outline-warning me-1">
                ✏️ Edit
              </a>
              <a href="delete-course.php?id=<?= $cid ?>" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Are you sure you want to delete this course?')">
                🗑️ Delete
              </a>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php if ($total_pages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination justify-content-center">
      <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="#" onclick="loadCourses(<?= $page - 1 ?>)">« Prev</a></li>
      <?php endif; ?>
      <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $total_pages ?></span></li>
      <?php if ($page < $total_pages): ?>
        <li class="page-item"><a class="page-link" href="#" onclick="loadCourses(<?= $page + 1 ?>)">Next »</a></li>
      <?php endif; ?>
    </ul>
  </nav>
<?php endif; ?>
