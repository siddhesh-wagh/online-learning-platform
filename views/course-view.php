<?php
include '../includes/auth.php';
include '../db-config.php';

if ($_SESSION['role'] !== 'learner') {
    echo "Access denied.";
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid course ID.";
    exit;
}

$course_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch course details
$stmt = $conn->prepare("SELECT c.title, c.description, c.file_path, u.name AS instructor_name, u.email AS instructor_email
                        FROM courses c
                        JOIN users u ON c.instructor_id = u.id
                        WHERE c.id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "Course not found.";
    exit;
}
$course = $result->fetch_assoc();

// Handle course completion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['mark_complete'])) {
    $progress_check = $conn->prepare("SELECT status FROM course_progress WHERE user_id = ? AND course_id = ?");
    $progress_check->bind_param("ii", $user_id, $course_id);
    $progress_check->execute();
    $progress_result = $progress_check->get_result();
    $progress = $progress_result->fetch_assoc();

    if (!$progress || $progress['status'] !== 'completed') {
        $update_stmt = $conn->prepare("
            INSERT INTO course_progress (user_id, course_id, status)
            VALUES (?, ?, 'completed')
            ON DUPLICATE KEY UPDATE status = 'completed'
        ");
        $update_stmt->bind_param("ii", $user_id, $course_id);
        $update_stmt->execute();
    }
}

// Handle comment submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (course_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $course_id, $user_id, $comment);
        $stmt->execute();

        // 📨 Email notification (placeholder)
        /*
        $to = $course['instructor_email'];
        $subject = "New Comment on Your Course: " . $course['title'];
        $message = $_SESSION['name'] . " commented: \n\n" . $comment;
        $headers = "From: noreply@yourdomain.com";
        mail($to, $subject, $message, $headers);
        */
    }
}

// Pagination for comments
$comments_per_page = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $comments_per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM comments WHERE course_id = ?");
$count_stmt->bind_param("i", $course_id);
$count_stmt->execute();
$total_comments = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_comments / $comments_per_page);

// Fetch comments for current page
$stmt = $conn->prepare("
    SELECT c.content, c.created_at, u.name 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.course_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $course_id, $comments_per_page, $offset);
$stmt->execute();
$comments_result = $stmt->get_result();

// Course progress
$progress_stmt = $conn->prepare("SELECT status FROM course_progress WHERE user_id = ? AND course_id = ?");
$progress_stmt->bind_param("ii", $user_id, $course_id);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();
$progress = $progress_result->fetch_assoc();
$current_status = $progress['status'] ?? 'in_progress';
?>

<!DOCTYPE html>
<html>
<head>
  <title><?php echo htmlspecialchars($course['title']); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

  <h2 class="mb-2"><?php echo htmlspecialchars($course['title']); ?></h2>
  <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
  <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>

  <?php if (!empty($course['file_path'])): 
      $file_url = "../" . $course['file_path'];
      $file_ext = pathinfo($file_url, PATHINFO_EXTENSION);
  ?>

  <h4 class="mt-4">Course Material</h4>
  <div class="row mb-4">
    <div class="col-md-8">
      <?php if ($file_ext === 'mp4'): ?>
        <video class="w-100" height="320" controls>
          <source src="<?php echo $file_url; ?>" type="video/mp4">
          Your browser does not support HTML5 video. <a href="<?php echo $file_url; ?>" download>Download Video</a>
        </video>
      <?php elseif ($file_ext === 'pdf'): ?>
        <embed src="<?php echo $file_url; ?>" width="100%" height="500px" type="application/pdf"
               onerror="this.outerHTML='<p>Browser cannot display PDF. <a href=\'<?php echo $file_url; ?>\'>Download instead</a></p>';">
        </embed>
      <?php else: ?>
        <a href="<?php echo $file_url; ?>" class="btn btn-outline-primary" download>Download File</a>
      <?php endif; ?>
    </div>
    <div class="col-md-4">
      <h5>Download</h5>
      <a href="<?php echo $file_url; ?>" class="btn btn-success mb-2" download>📥 Download</a>
      <div class="text-muted">
        Type: <?php echo strtoupper($file_ext); ?><br>
        Size: <?php echo round(filesize($file_url) / 1024 / 1024, 2); ?> MB
      </div>
    </div>
  </div>
  <?php else: ?>
    <p><em>No course file uploaded.</em></p>
  <?php endif; ?>

  <hr>
  <h4>Discussion / Comments</h4>

  <form method="POST" class="mb-4">
    <div class="mb-3">
      <textarea name="comment" class="form-control" rows="4" required placeholder="Write your comment..."></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Post Comment</button>
  </form>

  <?php if ($comments_result->num_rows > 0): ?>
    <?php while ($comment = $comments_result->fetch_assoc()): ?>
      <div class="border rounded p-3 mb-3 bg-white">
        <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
        <small class="text-muted">(<?php echo $comment['created_at']; ?>)</small>
        <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
      </div>
    <?php endwhile; ?>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <nav>
        <ul class="pagination">
          <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?id=<?php echo $course_id; ?>&page=<?php echo $page - 1; ?>">« Prev</a></li>
          <?php endif; ?>
          <li class="page-item disabled"><span class="page-link">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span></li>
          <?php if ($page < $total_pages): ?>
            <li class="page-item"><a class="page-link" href="?id=<?php echo $course_id; ?>&page=<?php echo $page + 1; ?>">Next »</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>
    
  <?php else: ?>
    <p>No comments yet. Be the first to comment!</p>
  <?php endif; ?>

  <hr>
  <h4>Course Progress</h4>
  <p>Status: <strong><?php echo ucfirst($current_status); ?></strong></p>
  <?php if ($current_status !== 'completed'): ?>
    <form method="POST">
      <button class="btn btn-outline-success" name="mark_complete">Mark as Completed</button>
    </form>
  <?php else: ?>
    <p class="text-success">✅ You have completed this course!</p>
  <?php endif; ?>

  <p class="mt-4"><a href="course-list.php" class="btn btn-secondary">← Back to Course List</a></p>

</div>
</body>
</html>
