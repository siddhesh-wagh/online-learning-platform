<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About - EduPlatform</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .icon {
      font-size: 1.5rem;
      margin-right: 0.5rem;
    }
    .section-title {
      font-weight: bold;
      color: #0d6efd;
    }
    .feature-list li {
      padding: 0.5rem 0;
      font-size: 1.1rem;
    }
  </style>
</head>
<body class="bg-light">

<div class="container py-5">
  
  <!-- 🔹 Header -->
  <div class="text-center mb-5">
    <h1 class="display-5 fw-bold text-primary">About <span class="text-dark">EduPlatform</span></h1>
    <p class="lead text-secondary">A modern learning management system designed to empower students, instructors, and administrators through efficient tools and insightful tracking.</p>
  </div>

  <!-- 🧑‍💼 Admin Features -->
  <div class="mb-5">
    <h4 class="section-title">👨‍💼 Admin Dashboard</h4>
    <ul class="list-unstyled feature-list">
      <li><span class="icon">📝</span> Real-time activity logs for login, registration, course edits, and progress</li>
      <li><span class="icon">📂</span> Export logs in CSV, PDF or print-ready formats (DomPDF & PhpSpreadsheet)</li>
      <li><span class="icon">🔍</span> Advanced filtering by date, role, keyword, or action with live pagination</li>
      <li><span class="icon">🚫</span> Soft-deletion with detailed log retention — admin account protected</li>
    </ul>
  </div>

  <!-- 🧑‍🏫 Instructor Features -->
  <div class="mb-5">
    <h4 class="section-title">👩‍🏫 Instructor Tools</h4>
    <ul class="list-unstyled feature-list">
      <li><span class="icon">📊</span> View enrolled students and comments via AJAX-enhanced dashboard</li>
      <li><span class="icon">📌</span> Logs created when courses are previewed or interacted with by users</li>
      <li><span class="icon">🧹</span> “Clear” filter buttons improve data navigation for instructors</li>
    </ul>
  </div>

  <!-- 👨‍🎓 Learner Experience -->
  <div class="mb-5">
    <h4 class="section-title">🎓 Learner Experience</h4>
    <ul class="list-unstyled feature-list">
      <li><span class="icon">📥</span> Seamless enrollment and real-time progress tracking</li>
      <li><span class="icon">📤</span> Logs track enrollments, comments, resets, and progress updates</li>
      <li><span class="icon">📄</span> PDF and video previews integrated directly in course pages</li>
    </ul>
  </div>

  <!-- 🎨 UX & Platform Design -->
  <div class="mb-5">
    <h4 class="section-title">✨ Platform Design & UX</h4>
    <ul class="list-unstyled feature-list">
      <li><span class="icon">📱</span> Responsive and AJAX-optimized interfaces (tabs, filters, and content)</li>
      <li><span class="icon">📌</span> Sticky footer and consistent navigation across all views</li>
      <li><span class="icon">🛠️</span> “Reset DB” concept under review for safe data clearance while preserving structure</li>
    </ul>
  </div>

  <!-- 🔙 Back Button -->
  <div class="text-center mt-5">
    <a href="../index.php" class="btn btn-dark px-4 py-2">
      ← Back to Home
    </a>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
