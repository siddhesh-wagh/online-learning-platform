<?php
session_start();
include '../db-config.php';
include '../includes/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, email, password, role, is_approved, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            if (!$user['is_verified']) {
                $error = "❌ Please verify your email before logging in.";
            } elseif ($user['role'] === 'instructor' && !$user['is_approved']) {
                $error = "❌ Your instructor account is pending admin approval.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];

                $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $user['id']);
                $update->execute();

                logAction($conn, $user['id'], "Logged in");

                header("Location: ../views/dashboard.php");
                exit;
            }
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - EduPlatform</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #e0ecff, #d8e2ff);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    .login-card {
      max-width: 400px;
      width: 100%;
      background: #fff;
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
    }
    .login-card h2 {
      font-weight: bold;
      margin-bottom: 1.5rem;
      color: #0d6efd;
    }
    .form-control:focus {
      box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    .alert {
      font-size: 0.95rem;
    }
    .back-button {
      position: absolute;
      top: 20px;
      left: 20px;
    }
  </style>
</head>
<body>

<!-- 🔙 Back to Home -->
<div class="back-button">
  <a href="../index.php" class="btn btn-secondary">← Back to Home</a>
</div>

<!-- 🔐 Login Card -->
<div class="login-card">
  <h2 class="text-center">Login</h2>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger text-center"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="mb-3">
      <label for="email" class="form-label">Email address</label>
      <input type="email" name="email" class="form-control" id="email" required>
    </div>

    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" name="password" class="form-control" id="password" required>
    </div>

    <div class="d-grid">
      <button type="submit" class="btn btn-primary">Log In</button>
    </div>
  </form>

  <div class="text-center mt-3">
    <a href="register.php" class="text-decoration-none">Don't have an account? Register</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
