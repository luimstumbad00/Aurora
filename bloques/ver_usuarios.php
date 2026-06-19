<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php"); 
    exit();
}

$rolActual = $_SESSION['usuario']['rol'];
if ($rolActual !== 'Administrador') {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

$curpLogueado = $_SESSION['usuario']['curp'];

ini_set('display_errors', 1);
error_reporting(E_ALL);
require '../config/database.php';

$mensaje = "";
$tipoMensaje = "";

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

// BORRADO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') { 
    $curpAEliminar = $_POST['curp'] ?? '';

    if ($curpAEliminar) { 
        if ($curpAEliminar === $curpLogueado) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=self_delete");
            exit();
        }

        try {
            $stmtDel = $pdo->prepare("DELETE FROM usuario_sistema WHERE curp = :curp");
            $stmtDel->execute([':curp' => $curpAEliminar]);
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=deleted");
            exit();
        } catch (PDOException $e) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=error");
            exit();
        }
    } else { 
        $mensaje = "CURP no especificada para eliminar.";
        $tipoMensaje = "error"; 
    }
}

// LECTURA — v7: +equipo
$usuarios = [];
$queryAll = "
    SELECT 
        u.curp, 
        u.rfc, 
        u.apellido_paterno   AS apellido_p, 
        u.apellido_materno   AS apellido_m, 
        u.nombre             AS nombres, 
        u.correo, 
        u.estado, 
        u.fecha_registro,
        r.nombre             AS rol,
        m.nom_mun            AS municipio_labora,
        e.nom_ent            AS entidad_labora,
        eq.nombre_equipo     AS equipo
    FROM usuario_sistema u
    INNER JOIN cat_rol_sistema r       ON r.id = u.id_rol
    LEFT  JOIN cat_municipio m         ON m.id_municipio = u.id_municipio_labora
    LEFT  JOIN entidad_federativa e    ON e.id_ent = m.id_ent
    LEFT  JOIN equipo eq               ON eq.id_equipo = u.id_equipo
    ORDER BY u.apellido_paterno, u.apellido_materno, u.nombre
";

try {
    $stmtAll = $pdo->query($queryAll);

    while ($row = $stmtAll->fetch(PDO::FETCH_ASSOC)) {
        $nombreCompleto = trim($row['nombres'] . " " . $row['apellido_p'] . " " . ($row['apellido_m'] ?? ''));

        if (!empty($row['municipio_labora'])) {
            $lugarLabora = $row['municipio_labora'];
            if (!empty($row['entidad_labora'])) {
                $lugarLabora .= ", " . $row['entidad_labora'];
            }
        } else {
            $lugarLabora = "No asignado";
        }

        $usuarios[] = [ 
            'curp'             => $row['curp'], 
            'rfc'              => $row['rfc'], 
            'nombre_completo'  => $nombreCompleto, 
            'rol'              => $row['rol'], 
            'estado'           => $row['estado'], 
            'correo'           => $row['correo'],
            'fecha_registro'   => $row['fecha_registro'],
            'lugar_labora'     => $lugarLabora,
            'equipo'           => $row['equipo']
        ];
    }
} catch (PDOException $e) {
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
        .btn-regresar:hover { background-color: #2c3e50; }
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
        .usuario-datos { margin-top: 8px; font-size: 14px; }
        .usuario-datos div { margin-bottom: 4px; }
        .acciones-btn { display: flex; gap: 10px; align-items: center; }
        .btn-borrar {
            background: #e74c3c; color: white; border: none;
            padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold;
        }
        .btn-borrar:hover { background: #c0392b; }
        .btn-editar {
            background: #3498db; color: white; border: none;
            padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold;
        }
        .btn-editar:hover { background: #2980b9; }
        .etiqueta-yo {
            background: #95a5a6; color: white;
            padding: 8px 12px; border-radius: 6px; font-weight: bold; font-size: 13px;
        }
        .mensaje { padding: 10px; border-radius: 6px; margin-bottom: 12px; font-weight: bold; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .tag-equipo {
            background: #8e44ad; color: #fff; font-size: 11px;
            padding: 2px 8px; border-radius: 4px; margin-left: 6px;
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
                            <?php if (!empty($u['equipo'])): ?>
                                <span class="tag-equipo"><?= htmlspecialchars($u['equipo']) ?></span>
                            <?php endif; ?>
                            <br><small style="color: #666; font-weight: normal;">CURP: <?= htmlspecialchars($u['curp']) ?></small>
                        </strong>
                        
                        <div class="acciones-btn">
                            <form action="modificar_usuario.php" method="POST" style="margin:0;">
                                <input type="hidden" name="curp_buscar" value="<?= htmlspecialchars($u['curp']) ?>">
                                <button type="submit" name="buscar" class="btn-editar">✏️ Editar</button>
                            </form>

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
                    </div> 

                    <div class="usuario-datos"> 
                        <div><strong>RFC:</strong> <?= htmlspecialchars($u['rfc'] ?? '') ?></div> 
                        <div><strong>Rol:</strong> <?= htmlspecialchars($u['rol'] ?? 'No asignado') ?></div> 
                        <div><strong>Estado:</strong> <?= htmlspecialchars($u['estado'] ?? '') ?></div>
                        <div><strong>Correo:</strong> <?= htmlspecialchars($u['correo'] ?? '') ?></div>
                        <div><strong>Municipio donde labora:</strong> <?= htmlspecialchars($u['lugar_labora'] ?? 'No asignado') ?></div>
                        <div><strong>Equipo:</strong> <?= htmlspecialchars($u['equipo'] ?? 'Sin equipo') ?></div>
                        <div><strong>Fecha de registro:</strong> <?= htmlspecialchars($u['fecha_registro'] ?? '') ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #555;">No hay usuarios registrados.</p> 
        <?php endif; ?>
        
    </div>

</body>
</html>