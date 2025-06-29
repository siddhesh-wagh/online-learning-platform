<?php
include '../db-config.php';

$course_id = (int)($_GET['course_id'] ?? 0);

$stmt = $conn->prepare("
  SELECT com.id, com.content, com.created_at, u.name 
  FROM comments com 
  JOIN users u ON com.user_id = u.id 
  WHERE com.course_id = ? 
  ORDER BY com.created_at DESC
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$comments = $stmt->get_result();

if ($comments->num_rows > 0) {
  echo "<ul class='list-group'>";
  while ($com = $comments->fetch_assoc()) {
    echo "<li class='list-group-item'>";
    echo "<strong>" . htmlspecialchars($com['name']) . "</strong><br>";
    echo nl2br(htmlspecialchars($com['content']));
    echo "<small class='float-end text-muted'>" . $com['created_at'] . "</small>";
    echo "</li>";
  }
  echo "</ul>";
} else {
  echo "<p class='text-muted'>No comments for this course.</p>";
}
?>
