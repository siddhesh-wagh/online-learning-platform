<?php
include '../db-config.php';
include_once '../includes/functions.php'; // ✅ Include logging utilities

if (!isset($_GET['token'])) {
    echo "❌ Invalid verification link.";
    exit;
}

$token = $_GET['token'];

// Check if token exists before updating
$check = $conn->prepare("SELECT id FROM users WHERE verify_token = ?");
$check->bind_param("s", $token);
$check->execute();
$check_result = $check->get_result();

if ($check_result->num_rows === 1) {
    $user = $check_result->fetch_assoc();
    $user_id = $user['id'];

    // ✅ Mark user as verified
    $update = $conn->prepare("UPDATE users SET is_verified = 1, verify_token = NULL WHERE verify_token = ?");
    $update->bind_param("s", $token);
    $update->execute();

    // ✅ Log the verification
    logAction($conn, $user_id, "Verified email");

    echo "✅ Your account has been verified. <a href='login.php'>Login now</a>";
} else {
    echo "❌ Verification failed or token already used.";
}
