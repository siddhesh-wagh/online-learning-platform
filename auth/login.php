<?php
session_start();
include '../db-config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Fetch user from DB
    $stmt = $conn->prepare("SELECT id, name, email, password, role, is_approved, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // ✅ Verify password
        if (password_verify($password, $user['password'])) {

            // ✅ Block if email not verified
            if (!$user['is_verified']) {
                $error = "❌ Please verify your email before logging in.";
            }

            // ✅ Block unapproved instructor
            elseif ($user['role'] === 'instructor' && !$user['is_approved']) {
                $error = "❌ Your instructor account is pending admin approval.";
            }

            // ✅ Login success
           // ✅ Login success
else {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];

    // ✅ Update last_login timestamp
    $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $update->bind_param("i", $user['id']);
    $update->execute();

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

<!-- Login Form -->
<h2>Login</h2>

<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST" action="">
  <label>Email:</label><br>
  <input type="email" name="email" required><br><br>

  <label>Password:</label><br>
  <input type="password" name="password" required><br><br>

  <button type="submit">Login</button>
</form>
