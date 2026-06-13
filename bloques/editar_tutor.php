<?php
session_start();

// 1. Validar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php"); 
    exit();
}

require("../config/database.php");

$mensaje = "";
$tipoMensaje = "";

if (!isset($_GET['curp'])) {
    header("Location: ver_tutores.php");
    exit();
}

$curp_tutor_get = trim($_GET['curp']);

// --- LÓGICA PARA ELIMINAR ---
// En el modelo normalizado se borra directo de tutor;
// nna_tutor tiene ON DELETE CASCADE, así que los vínculos se limpian solos.
if (isset($_POST['eliminar_tutor'])) {
    try {
        $stmtDel = $pdo->prepare("DELETE FROM tutor WHERE curp_tutor = :curp");
        $stmtDel->execute([':curp' => $curp_tutor_get]);

        header("Location: ver_tutores.php?mensaje=eliminado_exito");
        exit();
    } catch (PDOException $e) {
        // En producción: error_log($e->getMessage());
        $mensaje = "Error al eliminar el tutor ❌";
        $tipoMensaje = "error";
    }
}

// --- LÓGICA PARA ACTUALIZAR ---
// tutor guarda: nombre, primer_apellido, segundo_apellido, telefono, correo, es_adulto_mayor
// (no hay sexo ni tabla persona, por lo que es un UPDATE de una sola tabla)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['actualizar_tutor'])) {
    $nombre          = strtoupper(trim($_POST['nombre'] ?? ''));
    $apellido_p      = strtoupper(trim($_POST['apellido_paterno'] ?? ''));
    $apellido_m      = strtoupper(trim($_POST['apellido_materno'] ?? ''));
    $telefono        = trim($_POST['telefono'] ?? '');
    $correo          = strtolower(trim($_POST['correo'] ?? ''));
    $es_adulto_mayor = (($_POST['es_adulto_mayor'] ?? '') === 'Si') ? 'true' : 'false';

    try {
        // Una sola tabla: la transacción es opcional, pero la mantenemos por consistencia
        $pdo->beginTransaction();

        $sql = "UPDATE tutor SET 
                    nombre           = :nombre,
                    primer_apellido  = :ap_p,
                    segundo_apellido = :ap_m,
                    telefono         = :tel,
                    correo           = :correo,
                    es_adulto_mayor  = :adulto
                WHERE curp_tutor = :curp";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':ap_p'   => $apellido_p,
            ':ap_m'   => $apellido_m !== '' ? $apellido_m : null,
            ':tel'    => $telefono !== '' ? $telefono : null,
            ':correo' => $correo !== '' ? $correo : null,
            ':adulto' => $es_adulto_mayor,
            ':curp'   => $curp_tutor_get
        ]);

        $pdo->commit();
        $mensaje = "Datos actualizados correctamente ✅";
        $tipoMensaje = "success";
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (strpos($e->getMessage(), 'chk_correo_tutor') !== false) {
            $mensaje = "El formato del correo no es válido ⚠️";
        } else {
            $mensaje = "Error al actualizar los datos ❌";
        }
        $tipoMensaje = "error";
    }
}

// Cargar datos actuales del tutor (sin tabla persona)
try {
    $sql_tutor = "SELECT 
                    curp_tutor       AS curp,
                    nombre,
                    primer_apellido  AS apellido_paterno,
                    segundo_apellido AS apellido_materno,
                    telefono,
                    correo,
                    es_adulto_mayor
                  FROM tutor 
                  WHERE curp_tutor = :curp";
    $stmt = $pdo->prepare($sql_tutor);
    $stmt->execute([':curp' => $curp_tutor_get]);
    $tutor = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En producción: error_log($e->getMessage());
    $tutor = null;
}

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
            <?= htmlspecialchars($mensaje) ?>
        </p>
    <?php endif; ?>

    <form method="POST" id="formEditar">
        <label>CURP (No editable):</label>
        <input type="text" value="<?= htmlspecialchars($tutor['curp'] ?? 'Sin CURP') ?>" disabled style="background-color: #eee;">

        <label>Nombre(s):</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($tutor['nombre']) ?>" required>

        <label>Apellido Paterno:</label>
        <input type="text" name="apellido_paterno" value="<?= htmlspecialchars($tutor['apellido_paterno']) ?>" required>

        <label>Apellido Materno:</label>
        <input type="text" name="apellido_materno" value="<?= htmlspecialchars($tutor['apellido_materno'] ?? '') ?>">

        <label>¿Es Adulto Mayor?</label>
        <select name="es_adulto_mayor" required>
            <option value="No" <?= $tutor['es_adulto_mayor'] == 'f' ? 'selected' : '' ?>>No</option>
            <option value="Si" <?= $tutor['es_adulto_mayor'] == 't' ? 'selected' : '' ?>>Si</option>
        </select>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($tutor['telefono'] ?? '') ?>">

        <label>Correo Electrónico:</label>
        <input type="email" name="correo" value="<?= htmlspecialchars($tutor['correo'] ?? '') ?>" style="text-transform: lowercase;">

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