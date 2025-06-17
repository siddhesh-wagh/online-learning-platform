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
