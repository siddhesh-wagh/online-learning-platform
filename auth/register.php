<?php
include '../db-config.php';
include_once '../includes/mailer.php';
include_once '../includes/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = ["type" => "danger", "text" => "❌ Email already exists."];
    } else {
        $token = bin2hex(random_bytes(16));

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, is_verified, verify_token)
                                VALUES (?, ?, ?, ?, 0, ?)");
        $stmt->bind_param("sssss", $name, $email, $password, $role, $token);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            $verify_link = "http://localhost/online-learning-platform/auth/verify.php?token=$token";
            $subject = "🔐 Verify Your Email - Online Learning Platform";
            $body = "
                <h2>Hi {$name},</h2>
                <p>Thanks for signing up as a <strong>{$role}</strong>.</p>
                <p>Please verify your email to activate your account:</p>
                <a href='{$verify_link}'>Click here to verify</a>
                <br><br>
                <small>This link is valid once. If you did not sign up, ignore this email.</small>
            ";

            $emailSent = sendEmail($email, $subject, $body, $conn, $user_id);
            logNewRegistration($conn, $user_id);

            if ($emailSent) {
                $message = ["type" => "success", "text" => "✅ Registered successfully! Check your email to verify your account."];
            } else {
                $message = ["type" => "warning", "text" => "⚠️ Registered, but email sending failed. Contact support."];
            }
        } else {
            $message = ["type" => "danger", "text" => "❌ Registration failed: " . $stmt->error];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - EduPlatform</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #0d6efd, #6610f2);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', sans-serif;
    }

    .register-wrapper {
      background-color: #fff;
      padding: 2.5rem;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      width: 100%;
      max-width: 450px;
      position: relative;
    }

    .register-wrapper h2 {
      font-weight: 700;
      margin-bottom: 1.5rem;
      color: #343a40;
      text-align: center;
    }

    .btn-primary {
      background-color: #0d6efd;
      border-color: #0d6efd;
    }

    .btn-primary:hover {
      background-color: #0b5ed7;
    }

    .alert {
      font-size: 0.95rem;
    }

    .back-home {
      position: absolute;
      top: -50px;
      left: 0;
    }

    .back-home a {
      color: #fff;
      font-weight: 500;
      text-decoration: none;
      transition: 0.2s ease;
    }

    .back-home a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<!-- 🔙 Back to Home -->
<div class="back-home position-absolute start-0 top-0 m-4">
  <a href="../index.php" class="btn btn-sm btn-outline-light">← Back to Home</a>
</div>

<!-- 📝 Registration Form -->
<div class="register-wrapper">
  <h2>Create Your Account</h2>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?= $message['type'] ?> text-center"><?= $message['text'] ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="mb-3">
      <label class="form-label">Full Name</label>
      <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Register As</label>
      <select name="role" class="form-select" required>
        <option value="learner">Learner</option>
        <option value="instructor">Instructor</option>
      </select>
    </div>

    <div class="d-grid">
      <button type="submit" class="btn btn-primary">Register</button>
    </div>
  </form>

  <div class="text-center mt-3">
    <a href="login.php" class="text-decoration-none text-primary">Already have an account? Login</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
