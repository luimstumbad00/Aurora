<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$conn = pg_connect(
    "host=" . $_ENV['DB_HOST'] .
    " dbname=" . $_ENV['DB_NAME'] .
    " user=" . $_ENV['DB_USER'] .
    " password=" . $_ENV['DB_PASS']
);

if (!$conn) {
    die("Error de conexión ❌");
}