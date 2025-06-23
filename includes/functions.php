<?php
/**
 * Logs a user action to the logs table
 * 
 * @param mysqli $conn    Database connection object
 * @param int    $user_id User ID performing the action
 * @param string $action  Description of the action
 */
function logAction($conn, $user_id, $action) {
    if (!$conn instanceof mysqli || !$user_id || !$action) return;

    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, created_at) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Logs a user login
 */
function logLogin($conn, $user_id) {
    logAction($conn, $user_id, "Logged in");
}

/**
 * Logs a new course creation
 */
function logCourseCreation($conn, $user_id, $course_title) {
    logAction($conn, $user_id, "Created new course: \"$course_title\"");
}

/**
 * Logs a profile update
 */
function logProfileUpdate($conn, $user_id) {
    logAction($conn, $user_id, "Updated profile details");
}

/**
 * Logs a password change
 */
function logPasswordChange($conn, $user_id) {
    logAction($conn, $user_id, "Changed password");
}

/**
 * Logs an instructor's reply to a comment
 */
function logRepliedToComment($conn, $user_id, $course_id) {
    logAction($conn, $user_id, "Replied to a comment on course ID: $course_id");
}

/**
 * Logs a new user registration
 */
function logNewRegistration($conn, $user_id) {
    logAction($conn, $user_id, "New account registered");
}

/**
 * Logs when a learner views a course
 */
function logViewedCourse($conn, $user_id, $course_id) {
    logAction($conn, $user_id, "Viewed course ID: $course_id");
}

/**
 * Logs when a learner posts a comment
 */
function logPostedComment($conn, $user_id, $course_id) {
    logAction($conn, $user_id, "Posted comment on course ID: $course_id");
}

/**
 * Logs when a learner updates progress
 */
function logProgressUpdate($conn, $user_id, $course_id, $percent) {
    logAction($conn, $user_id, "Updated course ID: $course_id progress to $percent%");
}

/**
 * Logs when a learner resets progress
 */
function logProgressReset($conn, $user_id, $course_id) {
    logAction($conn, $user_id, "Reset progress on course ID: $course_id");
}
?>
