<?php
include '../db-config.php';

$stmt = $conn->query("
    SELECT u.id, u.name, u.email, COUNT(c.id) AS course_count
    FROM users u
    LEFT JOIN courses c ON c.instructor_id = u.id
    WHERE u.role = 'instructor' AND u.is_approved = 1
    GROUP BY u.id
    ORDER BY u.name ASC
");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Instructors</title>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

  <h2 class="mb-4">👨‍🏫 Meet Our Instructors</h2>
  <a href="../index.php" class="btn btn-secondary mb-3">← Back to Home</a>

  <?php if ($stmt->num_rows > 0): ?>
    <div class="row">
      <?php while ($inst = $stmt->fetch_assoc()): ?>
        <div class="col-md-4 mb-4">
          <div class="card shadow-sm">
            <div class="card-body">
              <h5><?= htmlspecialchars($inst['name']) ?></h5>
              <p class="text-muted"><?= htmlspecialchars($inst['email']) ?></p>
              <p><strong>Courses Published:</strong> <?= $inst['course_count'] ?></p>
              <!-- Optional: Filter by instructor courses -->
              <a href="../views/course-list.php?instructor=<?= $inst['id'] ?>" class="btn btn-primary btn-sm">View Courses</a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p class="alert alert-info">No instructors available at the moment.</p>
  <?php endif; ?>

</div>
</body>
</html>
