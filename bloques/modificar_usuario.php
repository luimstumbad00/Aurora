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

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../config/database.php';

$mensaje = "";
$tipoMensaje = "";
$usuario = null;

if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $mensaje = "Datos del usuario actualizados correctamente ✅";
    $tipoMensaje = "success";
}

// Catálogos
$roles = [];
$municipios = [];
$equipos = [];

try {
    $roles = $pdo->query("SELECT id, nombre FROM cat_rol_sistema ORDER BY nombre")
                 ->fetchAll(PDO::FETCH_ASSOC);

    $municipios = $pdo->query("
        SELECT m.id_municipio, m.nom_mun, e.nom_ent
        FROM cat_municipio m
        INNER JOIN entidad_federativa e ON e.id_ent = m.id_ent
        ORDER BY e.nom_ent, m.nom_mun
    ")->fetchAll(PDO::FETCH_ASSOC);

    $equipos = $pdo->query("SELECT id_equipo, nombre_equipo FROM equipo WHERE estado = 'ACTIVO' ORDER BY nombre_equipo")
                   ->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "No se pudieron cargar los catálogos ❌";
    $tipoMensaje = "error";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // FASE 1: BUSCAR USUARIO
    if (isset($_POST['buscar']) || isset($_POST['curp_buscar'])) {
        $curp_buscar = strtoupper(trim($_POST['curp_buscar'] ?? ''));
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    curp, rfc, 
                    apellido_paterno AS apellido_p, 
                    apellido_materno AS apellido_m, 
                    nombre           AS nombres, 
                    correo, estado,
                    id_rol, id_municipio_labora, id_equipo
                FROM usuario_sistema 
                WHERE curp = :curp
            ");
            $stmt->execute([':curp' => $curp_buscar]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $mensaje = "Usuario encontrado. Puede modificar sus datos. ";
                $tipoMensaje = "success";
            } else {
                $mensaje = "Usuario no encontrado ⚠️";
                $tipoMensaje = "error";
            }
        } catch (PDOException $e) {
            $mensaje = "Error al buscar usuario ❌";
            $tipoMensaje = "error";
        }
    } 
    
    // FASE 2: ACTUALIZAR USUARIO
    elseif (isset($_POST['actualizar'])) {
        $curp       = strtoupper(trim($_POST['curp'] ?? '')); 
        $rfc        = strtoupper(trim($_POST['rfc'] ?? ''));
        $apellido_p = strtoupper(trim($_POST['apellido_p'] ?? ''));
        $apellido_m = strtoupper(trim($_POST['apellido_m'] ?? ''));
        $nombres    = strtoupper(trim($_POST['nombres'] ?? ''));
        $correo     = strtolower(trim($_POST['correo'] ?? ''));

        $id_rol           = !empty($_POST['id_rol'])              ? (int)$_POST['id_rol']              : null;
        $id_municipio_lab = !empty($_POST['id_municipio_labora']) ? (int)$_POST['id_municipio_labora'] : null;
        $id_equipo        = !empty($_POST['id_equipo'])           ? (int)$_POST['id_equipo']           : null;

        if (!$id_rol) {
            $mensaje = "Debes seleccionar un rol ⚠️";
            $tipoMensaje = "error";
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE usuario_sistema SET 
                        rfc                 = :rfc, 
                        nombre              = :nombre, 
                        apellido_paterno    = :apellido_p,
                        apellido_materno    = :apellido_m,
                        correo              = :correo,
                        id_rol              = :id_rol,
                        id_municipio_labora = :id_municipio,
                        id_equipo           = :id_equipo
                    WHERE curp = :curp
                ");
                $stmt->execute([
                    ':rfc'          => $rfc !== '' ? $rfc : null,
                    ':nombre'       => $nombres,
                    ':apellido_p'   => $apellido_p,
                    ':apellido_m'   => $apellido_m !== '' ? $apellido_m : null,
                    ':correo'       => $correo,
                    ':id_rol'       => $id_rol,
                    ':id_municipio' => $id_municipio_lab,
                    ':id_equipo'    => $id_equipo,
                    ':curp'         => $curp
                ]);

                header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
                exit();

            } catch (PDOException $e) {
                $err = $e->getMessage();
                if (strpos($err, 'usuario_sistema_correo_key') !== false) {
                    $mensaje = "El correo ya está registrado por otro usuario ⚠️";
                } elseif (strpos($err, 'usuario_sistema_rfc_key') !== false) {
                    $mensaje = "El RFC ya está registrado por otro usuario ⚠️";
                } elseif (strpos($err, 'chk_correo_usuario') !== false) {
                    $mensaje = "El formato del correo no es válido ⚠️";
                } else {
                    $mensaje = "Error al actualizar usuario ❌";
                }
                $tipoMensaje = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar y Modificar Usuario</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .btn-regresar {
            display: inline-block; margin-bottom: 20px; padding: 10px 15px;
            background-color: #34495e; color: white; text-decoration: none;
            border-radius: 5px; font-weight: bold; font-family: Arial, sans-serif;
            transition: background-color 0.3s;
        }
        .btn-regresar:hover { background-color: #2c3e50; }
        label {
            display: block; text-align: left; margin-top: 15px; margin-bottom: 5px;
            font-weight: bold; color: #2c3e50; font-size: 14px;
        }
        input[type="text"] { text-transform: uppercase; }
    </style>
</head>
<body>

<div class="login-container">

    <a href="dashboard.php" class="btn-regresar">⬅ Regresar al Dashboard</a>

    <h1>Buscar Usuario</h1>

    <?php if ($mensaje): ?>
    <p style="color: <?= $tipoMensaje == 'success' ? 'green' : 'red' ?>; font-weight:bold;
              background: <?= $tipoMensaje == 'success' ? '#d4edda' : '#f8d7da' ?>;
              padding:10px; border-radius:5px;">
        <?= htmlspecialchars($mensaje) ?>
    </p>
    <?php endif; ?>

    <form method="POST">
        <label for="curp_buscar">Ingrese la CURP del usuario:</label>
        <input type="text" id="curp_buscar" name="curp_buscar" placeholder="Ej. ABCD123456EFGHIJ78" required maxlength="18" value="<?= htmlspecialchars($_POST['curp_buscar'] ?? '') ?>">
        <button type="submit" name="buscar">Buscar</button>
    </form>

    <br>

    <?php if ($usuario): ?>
        <hr>
        <h2>Modificar Datos</h2>
        <form method="POST">
            
            <label for="curp">CURP (No modificable):</label>
            <input type="text" id="curp" name="curp" value="<?= htmlspecialchars($usuario['curp'] ?? '') ?>" readonly style="background-color: #e9ecef;">
            
            <label for="rfc">RFC:</label>
            <input type="text" id="rfc" name="rfc" value="<?= htmlspecialchars($usuario['rfc'] ?? '') ?>" maxlength="13">

            <label for="apellido_p">Apellido Paterno:</label>
            <input type="text" id="apellido_p" name="apellido_p" value="<?= htmlspecialchars($usuario['apellido_p'] ?? '') ?>" required>
            
            <label for="apellido_m">Apellido Materno:</label>
            <input type="text" id="apellido_m" name="apellido_m" value="<?= htmlspecialchars($usuario['apellido_m'] ?? '') ?>">
            
            <label for="nombres">Nombres:</label>
            <input type="text" id="nombres" name="nombres" value="<?= htmlspecialchars($usuario['nombres'] ?? '') ?>" required>

            <label for="id_municipio_labora">Municipio donde labora (Opcional):</label>
            <select id="id_municipio_labora" name="id_municipio_labora">
                <option value="">SIN ASIGNAR</option>
                <?php foreach ($municipios as $m): ?>
                    <option value="<?= (int)$m['id_municipio'] ?>"
                        <?= (($usuario['id_municipio_labora'] ?? '') == $m['id_municipio']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(mb_strtoupper($m['nom_mun'] . ' — ' . $m['nom_ent'], 'UTF-8')) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="id_rol">Rol:</label>
            <select id="id_rol" name="id_rol" required>
                <option value="" hidden>Seleccione un rol</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r['id'] ?>"
                        <?= (($usuario['id_rol'] ?? '') == $r['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(str_replace('_', ' ', $r['nombre'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- v7: Equipo multidisciplinario -->
            <label for="id_equipo">🏥 Equipo Multidisciplinario (Opcional):</label>
            <select id="id_equipo" name="id_equipo">
                <option value="">SIN EQUIPO</option>
                <?php foreach ($equipos as $eq): ?>
                    <option value="<?= (int)$eq['id_equipo'] ?>"
                        <?= (($usuario['id_equipo'] ?? '') == $eq['id_equipo']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(mb_strtoupper($eq['nombre_equipo'], 'UTF-8')) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="correo">Correo Electrónico:</label>
            <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>" required>

            <button type="submit" name="actualizar" style="margin-top: 20px;">Guardar Cambios</button>

        </form>
    <?php endif; ?>

</div>

</body>
</html>