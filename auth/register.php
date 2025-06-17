<?php
include '../db-config.php'; // connect to DB

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // hashed
    $role     = $_POST['role'];

    // Prevent duplicate emails
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "Email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role);

        if ($stmt->execute()) {
            echo "✅ Registered successfully. <a href='login.php'>Login</a>";
        } else {
            echo "❌ Error: " . $stmt->error;
        }
    }
}
?>

<!-- Registration Form -->
<h2>Register</h2>
<form method="POST" action="">
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
