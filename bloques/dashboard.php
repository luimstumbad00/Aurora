<?php
session_start();

// 1. Seguridad: Validar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php"); // Asegúrate de que apunte a tu login
    exit();
}

require("../config/database.php");

// Accedemos a la llave 'curp' dentro del arreglo 'usuario'
$curp = $_SESSION['usuario']['curp'];

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

// trim() elimina espacios extra por si no tienen apellido materno, por ejemplo
$nombreCompleto = trim($usuario['nombres'] . " " . $usuario['ap_paterno'] . " " . $usuario['ap_materno']);
$rol = $usuario['rol'];

// Configurar zona horaria y generar saludo dinámico
date_default_timezone_set('America/Mexico_City');
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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Principal - Aurora</title>
    <style>
        /* Importar una fuente moderna */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        /* Reset básico */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            /* Fondo degradado suave inspirado en una aurora */
            background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
            min-height: 100vh;
            color: #333;
            padding: 40px 20px;
        }

        /* Contenedor principal del dashboard */
        .dashboard-container {
            max-width: 1000px;
            margin: 0 auto;
            animation: fadeIn 0.6s ease-in-out;
        }

        /* Tarjeta de Encabezado */
        .header-card {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-info h2 {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .header-info p {
            font-size: 15px;
            color: #666;
            margin-top: 5px;
        }

        .badge-rol {
            background-color: #a18cd1;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
            margin-top: 5px;
        }

        /* Grid para los botones de navegación */
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Tarjetas de menú interactivo */
        .nav-item {
            background-color: #ffffff;
            padding: 25px 20px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.3s, box-shadow 0.3s, color 0.3s;
        }

        .nav-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(161, 140, 209, 0.3);
            color: #a18cd1;
        }

        /* Estilo especial para el botón de cerrar sesión */
        .nav-item.logout {
            color: #e74c3c;
        }
        .nav-item.logout:hover {
            color: white;
            background-color: #e74c3c;
            box-shadow: 0 15px 35px rgba(231, 76, 60, 0.3);
        }

        /* Tarjeta de contenido general */
        .content-card {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .content-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        /* Animación de entrada */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <div class="header-card">
        <div class="header-info">
            <h2><?php echo $saludo; ?>, <?php echo explode(' ', $nombreCompleto)[0]; ?></h2>
            <p><strong>Usuario:</strong> <?php echo $nombreCompleto; ?></p>
            <div class="badge-rol"><?php echo $rol; ?></div>
        </div>
        
        <?php if (isset($_GET['error']) && $_GET['error'] == 'acceso_denegado'): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px 15px; border-radius: 8px; font-weight: 500; font-size: 14px;">
                ⚠️ Acceso denegado a esa sección.
            </div>
        <?php endif; ?>
    </div>

    <div class="nav-grid">
        <a href="mi_cuenta.php" class="nav-item" style="color: #f39c12;">⚙️ Mi Cuenta</a>
        <a href="ver_usuarios.php" class="nav-item">👥 Ver Usuarios</a>
        <a href="ver_nnas.php" class="nav-item">👥 Ver a los NNA's</a>
        <?php if ($rol === 'Director' || $rol === 'Coordinador'): ?>
            <a href="agusuario.php" class="nav-item">➕ Agregar Usuario</a>
            <a href="modificar_usuario.php" class="nav-item">✏️ Editar Usuario</a>
            <a href="agregar_nna.php" class="nav-item">➕ Agregar NNA's</a>
            <a href="ver_tutores.php" class="nav-item">👥 Ver a los Tutores</a>
        <?php endif; ?>
    </div>

    <div class="content-card">
        <h3>Panel de Control</h3>
        <p style="color: #666;">Bienvenido al sistema Aurora. Desde aquí puedes gestionar las operaciones de tu cuenta utilizando el menú superior.</p>
    </div>

</div>

</body>
</html>