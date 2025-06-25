<?php
include '../includes/auth.php';
include '../db-config.php';
include '../includes/functions.php';

if ($_SESSION['role'] !== 'instructor') {
    echo "Access denied.";
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid course ID.";
    exit;
}

$course_id = (int) $_GET['id'];
$instructor_id = $_SESSION['user_id'];

// Fetch course for this instructor only
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $course_id, $instructor_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    echo "Course not found or access denied.";
    exit;
}

// Optional: Log action
logAction($conn, $instructor_id, "Previewed course: " . $course['title']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Preview: <?= htmlspecialchars($course['title']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">👨‍🏫 Preview: <?= htmlspecialchars($course['title']) ?></h2>
    <a href="dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
  </div>

  <p class="lead"><?= nl2br(htmlspecialchars($course['description'])) ?></p>

  <?php if (!empty($course['file_path'])): ?>
    <?php
      $file_url = "../" . $course['file_path'];
      $file_ext = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
    ?>
    <div class="card shadow-sm p-4">
      <h5 class="mb-3">📂 Course Material</h5>
      <?php if ($file_ext === 'mp4'): ?>
        <video class="w-100" height="400" controls>
          <source src="<?= $file_url ?>" type="video/mp4">
          Your browser does not support the video tag.
        </video>
      <?php elseif ($file_ext === 'pdf'): ?>
        <embed src="<?= $file_url ?>" type="application/pdf" width="100%" height="600px">
      <?php else: ?>
        <a href="<?= $file_url ?>" class="btn btn-primary" download>Download File</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="alert alert-warning mt-4">No course file uploaded.</div>
  <?php endif; ?>
</div>

</body>
</html>
