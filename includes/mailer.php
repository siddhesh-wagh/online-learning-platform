<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// ✅ Load .env file from the project root
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function sendEmail($to, $subject, $body, $conn = null, $user_id = null) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom($_ENV['MAIL_USERNAME'], 'Online Learning Platform');
        $mail->addAddress($to);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();

        // ✅ Optional DB logging
        if ($conn && $user_id) {
            logAction($conn, $user_id, "Sent email: $subject to $to");
        }

        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        if ($conn && $user_id) {
            logAction($conn, $user_id, "❌ Failed to send email: $subject to $to");
        }
        echo "<script>console.error('❌ Mailer Error: " . $mail->ErrorInfo . "');</script>";
        return false;
    }
}
