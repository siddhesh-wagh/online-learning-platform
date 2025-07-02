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
            $subject = "Verify Your Email - Online Learning Platform";

            $body = '
            <!DOCTYPE html>
            <html lang="en">
            <head>
              <meta charset="UTF-8">
              <title>Email Verification</title>
            </head>
            <body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px; color: #333;">
              <table width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden;">
                <tr>
                  <td style="background-color: #0d6efd; color: white; padding: 20px 30px; text-align: center; font-size: 24px;">
                    Online Learning Platform
                  </td>
                </tr>
                <tr>
                  <td style="padding: 30px;">
                    <h2 style="margin-top: 0;">Welcome, ' . htmlspecialchars($name) . ' 👋</h2>
                    <p style="font-size: 16px;">Thank you for registering as a <strong>' . htmlspecialchars(ucfirst($role)) . '</strong> on our platform. To activate your account and access your dashboard, please verify your email by clicking the button below.</p>

                    <p style="text-align: center; margin: 30px 0;">
                      <a href="' . $verify_link . '" style="background-color: #0d6efd; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-size: 16px;">
                        ✅ Verify My Email
                      </a>
                    </p>

                    <p style="font-size: 14px;">If the button above doesn’t work, you can copy and paste the link below into your browser:</p>
                    <p style="word-break: break-all;"><a href="' . $verify_link . '">' . $verify_link . '</a></p>

                    <p style="font-size: 14px;">If you did not sign up for this account, please ignore this email.</p>

                    <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

                    <p style="font-size: 12px; color: #888;">This verification link is valid once and may expire for security reasons.</p>
                    <p style="font-size: 12px; color: #888;">Need help? Contact us at <a href="mailto:sid.website11@gmail.com">sid.website11@gmail.com</a></p>
                  </td>
                </tr>
                <tr>
                  <td style="background-color: #f1f1f1; text-align: center; padding: 15px; font-size: 13px; color: #999;">
                    &copy; ' . date("Y") . ' Online Learning Platform. All rights reserved.
                  </td>
                </tr>
              </table>
            </body>
            </html>';

            $emailSent = sendEmail($email, $subject, $body, $conn, $user_id);
            logNewRegistration($conn, $user_id);

            if ($emailSent) {
                $message = ["type" => "success", "text" => "✅ Registered successfully! Please check your inbox to verify your email."];
            } else {
                $message = ["type" => "warning", "text" => "⚠️ Registered, but email failed to send. Please contact support."];
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
