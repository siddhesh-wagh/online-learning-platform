<?php
include '../includes/auth.php';
include '../db-config.php';

if ($_SESSION['role'] !== 'instructor') exit("Access denied");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) exit("Invalid course ID");

$course_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch course
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) exit("Course not found or access denied.");

$success = false;
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);

    if ($title && $desc) {
        $update = $conn->prepare("UPDATE courses SET title = ?, description = ? WHERE id = ? AND instructor_id = ?");
        $update->bind_param("ssii", $title, $desc, $course_id, $user_id);
        if ($update->execute()) {
            $success = true;
            $course['title'] = $title;
            $course['description'] = $desc;
        } else {
            $error = "❌ Update failed.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Course</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">✏️ Edit Course</h2>
    <a href="user-profile.php" class="btn btn-outline-secondary">← Back to Profile</a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">✅ Course updated successfully.</div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <div class="card shadow-sm p-4">
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Course Title</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($course['title']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($course['description']) ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary">💾 Update Course</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
