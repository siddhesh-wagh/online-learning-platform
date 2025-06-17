<?php include '../includes/auth.php'; ?>
<?php include '../db-config.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>

<h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
<p>Your role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>

<nav>
  <a href="../auth/logout.php">Logout</a>
</nav>

<!-- Add content based on role -->
<?php if ($_SESSION['role'] === 'instructor'): ?>
    <p><a href="add-course.php">Add New Course</a></p>
<?php elseif ($_SESSION['role'] === 'learner'): ?>
    <p><a href="course-list.php">Browse Courses</a></p>
<?php endif; ?>

</body>
</html>
