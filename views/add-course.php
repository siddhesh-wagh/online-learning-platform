<?php
include '../includes/auth.php';
include '../db-config.php';

// Only instructors can access
if ($_SESSION['role'] !== 'instructor') {
    echo "Access denied.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title         = trim($_POST['title']);
    $description   = trim($_POST['description']);
    $instructor_id = $_SESSION['user_id'];
    $file_path     = null;

    // Handle file upload with validation
    if (!empty($_FILES['course_file']['name'])) {
        $allowed_types = ['application/pdf', 'video/mp4'];
        $max_size = 10 * 1024 * 1024; // 10MB

        $file_type = $_FILES['course_file']['type'];
        $file_size = $_FILES['course_file']['size'];
        $file_tmp  = $_FILES['course_file']['tmp_name'];

        $target_dir = "../uploads/";
        $file_name = time() . '_' . basename($_FILES['course_file']['name']);
        $target_file = $target_dir . $file_name;

        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
            if (move_uploaded_file($file_tmp, $target_file)) {
                $file_path = "uploads/" . $file_name;
            } else {
                echo "❌ Failed to move uploaded file.";
                exit;
            }
        } else {
            echo "❌ Invalid file type or size (Max 10MB, only PDF or MP4 allowed).";
            exit;
        }
    }

    // Insert course
    $stmt = $conn->prepare("INSERT INTO courses (instructor_id, title, description, file_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $instructor_id, $title, $description, $file_path);

    if ($stmt->execute()) {
        echo "✅ Course added successfully.";
    } else {
        echo "❌ Error: " . $stmt->error;
    }
}
?>

<h2>Add New Course</h2>

<form method="POST" enctype="multipart/form-data">
  <label>Course Title:</label><br>
  <input type="text" name="title" required><br><br>

  <label>Description:</label><br>
  <textarea name="description" required></textarea><br><br>

  <label>Upload File (PDF or MP4 only):</label><br>
  <input type="file" name="course_file"><br><br>

  <button type="submit">Add Course</button>
</form>

<p><a href="dashboard.php">← Back to Dashboard</a></p>
