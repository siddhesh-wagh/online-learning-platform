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

$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $course_id, $instructor_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    echo "Course not found or access denied.";
    exit;
}

// Log view
logAction($conn, $instructor_id, "Previewed course: " . $course['title']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Preview: <?= htmlspecialchars($course['title']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .course-header {
        background: #f8f9fa;
        border-left: 6px solid #0d6efd;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .course-material {
        background: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        padding: 2rem;
    }
    .file-icon {
        font-size: 1.5rem;
        margin-right: 8px;
    }
  </style>
</head>
<body class="bg-light">

<div class="container my-5">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="fw-bold mb-1">👨‍🏫 <?= htmlspecialchars($course['title']) ?></h2>
      <p class="text-muted mb-0">Course Preview</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
  </div>

  <div class="course-header mb-4">
    <p class="mb-0 fs-5"><?= nl2br(htmlspecialchars($course['description'])) ?></p>
  </div>

  <?php if (!empty($course['file_path'])): ?>
    <?php
      $file_url = "../" . $course['file_path'];
      $file_ext = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
    ?>
    <div class="course-material">
      <h5 class="mb-4">
        📂 Course Material
        <span class="badge bg-secondary text-uppercase ms-2"><?= strtoupper($file_ext) ?></span>
      </h5>

      <?php if ($file_ext === 'mp4'): ?>
        <video class="w-100 rounded shadow-sm" height="400" controls>
          <source src="<?= $file_url ?>" type="video/mp4">
          Your browser does not support the video tag.
        </video>

      <?php elseif ($file_ext === 'pdf'): ?>
        <embed src="<?= $file_url ?>" type="application/pdf" width="100%" height="600px" class="rounded border" />

      <?php else: ?>
        <div class="d-flex align-items-center">
          <span class="file-icon">📎</span>
          <a href="<?= $file_url ?>" class="btn btn-primary" download>Download File</a>
        </div>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <div class="alert alert-warning mt-4">No course file has been uploaded yet.</div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
