<?php
session_start();
require("../config/database.php");

// Simulación: normalmente esto viene del login
$curp = $_SESSION['curp']; // ejemplo: AUTL061220HMCGRSA5

$query = "
SELECT 
    (nombre).nombres AS nombres,
    (nombre).apellido_paterno AS ap_paterno,
    (nombre).apellido_materno AS ap_materno,
    rol
FROM usuario
WHERE curp = $1
";

$result = pg_query_params($conn, $query, array($curp));
$usuario = pg_fetch_assoc($result);

$nombreCompleto = $usuario['nombres'] . " " . 
                  $usuario['ap_paterno'] . " " . 
                  $usuario['ap_materno'];

$rol = $usuario['rol'];

// Saludo dinámico
$hora = date("H");

if ($hora >= 6 && $hora < 12) {
    $saludo = "Buenos días";
} elseif ($hora >= 12 && $hora < 19) {
    $saludo = "Buenas tardes";
} else {
    $saludo = "Buenas noches";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Panel Principal</title>
    <style>
        body { font-family: Arial; margin: 0; background: #f4f6f9; }
        header { background: #2c3e50; color: white; padding: 20px; }
        nav { background: #34495e; padding: 10px; }
        nav a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
            font-weight: bold;
        }
        .contenido { padding: 30px; }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<header>
    <h2><?php echo $saludo; ?></h2>
    <p><strong>Nombre:</strong> <?php echo $nombreCompleto; ?></p>
    <p><strong>Rol:</strong> <?php echo $rol; ?></p>
</header>

<nav>
    <a href="ver_usuarios.php">Ver todos los usuarios</a>
    <a href="agregar_usuario.php">Agregar nuevo usuario</a>
    <a href="logout.php">Cerrar sesión</a>
</nav>

<div class="contenido">
    <div class="card">
        <h3>Panel de Control</h3>
        <p>Bienvenido al sistema.</p>
    </div>
</div>

</body>
</html>