<?php
include '../db-config.php';

$course_id = (int)($_GET['course_id'] ?? 0);

$stmt = $conn->prepare("
  SELECT u.name, u.email 
  FROM course_progress cp 
  JOIN users u ON cp.user_id = u.id 
  WHERE cp.course_id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$students = $stmt->get_result();

$student_count = $students->num_rows;
echo "<p><strong>Total Enrolled:</strong> $student_count</p>";

if ($student_count > 0) {
  echo "<ul class='list-group small'>";
  while ($s = $students->fetch_assoc()) {
    echo "<li class='list-group-item'>";
    echo htmlspecialchars($s['name']) . " <small class='text-muted float-end'>" . htmlspecialchars($s['email']) . "</small>";
    echo "</li>";
  }
  echo "</ul>";
} else {
  echo "<p class='text-muted'>No students enrolled yet.</p>";
}
?>
