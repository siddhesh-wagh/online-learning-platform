<?php
include '../db-config.php';

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
    // Mark user as verified
    $update = $conn->prepare("UPDATE users SET is_verified = 1, verify_token = NULL WHERE verify_token = ?");
    $update->bind_param("s", $token);
    $update->execute();

    echo "✅ Your account has been verified. <a href='login.php'>Login now</a>";
} else {
    echo "❌ Verification failed or token already used.";
}
