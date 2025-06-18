<?php
// Handle form submission first
$response = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../includes/mailer.php';

    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $message = nl2br(htmlspecialchars(trim($_POST['message'])));

    $body = "<strong>From:</strong> $name &lt;$email&gt;<br><br>$message";

    if (sendEmail('sid.website11@gmail.com', '📨 New Contact Form Message', $body)) {
        $response = "<p class='alert alert-success'>✅ Message sent successfully!</p>";
    } else {
        $response = "<p class='alert alert-danger'>❌ Failed to send. Please try again later.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contact Us - EduPlatform</title>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">

    <h2 class="mb-4">✉️ Contact Us</h2>

    <?= $response ?>

    <form method="POST" style="max-width:500px;margin:auto;">
        <input name="name" class="form-control mb-3" placeholder="Your Name" required>
        <input name="email" class="form-control mb-3" type="email" placeholder="Your Email" required>
        <textarea name="message" class="form-control mb-3" rows="5" placeholder="Your Message" required></textarea>
        <button class="btn btn-primary w-100" type="submit">Send Message</button>
    </form>

    <p class="text-center mt-4"><a href="../index.php">← Back to Home</a></p>

</div>
</body>
</html>
