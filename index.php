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

<!-- 🌐 Navbar (Balanced & Symmetrical) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm py-3">
  <div class="container d-flex align-items-center justify-content-between">
    
    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2 fs-4 fw-semibold text-white ps-3" href="#" style="letter-spacing: 1px;">
      <span style="font-size: 1.5rem;">🧠</span> EduPlatform
    </a>

    <!-- Toggler (for mobile view) -->
    <button class="navbar-toggler me-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navbar links -->
    <div class="collapse navbar-collapse justify-content-end pe-3" id="navbarContent">
      <ul class="navbar-nav gap-3">
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

<!-- 🖥 Hero Section (Enhanced) -->
<header class="bg-light py-5" style="background: linear-gradient(135deg, #e0f0ff, #ffffff); box-shadow: inset 0 -1px 0 #ddd;">
    <div class="container text-center py-5">
        <h1 class="display-4 fw-bold text-dark mb-3">Welcome to <span class="text-primary">EduPlatform</span></h1>
        <p class="lead text-secondary mb-4">Empowering learners to build skills, grow careers, and achieve goals online.</p>
        <a href="auth/register.php" class="btn btn-primary btn-lg px-4 shadow-sm">Get Started</a>
    </div>
</header>

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

<!-- 📚 Course Categories -->
<section class="bg-white py-5">
    <div class="container text-center">
        <h2>Explore Learning Categories</h2>
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">💻 Coding & Development</h5>
                        <p class="card-text">Learn web, mobile, and software development skills.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">📊 Business & Management</h5>
                        <p class="card-text">Courses on marketing, finance, entrepreneurship, and more.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">🎨 Design & Creativity</h5>
                        <p class="card-text">Graphic design, UI/UX, animation, and more creative skills.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">🔬 Science & Data</h5>
                        <p class="card-text">Data science, machine learning, and scientific research basics.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 👨‍🏫 Instructors Section -->
<section class="bg-light py-5">
    <div class="container text-center">
        <h2>Meet Our Instructors</h2>
        <p class="text-muted">Learn from top professionals and educators from around the globe.</p>
        <a href="http://localhost/online-learning-platform/views/instructors.php" class="btn btn-outline-primary mt-3">View All Instructors</a>
    </div>
</section>

<!-- 📈 Platform Highlights -->
<section class="bg-light py-5">
    <div class="container text-center">
        <h2>Why Choose EduPlatform?</h2>
        <div class="row mt-4">
            <div class="col-md-4">
                <h3>5000+</h3>
                <p>Students enrolled globally</p>
            </div>
            <div class="col-md-4">
                <h3>150+</h3>
                <p>Courses across various domains</p>
            </div>
            <div class="col-md-4">
                <h3>24/7</h3>
                <p>Access and support available anytime</p>
            </div>
        </div>
    </div>
</section>

<!-- 🔗 Footer -->
<footer class="bg-dark text-white text-center py-3 mt-auto">
    &copy; <?= date('Y') ?> EduPlatform. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
