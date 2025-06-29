<?php
session_start();
require_once __DIR__ . '/../db-config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    logAction($conn, $user_id, "Logged out");
}

session_unset();
session_destroy();

header("Location: login.php");
exit;
