<?php
/**
 * Logs a user action to the logs table
 * @param mysqli $conn       DB connection
 * @param int    $user_id    User ID performing the action
 * @param string $action     Action description
 */
function logAction($conn, $user_id, $action) {
    if (!$user_id || !$action) return;

    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $action);
    $stmt->execute();
}

/**
 * Logs login activity
 */
function logLogin($conn, $user_id) {
    logAction($conn, $user_id, "Logged in");
}

/**
 * Logs when a course is created
 */
function logCourseCreation($conn, $user_id, $course_title) {
    logAction($conn, $user_id, "Created new course: \"$course_title\"");
}

/**
 * Logs profile update
 */
function logProfileUpdate($conn, $user_id) {
    logAction($conn, $user_id, "Updated profile details");
}

/**
 * Logs password change
 */
function logPasswordChange($conn, $user_id) {
    logAction($conn, $user_id, "Changed password");
}

/**
 * Logs comment replies
 */
function logRepliedToComment($conn, $user_id, $course_id) {
    logAction($conn, $user_id, "Replied to a comment on Course ID: $course_id");
}

/**
 * Logs new user registration (optional if admin creates user)
 */
function logNewRegistration($conn, $user_id) {
    logAction($conn, $user_id, "New account registered");
}
?>
