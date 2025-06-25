<?php
include '../includes/auth.php';
include '../db-config.php';
include '../includes/functions.php';

if ($_SESSION['role'] !== 'instructor') {
    exit("Access denied");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit("Invalid ID");
}

$course_id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get course details
$stmt = $conn->prepare("SELECT title, file_path, thumbnail_path FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $title = $row['title'];

    // Delete files
    if (!empty($row['file_path'])) {
        $file_path = "../" . $row['file_path'];
        if (file_exists($file_path)) unlink($file_path);
    }

    if (!empty($row['thumbnail_path'])) {
        $thumb_path = "../" . $row['thumbnail_path'];
        if (file_exists($thumb_path)) unlink($thumb_path);
    }

    // 🧹 Delete replies (need to collect comment IDs first)
    $comment_ids = [];
    $get_comments = $conn->prepare("SELECT id FROM comments WHERE course_id = ?");
    $get_comments->bind_param("i", $course_id);
    $get_comments->execute();
    $comment_result = $get_comments->get_result();
    while ($c = $comment_result->fetch_assoc()) {
        $comment_ids[] = $c['id'];
    }

    if (!empty($comment_ids)) {
        $in_clause = implode(',', array_fill(0, count($comment_ids), '?'));
        $types = str_repeat('i', count($comment_ids));
        $del_replies = $conn->prepare("DELETE FROM replies WHERE comment_id IN ($in_clause)");
        $del_replies->bind_param($types, ...$comment_ids);
        $del_replies->execute();
    }

    // 🧹 Delete comments
    $del_comments = $conn->prepare("DELETE FROM comments WHERE course_id = ?");
    $del_comments->bind_param("i", $course_id);
    $del_comments->execute();

    // 🧹 Delete course progress
    $del_progress = $conn->prepare("DELETE FROM course_progress WHERE course_id = ?");
    $del_progress->bind_param("i", $course_id);
    $del_progress->execute();

    // 🧹 Delete course itself
    $del_course = $conn->prepare("DELETE FROM courses WHERE id = ? AND instructor_id = ?");
    $del_course->bind_param("ii", $course_id, $user_id);
    $del_course->execute();

    logAction($conn, $user_id, "Deleted course: $title");
}

header("Location: dashboard.php");
exit;
