<?php
include '../includes/auth.php';
include '../db-config.php';

if ($_SESSION['role'] !== 'instructor') exit("Access denied");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) exit("Invalid ID");

$course_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// First, fetch file path to optionally delete the uploaded file
$stmt = $conn->prepare("SELECT file_path FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $file = "../" . $row['file_path'];
    if (file_exists($file)) {
        unlink($file); // delete file
    }

    // Now delete course
    $delete = $conn->prepare("DELETE FROM courses WHERE id = ? AND instructor_id = ?");
    $delete->bind_param("ii", $course_id, $user_id);
    $delete->execute();
}

header("Location: user-profile.php");
exit;
