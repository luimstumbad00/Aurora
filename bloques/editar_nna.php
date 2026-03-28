<?php
session_start();

// 1. Validar sesión y roles (solo Director o Coordinador)
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$rolActual = $_SESSION['usuario']['rol'];
if ($rolActual !== 'Director' && $rolActual !== 'Coordinador') {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

require '../config/database.php';

$mensaje = "";
$tipoMensaje = "";

// 2. Obtener CURP desde la URL
if (!isset($_GET['curp'])) {
    header("Location: ver_nnas.php");
    exit();
}
$curp_original = pg_escape_string($conn, $_GET['curp']);

// --- LÓGICA PARA ELIMINAR ---
if (isset($_POST['eliminar_nna'])) {
    $query_del = "DELETE FROM nna WHERE curp = '$curp_original'";
    $res_del = pg_query($conn, $query_del);
    if ($res_del) {
        header("Location: ver_nnas.php?mensaje=eliminado_exito");
        exit();
    } else {
        $mensaje = "Error al eliminar el registro ❌";
        $tipoMensaje = "error";
    }
}

// --- LÓGICA PARA ACTUALIZAR ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['actualizar_nna'])) {
    $nombres = pg_escape_string($conn, strtoupper(trim($_POST['nombres'])));
    $apellido_p = pg_escape_string($conn, strtoupper(trim($_POST['apellido_p'])));
    $apellido_m = pg_escape_string($conn, strtoupper(trim($_POST['apellido_m'])));
    $nacimiento = $_POST['nacimiento'];
    $sexo = $_POST['sexo'];
    $nacionalidad = pg_escape_string($conn, $_POST['nacionalidad']);
    $calle = pg_escape_string($conn, strtoupper(trim($_POST['calle'])));
    $num_ext = pg_escape_string($conn, strtoupper(trim($_POST['num_ext'])));
    $num_int = !empty($_POST['num_int']) ? "'" . pg_escape_string($conn, strtoupper(trim($_POST['num_int']))) . "'" : "NULL";

    $situacion_calle = ($_POST['situacion_calle'] === 'Si' ? 'true' : 'false');
    $es_migrante = ($_POST['migrante'] === 'Si' ? 'true' : 'false');
    $es_refugiado = ($_POST['refugiado'] === 'Si' ? 'true' : 'false');
    $poblacion_indigena = ($_POST['pob_indigena'] === 'Si' ? 'true' : 'false');

    $query_update = "UPDATE nna SET 
        nombre = '$nombres', apellido_paterno = '$apellido_p', apellido_materno = '$apellido_m',
        fecha_nacimiento = '$nacimiento', sexo = '$sexo', nacionalidad = '$nacionalidad',
        calle = '$calle', num_ext = '$num_ext', num_int = $num_int,
        situacion_calle = $situacion_calle, es_migrante = $es_migrante, 
        es_refugiado = $es_refugiado, poblacion_indigena = $poblacion_indigena
        WHERE curp = '$curp_original'";

    if (pg_query($conn, $query_update)) {
        $mensaje = "Información del NNA actualizada correctamente ✅";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al actualizar: " . pg_last_error($conn);
        $tipoMensaje = "error";
    }
}

// 3. Cargar datos actuales del NNA
$res = pg_query($conn, "SELECT * FROM nna WHERE curp = '$curp_original'");
$nna = pg_fetch_assoc($res);
if (!$nna) { die("NNA no encontrado."); }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar NNA</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-container { max-width: 600px; margin: 20px auto; padding: 30px; }
        label { display: block; margin-top: 15px; font-weight: bold; color: #2c3e50; }
        input[type="text"], select, input[type="date"] { width: 100%; padding: 8px; margin-top: 5px; text-transform: uppercase; }
        .btn-update { background-color: #27ae60; color: white; padding: 12px; border: none; width: 100%; cursor: pointer; border-radius: 5px; font-weight: bold; margin-top: 20px; }
        .btn-delete { background-color: #e74c3c; color: white; padding: 12px; border: none; width: 100%; cursor: pointer; border-radius: 5px; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>

<div class="login-container">
    <a href="ver_nnas.php" style="text-decoration: none; color: #34495e;">⬅ Volver a la lista</a>
    <h1>Editar Información de NNA</h1>

    <?php if ($mensaje): ?>
        <p style="background-color: <?= $tipoMensaje=='success'?'#d4edda':'#f8d7da' ?>; color: <?= $tipoMensaje=='success'?'green':'red' ?>; padding: 10px; border-radius: 5px; font-weight: bold; text-align: center;">
            <?= $mensaje ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>CURP (No editable):</label>
        <input type="text" value="<?= $nna['curp'] ?>" disabled style="background-color: #eee;">

        <label>Nombres:</label>
        <input type="text" name="nombres" value="<?= htmlspecialchars($nna['nombre']) ?>" required>

        <label>Apellido Paterno:</label>
        <input type="text" name="apellido_p" value="<?= htmlspecialchars($nna['apellido_paterno']) ?>" required>

        <label>Apellido Materno:</label>
        <input type="text" name="apellido_m" value="<?= htmlspecialchars($nna['apellido_materno']) ?>">

        <label>Fecha de Nacimiento:</label>
        <input type="date" name="nacimiento" value="<?= $nna['fecha_nacimiento'] ?>" required>

        <label>Sexo:</label>
        <select name="sexo" required>
            <option value="Masculino" <?= $nna['sexo']=='Masculino'?'selected':'' ?>>MASCULINO</option>
            <option value="Femenino" <?= $nna['sexo']=='Femenino'?'selected':'' ?>>FEMENINO</option>
            <option value="Otro" <?= $nna['sexo']=='Otro'?'selected':'' ?>>OTRO</option>
        </select>

        <label>Nacionalidad:</label>
        <select name="nacionalidad" required>
            <?php 
            $paises = ["México", "Estados Unidos", "Guatemala", "Honduras", "El Salvador", "Venezuela", "Colombia"]; // Agrega los que necesites
            foreach ($paises as $pais) {
                $sel = ($nna['nacionalidad'] == $pais) ? 'selected' : '';
                echo "<option value=\"$pais\" $sel>$pais</option>";
            }
            ?>
        </select>

        <label>Calle:</label>
        <input type="text" name="calle" value="<?= htmlspecialchars($nna['calle']) ?>" required>

        <label>Número Exterior:</label>
        <input type="text" name="num_ext" value="<?= htmlspecialchars($nna['num_ext']) ?>" required>

        <label>¿Situación de Calle?</label>
        <select name="situacion_calle">
            <option value="Si" <?= $nna['situacion_calle']=='t'?'selected':'' ?>>Si</option>
            <option value="No" <?= $nna['situacion_calle']=='f'?'selected':'' ?>>No</option>
        </select>

        <button type="submit" name="actualizar_nna" class="btn-update">Guardar Cambios</button>
        <button type="submit" name="eliminar_nna" class="btn-delete" onclick="return confirm('¿Seguro que deseas eliminar este registro?')">Eliminar NNA</button>
    </form>
</div>

</body>
</html>