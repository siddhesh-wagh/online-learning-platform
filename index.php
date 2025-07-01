<?php
session_start();
if (isset($_SESSION['role'])) {
    header("Location: views/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <title>Online Learning Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
      }
      main {
        flex: 1;
      }
      .feature-icon {
        font-size: 3rem;
        color: #0d6efd;
      }
    </style>
</head>
<body class="bg-light">

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
        <li class="nav-item"><a class="nav-link" href="views/about.php">About</a></li>
        <li class="nav-item"><a class="nav-link" href="auth/register.php">Register</a></li>
        <li class="nav-item"><a class="nav-link" href="auth/login.php">Login</a></li>
        <li class="nav-item"><a class="nav-link" href="views/contact.php">Contact</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- 🖥 Hero Section -->
<main class="container py-5 text-center">
    <h1>Welcome to EduPlatform</h1>
    <p class="lead">A modern platform to teach, learn, and grow together online.</p>
    <a href="auth/register.php" class="btn btn-primary btn-lg">Get Started</a>
</main>

<!-- 🚀 Features -->
<section class="container text-center py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="feature-icon mb-3">🎓</div>
            <h4>Expert Instructors</h4>
            <p>Learn from certified professionals with years of industry experience.</p>
        </div>
        <div class="col-md-4">
            <div class="feature-icon mb-3">🌐</div>
            <h4>Flexible Learning</h4>
            <p>Access courses anytime, anywhere on any device.</p>
        </div>
        <div class="col-md-4">
            <div class="feature-icon mb-3">📈</div>
            <h4>Career Growth</h4>
            <p>Boost your resume and open new job opportunities.</p>
        </div>
    </div>
</section>

<!-- 🌟 Popular Courses -->
<section class="bg-white py-5">
    <div class="container text-center">
        <h2>Popular Courses</h2>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Web Development</h5>
                        <p class="card-text">Build websites with HTML, CSS, and JavaScript.</p>
                        <a href="views/course-details.php?id=1" class="btn btn-outline-primary">View Course</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Data Science</h5>
                        <p class="card-text">Analyze data with Python, R, and SQL.</p>
                        <a href="views/course-details.php?id=2" class="btn btn-outline-primary">View Course</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Digital Marketing</h5>
                        <p class="card-text">Master SEO, SEM, and social media strategies.</p>
                        <a href="views/course-details.php?id=3" class="btn btn-outline-primary">View Course</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ❤️ Testimonials -->
<section class="bg-light py-5">
    <div class="container text-center">
        <h2>What Our Students Say</h2>
        <div class="row mt-4">
            <div class="col-md-6">
                <blockquote class="blockquote">
                    <p>"EduPlatform helped me land my dream job. Highly recommended!"</p>
                    <footer class="blockquote-footer">Sarah M.</footer>
                </blockquote>
            </div>
            <div class="col-md-6">
                <blockquote class="blockquote">
                    <p>"The instructors are top-notch, and the content is very well structured."</p>
                    <footer class="blockquote-footer">David L.</footer>
                </blockquote>
            </div>
        </div>
    </div>
</section>

<!-- 📬 Newsletter Signup -->
<section class="bg-primary text-white py-5">
    <div class="container text-center">
        <h3>Stay Updated</h3>
        <p>Subscribe to our newsletter for the latest updates and course launches.</p>
        <form class="row justify-content-center">
            <div class="col-md-4">
                <input type="email" class="form-control" placeholder="Enter your email">
            </div>
            <div class="col-md-2">
                <button class="btn btn-light w-100">Subscribe</button>
            </div>
        </form>
    </div>
</section>

<!-- 🔗 Footer (Sticky at bottom) -->
<footer class="bg-dark text-white text-center py-3 mt-auto">
    &copy; <?= date('Y') ?> EduPlatform. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
