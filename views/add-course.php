<?php
include '../includes/auth.php';
include '../db-config.php';
include '../includes/functions.php';

if ($_SESSION['role'] !== 'instructor') {
    echo "<div class='alert alert-danger text-center my-5'>⛔ Access Denied. Instructors only.</div>";
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title']);
    $description   = trim($_POST['description']);
    $instructor_id = $_SESSION['user_id'];

    $file_path = null;
    $thumbnail_path = null;

    // Handle Course File Upload
    if (!empty($_FILES['course_file']['name'])) {
        $allowed_types = ['application/pdf', 'video/mp4'];
        $file_type = $_FILES['course_file']['type'];
        $file_size = $_FILES['course_file']['size'];
        $file_tmp  = $_FILES['course_file']['tmp_name'];

        $max_size = 10 * 1024 * 1024;
        $file_name = time() . '_' . basename($_FILES['course_file']['name']);
        $target_file = "../uploads/" . $file_name;

        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
            if (move_uploaded_file($file_tmp, $target_file)) {
                $file_path = "uploads/" . $file_name;
            } else {
                $message = "<div class='alert alert-danger'>❌ Failed to upload course file.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>❌ Invalid file type or file too large (max 10MB, only PDF/MP4 allowed).</div>";
        }
    }

    // Handle Thumbnail Upload
    if (!empty($_FILES['thumbnail']['name'])) {
        $thumb_type = $_FILES['thumbnail']['type'];
        $thumb_tmp  = $_FILES['thumbnail']['tmp_name'];
        $allowed_img = ['image/jpeg', 'image/png'];

        $thumb_name = time() . '_' . basename($_FILES['thumbnail']['name']);
        $thumb_path = "../uploads/thumbnails/" . $thumb_name;

        if (in_array($thumb_type, $allowed_img)) {
            if (!is_dir("../uploads/thumbnails")) {
                mkdir("../uploads/thumbnails", 0777, true);
            }
            if (move_uploaded_file($thumb_tmp, $thumb_path)) {
                $thumbnail_path = "uploads/thumbnails/" . $thumb_name;
            } else {
                $message = "<div class='alert alert-danger'>❌ Failed to upload thumbnail.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>❌ Invalid thumbnail format. Only JPG/PNG allowed.</div>";
        }
    }

    if (empty($message)) {
        $stmt = $conn->prepare("INSERT INTO courses (instructor_id, title, description, file_path, thumbnail_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $instructor_id, $title, $description, $file_path, $thumbnail_path);

        if ($stmt->execute()) {
            logAction($conn, $instructor_id, "Created course: $title");
            $message = "<div class='alert alert-success'>✅ Course <strong>" . htmlspecialchars($title) . "</strong> added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>❌ Error: " . $stmt->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add New Course</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .thumbnail-preview {
      max-width: 100%;
      height: auto;
      border: 1px solid #ccc;
      border-radius: 8px;
      margin-top: 10px;
    }
  </style>
</head>
<body class="bg-light">

<div class="container my-5">
  <div class="text-center mb-4">
    <h2 class="fw-bold">📚 Create a New Course</h2>
    <p class="text-muted">Upload your course content and thumbnail image below.</p>
  </div>

  <?= $message ?>

  <form method="POST" enctype="multipart/form-data" class="card p-4 shadow border-0 rounded-4 bg-white needs-validation" novalidate>
    <div class="row g-4">
      <div class="col-md-12">
        <label for="title" class="form-label">📘 Course Title</label>
        <input type="text" id="title" name="title" class="form-control form-control-lg" placeholder="e.g., Mastering React" required>
        <div class="invalid-feedback">Course title is required.</div>
      </div>

      <div class="col-md-12">
        <label for="description" class="form-label">📝 Description</label>
        <textarea id="description" name="description" class="form-control form-control-lg" rows="4" required placeholder="Write a brief description..."></textarea>
        <div class="invalid-feedback">Description cannot be empty.</div>
      </div>

      <div class="col-md-6">
        <label for="course_file" class="form-label">📎 Upload Course File (PDF/MP4)</label>
        <input type="file" id="course_file" name="course_file" class="form-control" accept=".pdf,.mp4" required>
        <div class="form-text">Max 10MB. Allowed formats: PDF or MP4.</div>
        <div class="invalid-feedback">Please upload a course file (PDF/MP4).</div>
      </div>

      <div class="col-md-6">
        <label for="thumbnail" class="form-label">🖼️ Thumbnail (JPG/PNG)</label>
        <input type="file" id="thumbnail" name="thumbnail" class="form-control" accept=".jpg,.jpeg,.png" onchange="previewThumbnail(this)">
        <div class="form-text">Recommended 1280x720px. JPG or PNG only.</div>
        <img id="thumbPreview" class="thumbnail-preview d-none" alt="Thumbnail Preview">
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4">
      <a href="dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
      <button type="submit" class="btn btn-primary px-4">➕ Submit Course</button>
    </div>
  </form>
</div>

<script>
  // Bootstrap form validation
  (() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
      form.addEventListener('submit', e => {
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
        }
        form.classList.add('was-validated');
      });
    });
  })();

  // Thumbnail preview
  function previewThumbnail(input) {
    const file = input.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const img = document.getElementById('thumbPreview');
        img.src = e.target.result;
        img.classList.remove('d-none');
      };
      reader.readAsDataURL(file);
    }
  }
</script>

</body>
</html>
