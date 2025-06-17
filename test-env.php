<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "MAIL_USERNAME: " . $_ENV['MAIL_USERNAME'] . "<br>";
echo "MAIL_PASSWORD: " . $_ENV['MAIL_PASSWORD'];
