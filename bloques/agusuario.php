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

if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $mensaje = "Usuario creado correctamente ✅";
    $tipoMensaje = "success";
}

// Catálogos para poblar los <select>
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

    $curp       = strtoupper(trim($_POST['curp'] ?? ''));
    $rfc        = strtoupper(trim($_POST['rfc'] ?? ''));
    $apellido_p = strtoupper(trim($_POST['apellido_p'] ?? ''));
    $apellido_m = strtoupper(trim($_POST['apellido_m'] ?? ''));
    $nombres    = strtoupper(trim($_POST['nombres'] ?? ''));

    $id_rol            = !empty($_POST['id_rol'])              ? (int)$_POST['id_rol']              : null;
    $id_municipio_lab  = !empty($_POST['id_municipio_labora']) ? (int)$_POST['id_municipio_labora'] : null;
    $id_equipo         = !empty($_POST['id_equipo'])           ? (int)$_POST['id_equipo']           : null;

    $correo     = strtolower(trim($_POST['correo'] ?? ''));
    $contrasena = $curp; // Contraseña temporal = CURP

    if (!$id_rol) {
        $mensaje = "Debes seleccionar un rol ";
        $tipoMensaje = "error";
    } else {
        try {
            $query = "
                INSERT INTO usuario_sistema (
                    curp, rfc, nombre, apellido_paterno, apellido_materno,
                    correo, contrasena, id_rol, id_municipio_labora, id_equipo, estado
                ) VALUES (
                    :curp, :rfc, :nombre, :apellido_p, :apellido_m,
                    :correo, :contrasena, :id_rol, :id_municipio, :id_equipo, 'ACTIVO'
                )
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':curp'         => $curp,
                ':rfc'          => $rfc !== '' ? $rfc : null,
                ':nombre'       => $nombres,
                ':apellido_p'   => $apellido_p,
                ':apellido_m'   => $apellido_m !== '' ? $apellido_m : null,
                ':correo'       => $correo,
                ':contrasena'   => $contrasena,
                ':id_rol'       => $id_rol,
                ':id_municipio' => $id_municipio_lab,
                ':id_equipo'    => $id_equipo
            ]);

            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit();

        } catch (PDOException $e) {
            $err = $e->getMessage();

            if (strpos($err, 'usuario_sistema_curp_key') !== false) {
                $mensaje = "La CURP ya está registrada ";
            } elseif (strpos($err, 'usuario_sistema_rfc_key') !== false) {
                $mensaje = "El RFC ya está registrado ";
            } elseif (strpos($err, 'usuario_sistema_correo_key') !== false) {
                $mensaje = "El correo electrónico ya está registrado ";
            } elseif (strpos($err, 'chk_curp_usuario') !== false) {
                $mensaje = "La CURP debe tener exactamente 18 caracteres ";
            } elseif (strpos($err, 'chk_correo_usuario') !== false) {
                $mensaje = "El formato del correo no es válido ";
            } else {
                $mensaje = "Error al crear usuario ";
            }
            $tipoMensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar Usuario</title>
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
    label {
        display: block;
        text-align: left;
        margin-top: 15px;
        margin-bottom: 5px;
        font-weight: bold;
        color: #2c3e50;
        font-size: 14px;
    }
    input[type="text"] {
        text-transform: uppercase;
    }
</style>
</head>
<body>

<div class="login-container">

    <div style="text-align: left;">
        <a href="dashboard.php" class="btn-regresar">⬅ Regresar al Dashboard</a>
    </div>

    <h1>Registrar Usuario</h1>

    <?php if ($mensaje): ?>
    <p style="color: <?php echo $tipoMensaje == 'success' ? 'green' : 'red'; ?>; font-weight: bold; background-color: <?php echo $tipoMensaje == 'success' ? '#d4edda' : '#f8d7da'; ?>; padding: 10px; border-radius: 5px;">
        <?php echo $mensaje; ?>
    </p>
    <?php endif; ?>

    <form method="POST">
        <label for="curp">CURP:</label>
        <input type="text" id="curp" name="curp" placeholder="Ej. ABCD123456EFGHIJ78" value="<?= htmlspecialchars($_POST['curp'] ?? '') ?>" required maxlength="18">
        
        <label for="rfc">RFC:</label>
        <input type="text" id="rfc" name="rfc" placeholder="Ej. ABCD123456789" value="<?= htmlspecialchars($_POST['rfc'] ?? '') ?>" required maxlength="13">

        <label for="apellido_p">Apellido Paterno:</label>
        <input type="text" id="apellido_p" name="apellido_p" value="<?= htmlspecialchars($_POST['apellido_p'] ?? '') ?>" required>
        
        <label for="apellido_m">Apellido Materno:</label>
        <input type="text" id="apellido_m" name="apellido_m" value="<?= htmlspecialchars($_POST['apellido_m'] ?? '') ?>">
        
        <label for="nombres">Nombres:</label>
        <input type="text" id="nombres" name="nombres" value="<?= htmlspecialchars($_POST['nombres'] ?? '') ?>" required>

        <label for="id_municipio_labora">Municipio donde labora (Opcional):</label>
        <select id="id_municipio_labora" name="id_municipio_labora">
            <option value="" selected>SIN ASIGNAR</option>
            <?php foreach ($municipios as $m): ?>
                <option value="<?= (int)$m['id_municipio'] ?>"
                    <?= (($_POST['id_municipio_labora'] ?? '') == $m['id_municipio']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(mb_strtoupper($m['nom_mun'] . ' — ' . $m['nom_ent'], 'UTF-8')) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="id_rol">Rol:</label>
        <select id="id_rol" name="id_rol" required>
            <option value="" disabled <?= empty($_POST['id_rol']) ? 'selected' : '' ?> hidden>SELECCIONE UN ROL</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= (int)$r['id'] ?>"
                    <?= (($_POST['id_rol'] ?? '') == $r['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(mb_strtoupper($r['nombre'], 'UTF-8')) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- v7: Equipo multidisciplinario -->
        <label for="id_equipo"> Equipo Multidisciplinario (Opcional):</label>
        <select id="id_equipo" name="id_equipo">
            <option value="" selected>SIN EQUIPO</option>
            <?php foreach ($equipos as $eq): ?>
                <option value="<?= (int)$eq['id_equipo'] ?>"
                    <?= (($_POST['id_equipo'] ?? '') == $eq['id_equipo']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(mb_strtoupper($eq['nombre_equipo'], 'UTF-8')) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="correo">Correo Electrónico:</label>
        <input type="email" id="correo" name="correo" value="<?= ($_POST['correo'] ?? '') ?>" required>

        <div style="background:#fff3cd; border:1px solid #ffc107; border-radius:6px; padding:12px; margin-top:20px; font-size:13px; color:#856404;">
            ⚠️ <strong>Contraseña temporal:</strong> La contraseña inicial del usuario será su <strong>CURP</strong>.
            El usuario debe cambiarla en su primer inicio de sesión desde <em>Mi Cuenta</em>.
        </div>

        <button type="submit" style="margin-top: 15px;">Crear Usuario</button>

    </form>

</div>

</body>
</html>