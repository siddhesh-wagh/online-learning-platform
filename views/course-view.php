<?php
include '../includes/auth.php';
include '../db-config.php';

// Only learners allowed
if ($_SESSION['role'] !== 'learner') {
    echo "Access denied.";
    exit;
}

// Validate course ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid course ID.";
    exit;
}

$course_id = $_GET['id'];

// Fetch course details
$stmt = $conn->prepare("SELECT c.title, c.description, c.file_path, u.name AS instructor_name 
                        FROM courses c
                        JOIN users u ON c.instructor_id = u.id
                        WHERE c.id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $course = $result->fetch_assoc();
} else {
    echo "Course not found.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title><?php echo htmlspecialchars($course['title']); ?></title>
</head>
<body>

<h2><?php echo htmlspecialchars($course['title']); ?></h2>
<p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
<p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>

<?php if (!empty($course['file_path'])): ?>
    <p>
        <strong>Download File:</strong><br>
        <a href="../<?php echo $course['file_path']; ?>" download>
            <?php echo basename($course['file_path']); ?>
        </a>
    </p>
<?php else: ?>
    <p><em>No course file uploaded.</em></p>
<?php endif; ?>

<p><a href="course-list.php">← Back to Courses</a></p>

</body>
</html>
<hr>
<h3>Discussion / Comments</h3>

<!-- Comment Form -->
<form method="POST" action="">
  <textarea name="comment" rows="4" cols="50" required placeholder="Write your comment here..."></textarea><br>
  <button type="submit">Post Comment</button>
</form>
<br>

<?php
// Handle comment submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];

    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (course_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $course_id, $user_id, $comment);
        $stmt->execute();
    }
}

// Fetch and display comments
$stmt = $conn->prepare("SELECT c.content, c.created_at, u.name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.course_id = ? ORDER BY c.created_at DESC");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$comments_result = $stmt->get_result();

if ($comments_result->num_rows > 0):
    while ($comment = $comments_result->fetch_assoc()):
?>
    <div style="border: 1px solid #ccc; margin: 10px 0; padding: 10px;">
        <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
        <small>(<?php echo $comment['created_at']; ?>)</small>
        <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
    </div>
<?php endwhile; else: ?>
    <p>No comments yet. Be the first to comment!</p>
<?php endif; ?>
