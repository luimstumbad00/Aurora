<?php
session_start();

// 1. Validar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php"); 
    exit();
}

require("../config/database.php");
$curp = $_SESSION['usuario']['curp'];

$mensaje = "";
$tipoMensaje = "";

if (!isset($_GET['curp'])) {
    header("Location: ver_tutores.php");
    exit();
}

$curp_tutor_get = pg_escape_string($conn, $_GET['curp']);

// --- LÓGICA PARA ELIMINAR ---
if (isset($_POST['eliminar_tutor'])) {
    // CORRECCIÓN: Borramos de 'persona', lo cual hace cascada hacia 'tutor' y 'nna_tutor'
    $query_del = "DELETE FROM persona WHERE curp = '$curp_tutor_get'";
    $res_del = pg_query($conn, $query_del);

    if ($res_del) {
        // Redirigir al directorio con un mensaje de éxito
        header("Location: ver_tutores.php?mensaje=eliminado_exito");
        exit();
    } else {
        $mensaje = "Error al eliminar el tutor ❌";
        $tipoMensaje = "error";
    }
}

// --- LÓGICA PARA ACTUALIZAR ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['actualizar_tutor'])) {
    $nombre = pg_escape_string($conn, strtoupper(trim($_POST['nombre'])));
    $apellido_p = pg_escape_string($conn, strtoupper(trim($_POST['apellido_paterno'])));
    $apellido_m = pg_escape_string($conn, strtoupper(trim($_POST['apellido_materno'])));
    $sexo = pg_escape_string($conn, $_POST['sexo']);
    $telefono = pg_escape_string($conn, trim($_POST['telefono']));
    $correo = pg_escape_string($conn, trim($_POST['correo']));
    $es_adulto_mayor = ($_POST['es_adulto_mayor'] ?? '') === 'Si' ? 'true' : 'false';

    // CORRECCIÓN: Dividimos el UPDATE en dos tablas usando una transacción
    pg_query($conn, "BEGIN");

    $update_persona = "UPDATE persona SET 
                        nombre = '$nombre', 
                        apellido_paterno = '$apellido_p', 
                        apellido_materno = " . ($apellido_m ? "'$apellido_m'" : "NULL") . ", 
                        sexo = '$sexo'
                      WHERE curp = '$curp_tutor_get'";

    $update_tutor = "UPDATE tutor SET 
                        es_adulto_mayor = $es_adulto_mayor, 
                        telefono = '$telefono', 
                        correo = '$correo' 
                    WHERE curp = '$curp_tutor_get'";

    $res_persona = pg_query($conn, $update_persona);
    $res_tutor = pg_query($conn, $update_tutor);

    if ($res_persona && $res_tutor) {
        pg_query($conn, "COMMIT"); // Guardamos los cambios
        $mensaje = "Datos actualizados correctamente ✅";
        $tipoMensaje = "success";
    } else {
        pg_query($conn, "ROLLBACK"); // Revertimos si algo falló
        $mensaje = "Error al actualizar: " . pg_last_error($conn);
        $tipoMensaje = "error";
    }
}

// CORRECCIÓN: Cargar datos actuales usando JOIN con persona
$sql_tutor = "SELECT t.*, p.nombre, p.apellido_paterno, p.apellido_materno, p.sexo 
              FROM tutor t 
              JOIN persona p ON t.curp = p.curp 
              WHERE t.curp = '$curp_tutor_get'";
$res_tutor = pg_query($conn, $sql_tutor);
$tutor = pg_fetch_assoc($res_tutor);

if (!$tutor) { die("Error: Tutor no encontrado."); }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Tutor</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-container { max-width: 500px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box; }
        .btn-update { background-color: #f39c12; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; margin-bottom: 10px; }
        .btn-delete { background-color: #c0392b; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; }
        .btn-regresar { display: inline-block; margin-bottom: 15px; color: #34495e; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="form-container">
    <a href="ver_tutores.php" class="btn-regresar">⬅ Volver al Directorio</a>
    
    <h1>Editar Información</h1>

    <?php if ($mensaje): ?>
        <p style="color: white; background-color: <?= $tipoMensaje == 'success' ? '#27ae60' : '#e74c3c' ?>; padding: 10px; border-radius: 5px; text-align: center;">
            <?= $mensaje ?>
        </p>
    <?php endif; ?>

    <form method="POST" id="formEditar">
        <label>CURP (No editable):</label>
        <input type="text" value="<?= htmlspecialchars($tutor['curp']) ?>" disabled style="background-color: #eee;">

        <label>Nombre(s):</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($tutor['nombre']) ?>" required>

        <label>Apellido Paterno:</label>
        <input type="text" name="apellido_paterno" value="<?= htmlspecialchars($tutor['apellido_paterno']) ?>" required>

        <label>Apellido Materno:</label>
        <input type="text" name="apellido_materno" value="<?= htmlspecialchars($tutor['apellido_materno']) ?>">

        <label>Sexo:</label>
        <select name="sexo" required>
            <option value="Masculino" <?= $tutor['sexo'] == 'Masculino' ? 'selected' : '' ?>>MASCULINO</option>
            <option value="Femenino" <?= $tutor['sexo'] == 'Femenino' ? 'selected' : '' ?>>FEMENINO</option>
            <option value="Otro" <?= $tutor['sexo'] == 'Otro' ? 'selected' : '' ?>>OTRO</option>
        </select>

        <label>¿Es Adulto Mayor?</label>
        <select name="es_adulto_mayor" required>
            <option value="No" <?= $tutor['es_adulto_mayor'] == 'f' ? 'selected' : '' ?>>No</option>
            <option value="Si" <?= $tutor['es_adulto_mayor'] == 't' ? 'selected' : '' ?>>Si</option>
        </select>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($tutor['telefono']) ?>">

        <label>Correo Electrónico:</label>
        <input type="email" name="correo" value="<?= htmlspecialchars($tutor['correo']) ?>" style="text-transform: lowercase;">

        <!-- Botón Actualizar -->
        <button type="submit" name="actualizar_tutor" class="btn-update">Actualizar Información</button>
        
        <!-- Botón Eliminar -->
        <button type="submit" name="eliminar_tutor" class="btn-delete" onclick="return confirmarEliminacion();">Eliminar Tutor</button>
    </form>
</div>

<script>
function confirmarEliminacion() {
    return confirm("⚠️ ¿Estás seguro de que deseas eliminar a este tutor? Esta acción borrará también su relación con cualquier NNA asignado.");
}
</script>

</body>
</html>