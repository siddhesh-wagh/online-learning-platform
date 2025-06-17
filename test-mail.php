<?php
include 'includes/mailer.php';

$to = 'sid.website@gmail.com';
$subject = 'PHPMailer Test';
$body = 'Hello Sid, this is a test email from your PHP project.';

if (sendEmail($to, $subject, $body)) {
    echo "✅ Email sent successfully!";
} else {
    echo "❌ Failed to send email. Check error logs.";
}
