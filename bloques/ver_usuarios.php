<?php
session_start();

// 1. Validar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php"); 
    exit();
}

// 2. Validar que solo el Director o Coordinador puedan estar aquí
$rolActual = $_SESSION['usuario']['rol'];
if ($rolActual !== 'Director' && $rolActual !== 'Coordinador') {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

// Obtenemos la CURP del usuario logueado para las validaciones
$curpLogueado = $_SESSION['usuario']['curp'];

ini_set('display_errors', 1);
error_reporting(E_ALL);
require '../config/database.php';

$mensaje = "";
$tipoMensaje = "";

// Atrapamos el mensaje de éxito o error si venimos de una redirección al borrar
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted') {
        $mensaje = "Usuario eliminado correctamente ✅";
        $tipoMensaje = "success";
    } elseif ($_GET['status'] == 'error') {
        $mensaje = "Error al eliminar usuario ❌";
        $tipoMensaje = "error";
    } elseif ($_GET['status'] == 'self_delete') {
        $mensaje = "Acción bloqueada: No puedes eliminar tu propio usuario ⚠️";
        $tipoMensaje = "error";
    }
}

// FASE DE BORRADO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') { 
    $curpAEliminar = $_POST['curp'] ?? '';

    if ($curpAEliminar) { 
        // ¡NUEVA SEGURIDAD!: Evitar que el usuario se borre a sí mismo
        if ($curpAEliminar === $curpLogueado) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=self_delete");
            exit();
        }

        $deleteQuery = "DELETE FROM usuario WHERE curp = $1";
        $resultDel = @pg_query_params($conn, $deleteQuery, array($curpAEliminar));
        
        if ($resultDel) { 
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=deleted");
            exit();
        } else { 
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=error");
            exit();
        }
    } else { 
        $mensaje = "CURP no especificada para eliminar.";
        $tipoMensaje = "error"; 
    }
}

// FASE DE LECTURA DE DATOS
$usuarios = [];
$queryAll = "
    SELECT 
        curp, rfc, 
        (nombre).apellido_paterno AS apellido_p, 
        (nombre).apellido_materno AS apellido_m, 
        (nombre).nombres AS nombres, 
        (direccion).calle AS calle, 
        (direccion).numero_exterior AS num_ext, 
        (direccion).numero_interior AS num_int, 
        (direccion).codigo_postal AS cp, 
        (direccion).municipio AS municipio, 
        (direccion).estado AS estado_dir, 
        sexo, nacimiento, tipo_personal, rol, estado, correo 
    FROM usuario 
    ORDER BY (nombre).apellido_paterno, (nombre).apellido_materno, (nombre).nombres
";

$resultAll = @pg_query($conn, $queryAll);

if ($resultAll) { 
    while ($row = pg_fetch_assoc($resultAll)) {
        $nombreCompleto = trim($row['nombres'] . " " . $row['apellido_p'] . " " . $row['apellido_m']);
        
        $direccionCompleta = $row['calle'] . " " . $row['num_ext'];
        if (!empty($row['num_int'])) {
            $direccionCompleta .= " Int. " . $row['num_int'];
        }
        $direccionCompleta .= ", C.P. " . $row['cp'] . ", " . $row['municipio'] . ", " . $row['estado_dir'];

        $usuario = [ 
            'curp' => $row['curp'], 
            'rfc' => $row['rfc'], 
            'nombre_completo' => $nombreCompleto, 
            'direccion_completa' => $direccionCompleta,
            'sexo' => $row['sexo'], 
            'nacimiento' => $row['nacimiento'], 
            'tipo_personal' => $row['tipo_personal'], 
            'rol' => $row['rol'], 
            'estado' => $row['estado'], 
            'correo' => $row['correo'] 
        ];
        $usuarios[] = $usuario;
    }
} else { 
    $mensaje .= " No se pudieron cargar los usuarios.";
    $tipoMensaje = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Usuarios</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .btn-regresar {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: #34495e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-family: Arial, sans-serif;
            transition: background-color 0.3s;
        }
        .btn-regresar:hover {
            background-color: #2c3e50;
        }
        .usuario-tarjeta {
            border: 1px solid #ddd;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            background: #f9f9f9;
            text-align: left; 
        }
        .usuario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ccc; 
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .usuario-datos {
            margin-top: 8px;
            font-size: 14px;
        }
        .usuario-datos div {
            margin-bottom: 4px; 
        }
        .btn-borrar {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-borrar:hover {
            background: #c0392b;
        }
        .etiqueta-yo {
            background: #95a5a6;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 13px;
        }
        .mensaje {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 12px;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    
    <div class="login-container" style="max-width: 800px;"> 
        
        <div style="text-align: left;">
            <a href="dashboard.php" class="btn-regresar">⬅ Regresar al Dashboard</a>
        </div>

        <h1>Lista de Usuarios</h1>
        
        <?php if ($mensaje): ?> 
            <div class="mensaje <?= $tipoMensaje == 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($mensaje) ?> 
            </div>
        <?php endif; ?>

        <?php if (!empty($usuarios)): ?>
            <?php foreach ($usuarios as $u): ?>
                <div class="usuario-tarjeta">
                    
                    <div class="usuario-header">
                        <strong>
                            <?= htmlspecialchars($u['nombre_completo']) ?>
                            (CURP: <?= htmlspecialchars($u['curp']) ?>)
                        </strong>
                        
                        <?php if ($u['curp'] === $curpLogueado): ?>
                            <span class="etiqueta-yo">Mi Cuenta</span>
                        <?php else: ?>
                            <form method="POST" style="margin:0;"> 
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="curp" value="<?= htmlspecialchars($u['curp']) ?>">
                                <button type="submit" class="btn-borrar" onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario y todos sus datos? Esta acción no se puede deshacer.');">
                                    Borrar 
                                </button> 
                            </form> 
                        <?php endif; ?>
                        
                    </div> 

                    <div class="usuario-datos"> 
                        <div><strong>RFC:</strong> <?= htmlspecialchars($u['rfc']) ?></div> 
                        <div><strong>Sexo:</strong> <?= htmlspecialchars($u['sexo']) ?></div> 
                        <div><strong>Nacimiento:</strong> <?= htmlspecialchars($u['nacimiento']) ?></div> 
                        <div><strong>Tipo de Personal:</strong> <?= htmlspecialchars($u['tipo_personal']) ?></div> 
                        <div><strong>Rol:</strong> <?= htmlspecialchars($u['rol'] ?? 'No asignado') ?></div> 
                        <div><strong>Estado:</strong> <?= htmlspecialchars($u['estado']) ?></div>
                        <div><strong>Correo:</strong> <?= htmlspecialchars($u['correo']) ?></div>
                        <div><strong>Dirección:</strong> <?= htmlspecialchars($u['direccion_completa']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #555;">No hay usuarios registrados.</p> 
        <?php endif; ?>
        
    </div>

</body>
</html>