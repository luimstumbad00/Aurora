<?php
/*
|--------------------------------------------------------------------------
| Conexión PDO a PostgreSQL — Proyecto Aurora
|--------------------------------------------------------------------------
*/

$host     = 'localhost';
$dbname   = 'aurora';
$user     = 'postgres';
$password = 'aurora123';  // la contraseña que configuraste
$port     = '5432';

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}