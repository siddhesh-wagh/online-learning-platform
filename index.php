<?php
session_start();
if (isset($_SESSION['role'])) {
    header("Location: views/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Online Learning Platform</title>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- 🌐 Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">🧠 EduPlatform</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="views/course-list.php">Courses</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Instructors</a></li>
        <li class="nav-item"><a class="nav-link" href="auth/register.php">Register</a></li>
        <li class="nav-item"><a class="nav-link" href="auth/login.php">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- 🖥 Hero Section -->
<div class="container py-5 text-center">
    <h1>Welcome to EduPlatform</h1>
    <p class="lead">A modern platform to teach, learn, and grow together online.</p>
    <a href="auth/register.php" class="btn btn-primary btn-lg">Get Started</a>
</div>

<!-- 🔗 Footer -->
<footer class="bg-dark text-white text-center py-3 mt-4">
    &copy; <?= date('Y') ?> EduPlatform. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
