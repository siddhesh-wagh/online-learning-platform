<?php
session_start();
include '../db-config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo 0;
    exit;
}

$uid = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $uid);
$stmt->execute();
echo $stmt->get_result()->fetch_assoc()['unread'];
