<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// ✅ Load .env file from the project root
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];  // loaded from .env
        $mail->Password   = $_ENV['MAIL_PASSWORD'];  // loaded from .env
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Email content
        $mail->setFrom($_ENV['MAIL_USERNAME'], 'Online Learning Platform');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
