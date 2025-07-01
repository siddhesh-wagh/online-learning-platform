<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About - EduPlatform</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .hero {
      background: linear-gradient(135deg, #0d6efd, #6610f2);
      color: white;
      padding: 60px 0;
      text-align: center;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    .hero h1 {
      font-size: 3rem;
      font-weight: 700;
    }
    .section-title {
      border-left: 4px solid #0d6efd;
      padding-left: 15px;
      margin-bottom: 20px;
      font-weight: 600;
      color: #0d6efd;
    }
    .info-card {
      border: none;
      border-left: 4px solid #0d6efd;
      background: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      transition: 0.3s;
    }
    .info-card:hover {
      transform: translateY(-3px);
    }
    .info-card .card-body {
      padding: 1.25rem 1.5rem;
    }
  </style>
</head>
<body>

<!-- 🔷 Hero Header -->
<section class="hero">
  <div class="container">
    <h1>About <span class="text-warning">EduPlatform</span></h1>
    <p class="lead mt-3">An advanced platform to empower learners, instructors, and admins through smart features and beautiful design.</p>
  </div>
</section>

<!-- 🔧 Content -->
<div class="container py-5">

  <!-- 🧑‍💼 Admin Section -->
  <h3 class="section-title">👨‍💼 Admin Dashboard</h3>
  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card info-card">
        <div class="card-body">
          <h5 class="card-title">Real-time Activity Logs</h5>
          <p class="card-text">Track logins, registrations, profile changes, course creation, and more using a detailed log system.</p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card info-card">
        <div class="card-body">
          <h5 class="card-title">Smart Exports</h5>
          <p class="card-text">Export logs in PDF, CSV, or print view with filters and pagination using DomPDF and PhpSpreadsheet.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- 🧑‍🏫 Instructor Section -->
  <h3 class="section-title">👩‍🏫 Instructor Tools</h3>
  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card info-card">
        <div class="card-body">
          <h5 class="card-title">Course Insights</h5>
          <p class="card-text">Instructors can preview their courses and monitor enrolled learners, comments, and engagement in real time.</p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card info-card">
        <div class="card-body">
          <h5 class="card-title">Live Dashboard Updates</h5>
          <p class="card-text">Dashboards use AJAX to load tabs for comments and student info without refreshing the page.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- 👨‍🎓 Learner Section -->
  <h3 class="section-title">🎓 Learner Experience</h3>
  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card info-card">
        <div class="card-body">
          <h5 class="card-title">Progress Tracking</h5>
          <p class="card-text">Learners can enroll, view video or PDF content, and update/reset progress — all logged automatically.</p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card info-card">
        <div class="card-body">
          <h5 class="card-title">Smooth Course View</h5>
          <p class="card-text">Video playback, PDF preview, and comments are built into the learner interface for a modern learning experience.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- 🖌️ UX Section -->
  <h3 class="section-title">✨ Platform UX & Security</h3>
  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card info-card">
        <div class="card-body">
          <h5 class="card-title">Sticky Footer & Responsive Design</h5>
          <p class="card-text">EduPlatform is mobile-friendly with sticky footers and fluid layouts for all devices.</p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card info-card">
        <div class="card-body">
          <h5 class="card-title">Secure Admin Controls</h5>
          <p class="card-text">Admins (UID 1) are protected, and user deletions are handled safely with soft-delete logs.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- 🔙 Back Button -->
  <div class="text-center mt-5">
    <a href="../index.php" class="btn btn-primary px-4 py-2 shadow">← Back to Home</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
