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

// Fetch course
$stmt = $conn->prepare("SELECT c.title, c.description, c.file_path, c.instructor_id, u.name AS instructor_name, u.email AS instructor_email
                        FROM courses c
                        JOIN users u ON c.instructor_id = u.id
                        WHERE c.id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if (!$course) { echo "Course not found."; exit; }

// Track access
$track_stmt = $conn->prepare("INSERT INTO course_progress (user_id, course_id, progress_percent, updated_at)
                              VALUES (?, ?, 0, NOW())
                              ON DUPLICATE KEY UPDATE updated_at = NOW()");
$track_stmt->bind_param("ii", $user_id, $course_id);
$track_stmt->execute();
logAction($conn, $user_id, "Accessed course: " . $course['title']);

// Handle progress update and comments
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['update_progress'])) {
        $percent = (int) $_POST['progress_percent'];
        if ($percent >= 0 && $percent <= 100) {
            $stmt = $conn->prepare("UPDATE course_progress SET progress_percent = ?, updated_at = NOW()
                                    WHERE user_id = ? AND course_id = ?");
            $stmt->bind_param("iii", $percent, $user_id, $course_id);
            $stmt->execute();
            $current_percent = $percent;
            logAction($conn, $user_id, "Updated progress to $percent% on course: " . $course['title']);
        }
    } elseif (isset($_POST['reset_progress'])) {
        $stmt = $conn->prepare("UPDATE course_progress SET progress_percent = 0, updated_at = NOW()
                                WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $current_percent = 0;
        logAction($conn, $user_id, "Reset progress for course: " . $course['title']);
    } elseif (isset($_POST['comment'])) {
        $comment = trim($_POST['comment']);
        if (!empty($comment)) {
            $stmt = $conn->prepare("INSERT INTO comments (course_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $course_id, $user_id, $comment);
            $stmt->execute();
            logAction($conn, $user_id, "Commented on course: " . $course['title']);

            // Notify instructor
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $message = $_SESSION['name'] . " commented on your course: " . $course['title'];
            $notif_stmt->bind_param("is", $course['instructor_id'], $message);
            $notif_stmt->execute();

            include_once '../includes/mailer.php';
            sendEmail(
                $course['instructor_email'],
                "📝 New Comment on Your Course: " . $course['title'],
                "<h3>Hello {$course['instructor_name']},</h3>
                 <p><strong>{$_SESSION['name']}</strong> commented on your course <em>{$course['title']}</em>:</p>
                 <blockquote style='border-left:3px solid #ccc;padding-left:10px;color:#555;'>$comment</blockquote>
                 <p><a href='http://localhost/online-learning-platform/views/course-view.php?id={$course_id}'>View Course</a></p>
                 <small>This is an automated message.</small>"
            );
        }
    }
}

// Get current progress
$progress_stmt = $conn->prepare("SELECT progress_percent FROM course_progress WHERE user_id = ? AND course_id = ?");
$progress_stmt->bind_param("ii", $user_id, $course_id);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();
if ($progress_row = $progress_result->fetch_assoc()) {
    $current_percent = $progress_row['progress_percent'];
}

// Pagination for comments
$comments_result = null;
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
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="mb-0"><?= htmlspecialchars($course['title']) ?></h2>
    <a href="course-list.php" class="btn btn-outline-secondary btn-sm">← Back to Course List</a>
  </div>

  <p><strong>Instructor:</strong> <?= htmlspecialchars($course['instructor_name']) ?></p>
  <p><?= nl2br(htmlspecialchars($course['description'])) ?></p>

  <?php if (!empty($course['file_path'])):
    $file_url = "../" . $course['file_path'];
    $file_ext = pathinfo($file_url, PATHINFO_EXTENSION);
  ?>
    <h4 class="mt-4">Course Material</h4>
    <div class="card mb-4">
      <div class="row g-0">
        <div class="col-md-8 p-3">
          <?php if ($file_ext === 'mp4'): ?>
            <video class="w-100" height="320" controls>
              <source src="<?= $file_url ?>" type="video/mp4">
            </video>
          <?php elseif ($file_ext === 'pdf'): ?>
            <embed src="<?= $file_url ?>" width="100%" height="500px" type="application/pdf">
          <?php else: ?>
            <a href="<?= $file_url ?>" class="btn btn-outline-primary" download>Download File</a>
          <?php endif; ?>
        </div>
        <div class="col-md-4 border-start p-3">
          <h5 class="mb-3">📥 Download</h5>
          <?php
              // Sanitize title for filename (remove spaces/special chars)
              $safe_title = preg_replace("/[^a-zA-Z0-9]/", "", $course['title']);
              $download_name = "CourseMaterial_" . $course_id . "_" . $safe_title . "." . $file_ext;
          ?>
              <a href="<?= $file_url ?>" class="btn btn-success w-100 mb-2" download="<?= $download_name ?>">Download</a>          <div class="mb-3 text-muted">
            Type: <?= strtoupper($file_ext) ?><br>
            Size: <?= round(filesize($file_url) / 1024 / 1024, 2) ?> MB
          </div>
          <hr>
          <h6>📊 Course Progress</h6>
          <div class="progress mb-2" style="height: 20px;">
            <div class="progress-bar bg-<?= $current_percent == 100 ? 'success' : 'info' ?>" 
                 style="width: <?= $current_percent ?>%;">
              <?= $current_percent ?>%
            </div>
          </div>
          <form method="POST" class="d-grid gap-2">
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
        <small class="text-muted">(<?= $comment['created_at'] ?>)</small>
        <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
        <?php
        $reply_stmt = $conn->prepare("SELECT r.reply, r.created_at, u.name AS instructor_name
                                      FROM replies r JOIN users u ON r.instructor_id = u.id 
                                      WHERE r.comment_id = ?");
        $reply_stmt->bind_param("i", $comment['comment_id']);
        $reply_stmt->execute();
        $reply = $reply_stmt->get_result()->fetch_assoc();
        ?>
        <?php if ($reply): ?>
          <div class="ms-3 mt-3 alert alert-secondary">
            <strong><?= htmlspecialchars($reply['instructor_name']) ?> (Instructor):</strong><br>
            <?= nl2br(htmlspecialchars($reply['reply'])) ?>
            <small class="text-muted float-end"><?= $reply['created_at'] ?></small>
          </div>
        <?php endif; ?>
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
</div>
</body>
</html>