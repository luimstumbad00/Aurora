<?php
session_start();

// 1. Validar sesión y roles (solo Administrador)
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// CORRECCIÓN: Validar contra el rol 'Administrador'
$rolActual = $_SESSION['usuario']['rol'];
if ($rolActual !== 'Administrador') {
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
    // CORRECCIÓN: Borramos de 'persona', lo cual hace cascada hacia 'nna'
    $query_del = "DELETE FROM persona WHERE curp = '$curp_original'";
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
    $nacimiento = pg_escape_string($conn, $_POST['nacimiento']);
    $sexo = pg_escape_string($conn, $_POST['sexo']);
    $nacionalidad = pg_escape_string($conn, $_POST['nacionalidad']);

    $situacion_calle = ($_POST['situacion_calle'] === 'Si' ? 'true' : 'false');
    // CORRECCIÓN: Se agregaron las variables para los campos que faltaban en el form
    $es_migrante = (isset($_POST['es_migrante']) && $_POST['es_migrante'] === 'Si' ? 'true' : 'false');
    $es_refugiado = (isset($_POST['es_refugiado']) && $_POST['es_refugiado'] === 'Si' ? 'true' : 'false');
    $poblacion_indigena = (isset($_POST['poblacion_indigena']) && $_POST['poblacion_indigena'] === 'Si' ? 'true' : 'false');

    // CORRECCIÓN: Dividimos el UPDATE en dos tablas usando una transacción
    pg_query($conn, "BEGIN");

    $update_persona = "UPDATE persona SET 
                        nombre = '$nombres', 
                        apellido_paterno = '$apellido_p', 
                        apellido_materno = " . ($apellido_m ? "'$apellido_m'" : "NULL") . ", 
                        fecha_nacimiento = '$nacimiento',
                        sexo = '$sexo'
                      WHERE curp = '$curp_original'";

    $update_nna = "UPDATE nna SET 
                    nacionalidad = '$nacionalidad',
                    situacion_calle = $situacion_calle,
                    es_migrante = $es_migrante, 
                    es_refugiado = $es_refugiado, 
                    poblacion_indigena = $poblacion_indigena
                   WHERE curp = '$curp_original'";

    $res_persona = pg_query($conn, $update_persona);
    $res_nna = pg_query($conn, $update_nna);

    if ($res_persona && $res_nna) {
        pg_query($conn, "COMMIT");
        $mensaje = "Información del NNA actualizada correctamente ✅";
        $tipoMensaje = "success";
    } else {
        pg_query($conn, "ROLLBACK");
        $mensaje = "Error al actualizar: " . pg_last_error($conn);
        $tipoMensaje = "error";
    }
}

// 3. Cargar datos actuales del NNA usando JOIN
$res = pg_query($conn, "SELECT n.*, p.nombre, p.apellido_paterno, p.apellido_materno, p.fecha_nacimiento, p.sexo 
                        FROM nna n 
                        JOIN persona p ON n.curp = p.curp 
                        WHERE n.curp = '$curp_original'");
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
        <input type="text" value="<?= htmlspecialchars($nna['curp']) ?>" disabled style="background-color: #eee;">

        <label>Nombres:</label>
        <input type="text" name="nombres" value="<?= htmlspecialchars($nna['nombre']) ?>" required>

        <label>Apellido Paterno:</label>
        <input type="text" name="apellido_p" value="<?= htmlspecialchars($nna['apellido_paterno']) ?>" required>

        <label>Apellido Materno:</label>
        <input type="text" name="apellido_m" value="<?= htmlspecialchars($nna['apellido_materno']) ?>">

        <label>Fecha de Nacimiento:</label>
        <input type="date" name="nacimiento" value="<?= htmlspecialchars($nna['fecha_nacimiento']) ?>" required>

        <label>Sexo:</label>
        <select name="sexo" required>
            <option value="Masculino" <?= $nna['sexo']=='Masculino'?'selected':'' ?>>MASCULINO</option>
            <option value="Femenino" <?= $nna['sexo']=='Femenino'?'selected':'' ?>>FEMENINO</option>
            <option value="Otro" <?= $nna['sexo']=='Otro'?'selected':'' ?>>OTRO</option>
        </select>

        <label>Nacionalidad:</label>
        <select name="nacionalidad" required>
            <?php 
            $paises = ["México", "Estados Unidos", "Guatemala", "Honduras", "El Salvador", "Venezuela", "Colombia"];
            foreach ($paises as $pais) {
                $sel = ($nna['nacionalidad'] == $pais) ? 'selected' : '';
                echo "<option value=\"$pais\" $sel>$pais</option>";
            }
            ?>
        </select>

        <label>¿Situación de Calle?</label>
        <select name="situacion_calle">
            <option value="Si" <?= $nna['situacion_calle']=='t'?'selected':'' ?>>Si</option>
            <option value="No" <?= $nna['situacion_calle']=='f'?'selected':'' ?>>No</option>
        </select>

        <!-- CORRECCIÓN: Agregados los campos faltantes de vulnerabilidad que estaban en el UPDATE -->
        <label>¿Es Migrante?</label>
        <select name="es_migrante">
            <option value="Si" <?= $nna['es_migrante']=='t'?'selected':'' ?>>Si</option>
            <option value="No" <?= $nna['es_migrante']=='f'?'selected':'' ?>>No</option>
        </select>

        <label>¿Es Refugiado?</label>
        <select name="es_refugiado">
            <option value="Si" <?= $nna['es_refugiado']=='t'?'selected':'' ?>>Si</option>
            <option value="No" <?= $nna['es_refugiado']=='f'?'selected':'' ?>>No</option>
        </select>

        <label>¿Pertenece a Población Indígena?</label>
        <select name="poblacion_indigena">
            <option value="Si" <?= $nna['poblacion_indigena']=='t'?'selected':'' ?>>Si</option>
            <option value="No" <?= $nna['poblacion_indigena']=='f'?'selected':'' ?>>No</option>
        </select>

        <button type="submit" name="actualizar_nna" class="btn-update">Guardar Cambios</button>
        <button type="submit" name="eliminar_nna" class="btn-delete" onclick="return confirm('¿Seguro que deseas eliminar este registro?')">Eliminar NNA</button>
    </form>
</div>

</body>
</html>