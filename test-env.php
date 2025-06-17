<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "USERNAME: " . $_ENV['MAIL_USERNAME'] . "<br>";
echo "PASSWORD: " . $_ENV['MAIL_PASSWORD'];
