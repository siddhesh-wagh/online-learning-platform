<?php
include '../includes/auth.php';
include '../db-config.php';

if ($_SESSION['role'] !== 'learner') {
    echo "Access denied.";
    exit;
}

$sql = "SELECT c.id, c.title, c.description, u.name AS instructor_name 
        FROM courses c
        JOIN users u ON c.instructor_id = u.id
        ORDER BY c.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Available Courses</title>
</head>
<body>

<h2>Available Courses</h2>
<p><a href="dashboard.php">← Back to Dashboard</a></p>

<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
            <small>Instructor: <?php echo htmlspecialchars($row['instructor_name']); ?></small><br><br>
            <a href="course-view.php?id=<?php echo $row['id']; ?>">View Course</a>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>No courses available.</p>
<?php endif; ?>

</body>
</html>
