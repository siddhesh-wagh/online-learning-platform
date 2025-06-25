<?php
include '../includes/auth.php';
include '../db-config.php';
include '../includes/functions.php'; // for logAction()

if ($_SESSION['role'] !== 'instructor') {
    exit("Access denied");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit("Invalid ID");
}

$course_id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get file & thumbnail path
$stmt = $conn->prepare("SELECT title, file_path, thumbnail_path FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $title = $row['title'];

    // Delete course file
    if (!empty($row['file_path'])) {
        $file_path = "../" . $row['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Delete thumbnail
    if (!empty($row['thumbnail_path'])) {
        $thumb_path = "../" . $row['thumbnail_path'];
        if (file_exists($thumb_path)) {
            unlink($thumb_path);
        }
    }

    // Delete course
    $delete = $conn->prepare("DELETE FROM courses WHERE id = ? AND instructor_id = ?");
    $delete->bind_param("ii", $course_id, $user_id);
    $delete->execute();

    logAction($conn, $user_id, "Deleted course: $title"); // ✅ Log action
}

header("Location: dashboard.php");
exit;
