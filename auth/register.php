<?php
include '../db-config.php'; // DB connection
include_once '../includes/mailer.php';
include_once '../includes/functions.php'; // ✅ Logging functions included

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

    // Prevent duplicate email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "❌ Email already exists.";
    } else {
        $token = bin2hex(random_bytes(16));

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, is_verified, verify_token)
                                VALUES (?, ?, ?, ?, 0, ?)");
        $stmt->bind_param("sssss", $name, $email, $password, $role, $token);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id; // ✅ Get inserted user ID

            // ✅ Send verification email
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

            sendEmail($email, $subject, $body);

            // ✅ Log the registration
            logNewRegistration($conn, $user_id);

            echo "✅ Registered! Please check your email to verify your account.";
        } else {
            echo "❌ Registration failed: " . $stmt->error;
        }
    }
}

?>

<!-- Registration Form -->
<h2>Register</h2>
<form method="POST">
  <label>Name:</label><br>
  <input type="text" name="name" required><br><br>

  <label>Email:</label><br>
  <input type="email" name="email" required><br><br>

  <label>Password:</label><br>
  <input type="password" name="password" required><br><br>

  <label>Role:</label><br>
  <select name="role" required>
    <option value="learner">Learner</option>
    <option value="instructor">Instructor</option>
  </select><br><br>

  <button type="submit">Register</button>
</form>
