<?php
$response = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../includes/mailer.php';

    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $message = nl2br(htmlspecialchars(trim($_POST['message'])));

    $body = "<strong>From:</strong> $name &lt;$email&gt;<br><br>$message";

    if (sendEmail('sid.website11@gmail.com', '📨 New Contact Form Message', $body)) {
        $response = "<div class='alert alert-success text-center'>✅ Message sent successfully!</div>";
    } else {
        $response = "<div class='alert alert-danger text-center'>❌ Failed to send. Please try again later.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us - EduPlatform</title>
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

        .contact-wrapper {
            background-color: #fff;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 500px;
            position: relative;
        }

        .contact-wrapper h2 {
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
            color: #343a40;
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
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

<!-- 📬 Contact Form -->
<div class="contact-wrapper">
    <h2>✉️ Contact Us</h2>

    <?= $response ?>

    <form method="POST">
        <div class="mb-3">
            <input name="name" class="form-control" placeholder="Your Name" required>
        </div>
        <div class="mb-3">
            <input name="email" class="form-control" type="email" placeholder="Your Email" required>
        </div>
        <div class="mb-3">
            <textarea name="message" class="form-control" rows="5" placeholder="Your Message" required></textarea>
        </div>
        <button class="btn btn-primary w-100" type="submit">Send Message</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
