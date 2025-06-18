<?php
include '../includes/auth.php';
include '../db-config.php';

$user_id = $_SESSION['user_id'];

// Fetch latest user info
$stmt = $conn->prepare("SELECT name, email, role, created_at, bio, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$msg = "";

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = htmlspecialchars(trim($_POST['name']));
    $new_bio  = htmlspecialchars(trim($_POST['bio']));
    $upload_path = $user['profile_pic'];

    if (!empty($_FILES['profile_pic']['name'])) {
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $filename = time() . "_$user_id.$ext";
            $target = "../uploads/profile_pics/$filename";
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
                $upload_path = "uploads/profile_pics/$filename";
            }
        }
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, bio = ?, profile_pic = ? WHERE id = ?");
    $stmt->bind_param("sssi", $new_name, $new_bio, $upload_path, $user_id);
    if ($stmt->execute()) {
        $_SESSION['name'] = $new_name;
        header("Location: user-profile.php");
        exit;
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_pass'])) {
    $current = $_POST['current_pass'];
    $new     = $_POST['new_pass'];

    $check = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $hashed = $check->get_result()->fetch_assoc()['password'];

    if (password_verify($current, $hashed)) {
        $new_hashed = password_hash($new, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $up->bind_param("si", $new_hashed, $user_id);
        $up->execute();
        $msg = "<div class='alert alert-success'>✅ Password updated!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>❌ Incorrect current password.</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>My Profile</title>
  <meta charset="UTF-8" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<div class="container py-5">
  <h2 class="mb-4">👤 My Profile</h2>
  <?= $msg ?>

  <div class="card mb-4">
    <div class="card-body d-flex align-items-center">
      <img src="../<?= $user['profile_pic'] ?: 'assets/images/default-avatar.png' ?>" class="img-thumbnail me-4" width="120" height="120">
      <div>
        <h5><?= htmlspecialchars($user['name']) ?></h5>
        <p class="text-muted mb-1"><?= htmlspecialchars($user['email']) ?></p>
        <p><span class="badge bg-info"><?= $user['role'] ?></span></p>
        <p class="text-muted">Joined: <?= $user['created_at'] ?></p>
        <?php if (!empty($user['bio'])): ?>
        <p class="mt-2"><strong>Bio:</strong> <?= nl2br(htmlspecialchars($user['bio'])) ?></p>
      <?php endif; ?>
</div>

    </div>
  </div>

  <!-- Buttons -->
  <div class="mb-4 d-flex flex-wrap gap-2">
    <button class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editInfo">✏️ Edit Info</button>
    <button class="btn btn-outline-dark" data-bs-toggle="collapse" data-bs-target="#changePassword">🔒 Change Password</button>
    <?php if ($user['role'] === 'learner'): ?>
      <button class="btn btn-outline-success" data-bs-toggle="collapse" data-bs-target="#courseProgress">📚 View Courses & Progress</button>
    <?php endif; ?>
  </div>

  <!-- Edit Info -->
  <div class="collapse" id="editInfo">
    <div class="card card-body mb-4">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="update_profile" value="1">
        <div class="mb-3">
          <label>Name</label>
          <input name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
        <div class="mb-3">
          <label>Bio</label>
          <textarea name="bio" class="form-control"><?= htmlspecialchars($user['bio']) ?></textarea>
        </div>
        <div class="mb-3">
          <label>Profile Picture (jpg/png)</label>
          <input type="file" name="profile_pic" class="form-control">
        </div>
        <button class="btn btn-primary">💾 Save Changes</button>
      </form>
    </div>
  </div>

  <!-- Change Password -->
  <div class="collapse" id="changePassword">
    <div class="card card-body mb-4">
      <form method="POST">
        <input type="hidden" name="change_pass" value="1">
        <div class="mb-3">
          <label>Current Password</label>
          <input type="password" name="current_pass" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>New Password</label>
          <input type="password" name="new_pass" class="form-control" required>
        </div>
        <button class="btn btn-dark">Update Password</button>
      </form>
    </div>
  </div>

  <!-- Course Progress -->
  <?php if ($user['role'] === 'learner'): ?>
  <div class="collapse" id="courseProgress">
    <div class="card card-body mb-4">
      <?php
      $stmt = $conn->prepare("SELECT c.title, cp.status, c.id FROM course_progress cp JOIN courses c ON cp.course_id = c.id WHERE cp.user_id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();

      $status_counts = ['in_progress' => 0, 'completed' => 0];
      if ($result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
          $status_counts[$row['status']]++;
      ?>
        <div class="mb-3">
          <strong><?= htmlspecialchars($row['title']) ?></strong><br>
          <span class="badge bg-<?= $row['status'] === 'completed' ? 'success' : 'warning' ?>"><?= ucfirst($row['status']) ?></span>
          <a href="course-view.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary ms-2">View</a>
        </div>
      <?php endwhile; else: ?>
        <p class="text-muted">No enrolled courses yet.</p>
      <?php endif; ?>
      <hr>
      <canvas id="progressChart" height="150"></canvas>
      <script>
      const ctx = document.getElementById('progressChart').getContext('2d');
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['In Progress', 'Completed'],
          datasets: [{
            data: [<?= $status_counts['in_progress'] ?>, <?= $status_counts['completed'] ?>],
            backgroundColor: ['#f39c12', '#2ecc71'],
          }]
        }
      });
      </script>
    </div>
  </div>
  <?php endif; ?>

  <a href="dashboard.php" class="btn btn-secondary mt-4">← Back to Dashboard</a>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
