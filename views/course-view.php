<?php
include '../includes/auth.php';
include '../db-config.php';
include '../includes/functions.php';

if ($_SESSION['role'] !== 'learner') {
    echo "Access denied.";
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid course ID.";
    exit;
}

$course_id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'];
$current_percent = 0;

// Fetch course info
$stmt = $conn->prepare("SELECT c.title, c.description, c.file_path, c.instructor_id, u.name AS instructor_name, u.email AS instructor_email
                        FROM courses c
                        JOIN users u ON c.instructor_id = u.id
                        WHERE c.id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    echo "Course not found.";
    exit;
}

// Check enrollment
$enrolled_stmt = $conn->prepare("SELECT progress_percent FROM course_progress WHERE user_id = ? AND course_id = ?");
$enrolled_stmt->bind_param("ii", $user_id, $course_id);
$enrolled_stmt->execute();
$enrollment_result = $enrolled_stmt->get_result();
$is_enrolled = $enrollment_result->num_rows > 0;

if ($is_enrolled) {
    $current_percent = $enrollment_result->fetch_assoc()['progress_percent'];
}

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Enroll
    if (isset($_POST['enroll'])) {
        $insert = $conn->prepare("INSERT INTO course_progress (user_id, course_id, progress_percent, status, updated_at)
                                  VALUES (?, ?, 0, 'not_started', NOW())");
        $insert->bind_param("ii", $user_id, $course_id);
        $insert->execute();
        logAction($conn, $user_id, "Enrolled in course: " . $course['title']);
        header("Location: course-view.php?id=$course_id");
        exit;
    }

    if (!$is_enrolled) {
        echo "<div class='alert alert-danger'>You must enroll to interact with this course.</div>";
        exit;
    }

    // Update progress
    if (isset($_POST['update_progress'])) {
        $percent = (int) $_POST['progress_percent'];
        if ($percent >= 0 && $percent <= 100) {
            $stmt = $conn->prepare("UPDATE course_progress SET progress_percent = ?, status = ?, updated_at = NOW()
                                    WHERE user_id = ? AND course_id = ?");
            $status = $percent == 100 ? 'completed' : ($percent > 0 ? 'in_progress' : 'not_started');
            $stmt->bind_param("isii", $percent, $status, $user_id, $course_id);
            $stmt->execute();
            $current_percent = $percent;
            logAction($conn, $user_id, "Updated progress to $percent% on course: " . $course['title']);
        }
    }

    // Reset
    elseif (isset($_POST['reset_progress'])) {
        $stmt = $conn->prepare("UPDATE course_progress SET progress_percent = 0, status = 'not_started', updated_at = NOW()
                                WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $current_percent = 0;
        logAction($conn, $user_id, "Reset progress for course: " . $course['title']);
    }

    // Comment
    elseif (isset($_POST['comment'])) {
        $comment = trim($_POST['comment']);
        if (!empty($comment)) {
            $stmt = $conn->prepare("INSERT INTO comments (course_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $course_id, $user_id, $comment);
            $stmt->execute();
            logAction($conn, $user_id, "Commented on course: " . $course['title']);

            $notif = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $message = $_SESSION['name'] . " commented on your course: " . $course['title'];
            $notif->bind_param("is", $course['instructor_id'], $message);
            $notif->execute();

            include_once '../includes/mailer.php';
            sendEmail(
                $course['instructor_email'],
                "📝 New Comment on Your Course: " . $course['title'],
                "<h3>Hello {$course['instructor_name']},</h3>
                 <p><strong>{$_SESSION['name']}</strong> commented on your course <em>{$course['title']}</em>:</p>
                 <blockquote>$comment</blockquote>
                 <p><a href='http://localhost/online-learning-platform/views/course-view.php?id={$course_id}'>View Course</a></p>"
            );
        }
    }
}

// Fetch comments (paginated)
$comments_per_page = 5;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $comments_per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM comments WHERE course_id = ?");
$count_stmt->bind_param("i", $course_id);
$count_stmt->execute();
$total_comments = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_comments / $comments_per_page);

$comments_stmt = $conn->prepare("
    SELECT c.id AS comment_id, c.content, c.created_at, u.name 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.course_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT ? OFFSET ?
");
$comments_stmt->bind_param("iii", $course_id, $comments_per_page, $offset);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($course['title']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2><?= htmlspecialchars($course['title']) ?></h2>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← Back to Dashboard</a>
  </div>

  <p><strong>Instructor:</strong> <?= htmlspecialchars($course['instructor_name']) ?></p>
  <p><?= nl2br(htmlspecialchars($course['description'])) ?></p>

  <?php if (!$is_enrolled): ?>
    <div class="alert alert-warning text-center">
      <p>🔒 You are not enrolled in this course.</p>
      <form method="POST">
        <button type="submit" name="enroll" class="btn btn-success">📥 Enroll Now</button>
      </form>
    </div>
  <?php else: ?>

    <!-- 🎞️ Course Material -->
    <?php if (!empty($course['file_path'])):
      $file_url = "../" . $course['file_path'];
      $file_ext = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
      $safe_title = preg_replace("/[^a-zA-Z0-9]/", "", $course['title']);
      $download_name = "CourseMaterial_" . $course_id . "_" . $safe_title . "." . $file_ext;
    ?>
      <h4 class="mt-4">📁 Course Material</h4>
      <div class="card mb-4">
        <div class="row g-0">
          <div class="col-md-8 p-3">
            <?php if ($file_ext === 'mp4'): ?>
              <video class="w-100" height="320" controls>
                <source src="<?= $file_url ?>" type="video/mp4">
              </video>
            <?php elseif ($file_ext === 'pdf'): ?>
              <embed src="<?= $file_url ?>" width="100%" height="500px" type="application/pdf">
            <?php endif; ?>
          </div>
          <div class="col-md-4 border-start p-3">
            <h5 class="mb-3">📥 Download</h5>
            <a href="<?= $file_url ?>" class="btn btn-success w-100 mb-2" download="<?= $download_name ?>">Download</a>
            <div class="mb-3 text-muted">
              Type: <?= strtoupper($file_ext) ?><br>
              Size: <?= round(filesize($file_url) / 1024 / 1024, 2) ?> MB
            </div>
            <hr>
            <h6>📊 Progress</h6>
            <form method="POST" class="d-grid gap-2">
              <div class="progress mb-2" style="height: 20px;">
                <div class="progress-bar bg-<?= $current_percent == 100 ? 'success' : 'info' ?>" 
                     style="width: <?= $current_percent ?>%;">
                  <?= $current_percent ?>%
                </div>
              </div>
              <select name="progress_percent" class="form-select form-select-sm">
                <?php foreach ([0, 20, 40, 60, 80, 100] as $p): ?>
                  <option value="<?= $p ?>" <?= $p == $current_percent ? 'selected' : '' ?>><?= $p ?>%</option>
                <?php endforeach; ?>
              </select>
              <button type="submit" name="update_progress" class="btn btn-outline-primary btn-sm">Update</button>
              <?php if ($current_percent > 0): ?>
                <button type="submit" name="reset_progress" class="btn btn-outline-danger btn-sm">Reset</button>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- 💬 Comments -->
    <hr>
    <h4>💬 Discussion</h4>
    <form method="POST" class="mb-4">
      <textarea name="comment" class="form-control" rows="4" required placeholder="Write your comment..."></textarea>
      <button type="submit" class="btn btn-primary mt-2">Post Comment</button>
    </form>

    <?php if ($comments_result->num_rows > 0): ?>
      <?php while ($comment = $comments_result->fetch_assoc()): ?>
        <div class="border rounded p-3 mb-3 bg-white">
          <strong><?= htmlspecialchars($comment['name']) ?></strong>
          <small class="text-muted float-end"><?= $comment['created_at'] ?></small>
          <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
        </div>
      <?php endwhile; ?>
      <?php if ($total_pages > 1): ?>
        <nav><ul class="pagination">
          <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?id=<?= $course_id ?>&page=<?= $page - 1 ?>">« Prev</a></li>
          <?php endif; ?>
          <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $total_pages ?></span></li>
          <?php if ($page < $total_pages): ?>
            <li class="page-item"><a class="page-link" href="?id=<?= $course_id ?>&page=<?= $page + 1 ?>">Next »</a></li>
          <?php endif; ?>
        </ul></nav>
      <?php endif; ?>
    <?php else: ?>
      <p class="text-muted">No comments yet.</p>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
