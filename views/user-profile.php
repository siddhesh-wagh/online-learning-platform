<?php
include '../includes/auth.php';
include '../db-config.php';
include '../includes/functions.php';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT name, email, role, created_at, bio, profile_pic, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

logAction($conn, $user_id, "Viewed profile page");

$msg = "";

// ✅ Update Profile Info
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
        logAction($conn, $user_id, "Updated profile info");
        header("Location: user-profile.php?success=1");
        exit;
    }
}

// ✅ Change Password
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
        logAction($conn, $user_id, "Changed password");
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .profile-avatar {
      width: 120px; height: 120px; border-radius: 50%; object-fit: cover;
    }
    .initials-avatar {
      width: 120px; height: 120px; background-color: #6c757d; color: white;
      border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem;
    }
  </style>
</head>
<body class="bg-light">

<div class="container py-5">
  <h2 class="mb-4">👤 My Profile</h2>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">✅ Profile updated successfully.</div>
  <?php endif; ?>

  <?= $msg ?>

  <!-- Profile Header -->
  <div class="card shadow-sm mb-4">
    <div class="card-body d-flex align-items-center">
      <?php if ($user['profile_pic'] && file_exists("../" . $user['profile_pic'])): ?>
        <img src="../<?= $user['profile_pic'] ?>" class="profile-avatar me-4" alt="Profile Picture">
      <?php else: ?>
        <div class="initials-avatar me-4"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
      <?php endif; ?>
      <div>
        <h4><?= htmlspecialchars($user['name']) ?></h4>
        <p class="text-muted mb-1"><?= htmlspecialchars($user['email']) ?></p>
        <p><span class="badge bg-primary"><?= ucfirst($user['role']) ?></span></p>
        <p class="text-muted">Joined: <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
        <?php if (!empty($user['last_login'])): ?>
          <p class="text-muted">Last login: <?= date('F j, Y \a\t g:i A', strtotime($user['last_login'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($user['bio'])): ?>
          <hr><p><strong>Bio:</strong><br><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-4" id="profileTabs">
    <li class="nav-item">
      <a class="nav-link active" data-bs-toggle="tab" href="#editTab">✏️ Edit Info</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="tab" href="#passwordTab">🔒 Change Password</a>
    </li>
    <?php if ($user['role'] === 'learner'): ?>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#progressTab">📚 Course Progress</a>
      </li>
    <?php endif; ?>
  </ul>

  <!-- Tab Content -->
  <div class="tab-content">

    <!-- Edit Info -->
    <div class="tab-pane fade show active" id="editTab">
      <div class="card card-body mb-4">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="update_profile" value="1">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Bio</label>
            <textarea name="bio" class="form-control"><?= htmlspecialchars($user['bio']) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Upload Profile Picture</label>
            <input type="file" name="profile_pic" class="form-control">
          </div>
          <button class="btn btn-primary">💾 Save Changes</button>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="tab-pane fade" id="passwordTab">
      <div class="card card-body mb-4">
        <form method="POST">
          <input type="hidden" name="change_pass" value="1">
          <div class="mb-3"><label class="form-label">Current Password</label>
            <input type="password" name="current_pass" class="form-control" required>
          </div>
          <div class="mb-3"><label class="form-label">New Password</label>
            <input type="password" name="new_pass" class="form-control" required>
          </div>
          <button class="btn btn-dark">🔐 Update Password</button>
        </form>
      </div>
    </div>

    <!-- Course Progress -->
    <?php if ($user['role'] === 'learner'): ?>
    <div class="tab-pane fade" id="progressTab">
      <div class="card card-body mb-4">
        <?php
        $stmt = $conn->prepare("SELECT c.title, cp.status, c.id FROM course_progress cp JOIN courses c ON cp.course_id = c.id WHERE cp.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $status_counts = ['in_progress' => 0, 'completed' => 0];
        ?>
        <ul class="list-group mb-3">
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): $status_counts[$row['status']]++; ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($row['title']) ?>
                <div>
                  <span class="badge bg-<?= $row['status'] === 'completed' ? 'success' : 'warning' ?>">
                    <?= ucfirst($row['status']) ?>
                  </span>
                  <a href="course-view.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary ms-2">View</a>
                </div>
              </li>
            <?php endwhile; ?>
          <?php else: ?>
            <li class="list-group-item text-muted">No enrolled courses found.</li>
          <?php endif; ?>
        </ul>

        <!-- Chart -->
        <div class="text-center mt-4">
          <div class="mx-auto" style="max-width: 240px;">
            <canvas id="progressChart"></canvas>
          </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
          new Chart(document.getElementById('progressChart'), {
            type: 'doughnut',
            data: {
              labels: ['In Progress', 'Completed'],
              datasets: [{
                data: [<?= $status_counts['in_progress'] ?>, <?= $status_counts['completed'] ?>],
                backgroundColor: ['#f39c12', '#28a745'],
                borderWidth: 1
              }]
            },
            options: {
              plugins: { legend: { position: 'bottom' } },
              responsive: true
            }
          });
        </script>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <a href="dashboard.php" class="btn btn-secondary mt-4">← Back to Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
