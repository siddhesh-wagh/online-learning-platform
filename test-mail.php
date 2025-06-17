<?php
include 'includes/mailer.php';

$to = 'sid.website11@gmail.com'; // Test your own address
$subject = '📨 Test Email from Learning Platform';
$body = "If you see this, your PHPMailer setup is working!";

if (sendEmail($to, $subject, $body)) {
    echo "✅ Email sent successfully.";
} else {
    echo "❌ Email failed.";
}
