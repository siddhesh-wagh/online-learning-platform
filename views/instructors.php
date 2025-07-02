<?php
include '../db-config.php';

$stmt = $conn->query("
    SELECT u.id, u.name, u.email, u.profile_pic, u.bio, COUNT(c.id) AS course_count
    FROM users u
    LEFT JOIN courses c ON c.instructor_id = u.id
    WHERE u.role = 'instructor' AND u.is_approved = 1
    GROUP BY u.id
    ORDER BY u.name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Meet Our Instructors</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .avatar {
      width: 90px;
      height: 90px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid #dee2e6;
    }
    .card {
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    .card-title {
      font-size: 1.25rem;
      margin-bottom: 0.25rem;
    }
    .card-subtitle {
      font-size: 0.9rem;
      color: #6c757d;
    }
    .bio-snippet {
      font-size: 0.9rem;
      color: #555;
    }
    .badge-course {
      font-size: 0.8rem;
      background-color: #0d6efd;
    }
  </style>
</head>
<body>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">👨‍🏫 Meet Our Instructors</h2>
    <a href="../index.php" class="btn btn-outline-secondary">← Back to Home</a>
  </div>

  <?php if ($stmt->num_rows > 0): ?>
    <div class="row g-4">
      <?php while ($inst = $stmt->fetch_assoc()):
        $profile_pic = $inst['profile_pic'] ?: '../assets/default-avatar.png';
        $bio = trim(strip_tags($inst['bio'] ?? ''));
        $bio_snippet = $bio ? (strlen($bio) > 80 ? substr($bio, 0, 80) . '...' : $bio) : 'No bio available.';
      ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center d-flex flex-column align-items-center">
              <!-- Centered Profile Picture -->
              <div class="mb-3">
                <img src="<?= htmlspecialchars($profile_pic) ?>" class="avatar" alt="Avatar of <?= htmlspecialchars($inst['name']) ?>">
              </div>
              <h5 class="card-title"><?= htmlspecialchars($inst['name']) ?></h5>
              <div class="card-subtitle mb-2"><?= htmlspecialchars($inst['email']) ?></div>
              <p class="bio-snippet mb-2"><?= htmlspecialchars($bio_snippet) ?></p>
              <span class="badge badge-course mb-3">📚 <?= $inst['course_count'] ?> course<?= $inst['course_count'] == 1 ? '' : 's' ?></span>
              <div class="d-grid gap-2 w-100 mt-auto">
                <a href="instructor-profile.php?id=<?= $inst['id'] ?>" class="btn btn-sm btn-outline-primary">👤 View Profile</a>
                <a href="../views/course-list.php?instructor=<?= $inst['id'] ?>" class="btn btn-sm btn-primary">🎓 View Courses</a>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="alert alert-info text-center">No instructors available at the moment.</div>
  <?php endif; ?>
</div>

</body>
</html>
