<form method="POST" style="max-width:500px;margin:auto">
  <h3>Contact Us</h3>
  <input name="name" class="form-control mb-2" placeholder="Your Name" required>
  <input name="email" class="form-control mb-2" placeholder="Your Email" required>
  <textarea name="message" class="form-control mb-2" rows="5" placeholder="Your Message" required></textarea>
  <button class="btn btn-primary" type="submit">Send</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'includes/mailer.php';
    $msg = "From: " . $_POST['name'] . " <" . $_POST['email'] . ">\n\n" . $_POST['message'];
    if (sendEmail('sid.website11@gmail.com', 'Contact Form Message', $msg)) {
        echo "<p class='text-success'>✅ Message sent!</p>";
    } else {
        echo "<p class='text-danger'>❌ Failed to send. Try again later.</p>";
    }
}
?>
