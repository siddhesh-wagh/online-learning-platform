<?php
include '../includes/auth.php';
include '../db-config.php';

$user_id = $_SESSION['user_id'];
$name    = $_SESSION['name'];
$email   = $_SESSION['email'];
$role    = $_SESSION['role'];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Your Profile</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2>User Profile</h2>
<p><strong>Name:</strong> <?php echo htmlspecialchars($name); ?></p>
<p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
<p><strong>Role:</strong> <?php echo htmlspecialchars($role); ?></p>

<hr>

<?php if ($role === 'learner'): ?>
  <h3>Your Courses & Progress</h3>
  <?php
  $stmt = $conn->prepare("
      SELECT c.title, cp.status, c.id
      FROM course_progress cp
      JOIN courses c ON cp.course_id = c.id
      WHERE cp.user_id = ?
  ");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $status_counts = ['in_progress' => 0, 'completed' => 0];
  while ($row = $result->fetch_assoc()) {
      $status_counts[$row['status']]++;
      echo '<div style="margin-bottom: 10px;">
              <strong>' . htmlspecialchars($row['title']) . '</strong><br>
              Status: ' . ucfirst($row['status']) . '<br>
              <a href="course-view.php?id=' . $row['id'] . '">View</a>
            </div>';
  }

  if ($status_counts['in_progress'] === 0 && $status_counts['completed'] === 0) {
      echo "<p>You haven't enrolled in any courses yet.</p>";
  }
  ?>

  <!-- Chart -->
  <h4>Visual Progress Overview</h4>
  <canvas id="progressChart" width="300" height="300"></canvas>
  <script>
  const ctx = document.getElementById('progressChart').getContext('2d');
  const chart = new Chart(ctx, {
      type: 'doughnut',
      data: {
          labels: ['In Progress', 'Completed'],
          datasets: [{
              label: 'Progress',
              data: [<?php echo $status_counts['in_progress']; ?>, <?php echo $status_counts['completed']; ?>],
              backgroundColor: ['#f39c12', '#2ecc71'],
          }]
      }
  });
  </script>

<?php elseif ($role === 'instructor'): ?>
  <h3>Your Uploaded Courses</h3>
  <?php
  $stmt = $conn->prepare("
      SELECT id, title, description
      FROM courses
      WHERE instructor_id = ?
      ORDER BY created_at DESC
  ");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0):
      while ($row = $result->fetch_assoc()):
  ?>
      <div style="margin-bottom: 10px;">
          <strong><?php echo htmlspecialchars($row['title']); ?></strong><br>
          <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
          <a href="course-view.php?id=<?php echo $row['id']; ?>">View</a> |
          <a href="edit-course.php?id=<?php echo $row['id']; ?>">Edit</a> |
          <a href="delete-course.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this course?');">Delete</a>
      </div>
  <?php endwhile; else: ?>
      <p>You haven't uploaded any courses yet.</p>
  <?php endif; ?>
<?php endif; ?>

<p><a href="dashboard.php">← Back to Dashboard</a></p>

</body>
</html>
