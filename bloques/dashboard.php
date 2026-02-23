<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set("America/Mexico_City");

$hora = date("H");

if ($hora < 12) {
    $saludo = "Buenos días";
} elseif ($hora < 19) {
    $saludo = "Buenas tardes";
} else {
    $saludo = "Buenas noches";
}

$usuario = $_SESSION['usuario'];

// Extraer nombre del tipo compuesto
$nombre = $usuario['nombre'];
$nombre = trim($nombre, '()');
$partes = explode(',', $nombre);

$nombres = $partes[2]; // campo nombres
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>

<h1><?php echo $saludo . ", " . $nombres; ?> 👋</h1>

<p>Bienvenido al sistema Aurora.</p>

<a href="logout.php">Cerrar sesión</a>

</body>
</html>