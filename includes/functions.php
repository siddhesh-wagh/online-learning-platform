<?php
// Prevent re-inclusion
if (!function_exists('logAction')) {

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

    function logLogin($conn, $user_id) {
        logAction($conn, $user_id, "Logged in");
    }

    function logCourseCreation($conn, $user_id, $course_title) {
        logAction($conn, $user_id, "Created new course: \"$course_title\"");
    }

    function logProfileUpdate($conn, $user_id) {
        logAction($conn, $user_id, "Updated profile details");
    }

    function logPasswordChange($conn, $user_id) {
        logAction($conn, $user_id, "Changed password");
    }

    function logRepliedToComment($conn, $user_id, $course_id) {
        logAction($conn, $user_id, "Replied to a comment on course ID: $course_id");
    }

    function logNewRegistration($conn, $user_id) {
        logAction($conn, $user_id, "New account registered");
    }

    function logViewedCourse($conn, $user_id, $course_id) {
        logAction($conn, $user_id, "Viewed course ID: $course_id");
    }

    function logPostedComment($conn, $user_id, $course_id) {
        logAction($conn, $user_id, "Posted comment on course ID: $course_id");
    }

    function logProgressUpdate($conn, $user_id, $course_id, $percent) {
        logAction($conn, $user_id, "Updated course ID: $course_id progress to $percent%");
    }

    function logProgressReset($conn, $user_id, $course_id) {
        logAction($conn, $user_id, "Reset progress on course ID: $course_id");
    }
}
