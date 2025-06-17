<?php
include '../includes/auth.php';
include '../db-config.php';

if ($_SESSION['role'] !== 'instructor') exit("Access denied");

// Validate course ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) exit("Invalid course ID");

$course_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch course
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) exit("Course not found or access denied.");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);

    $update = $conn->prepare("UPDATE courses SET title = ?, description = ? WHERE id = ? AND instructor_id = ?");
    $update->bind_param("ssii", $title, $desc, $course_id, $user_id);
    if ($update->execute()) {
        header("Location: user-profile.php");
        exit;
    } else {
        echo "❌ Update failed.";
    }
}
?>

<h2>Edit Course</h2>
<form method="POST">
  <label>Title:</label><br>
  <input type="text" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required><br><br>

  <label>Description:</label><br>
  <textarea name="description" required><?php echo htmlspecialchars($course['description']); ?></textarea><br><br>

  <button type="submit">Update Course</button>
</form>

<p><a href="user-profile.php">← Back to Profile</a></p>
