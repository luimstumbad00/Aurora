<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/database.php';

// 1. Obtener parámetros de la URL para identificar a quién se registra
$curp_nna = $_GET['curp_nna'] ?? null;
$curp_tutor = $_GET['curp_tutor'] ?? null;

$mensaje = "";
$tipoMensaje = "";

// 2. Cargar el catálogo de enfermedades según tu script de pgAdmin
$query_cat = "SELECT id_enfermedad, nombre_padecimiento, tipo_enfermedad FROM cat_enfermedad ORDER BY nombre_padecimiento ASC";
$res_cat = pg_query($conn, $query_cat);

// 3. Procesar el formulario de registro
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_enfermedad = $_POST['id_enfermedad'];
    // Validar checkboxes para booleanos de PostgreSQL
    $es_cronica = isset($_POST['es_cronica']) ? 'true' : 'false';
    $esta_controlada = isset($_POST['esta_controlada']) ? 'true' : 'false';
    $tratamiento = pg_escape_string($conn, strtoupper(trim($_POST['tratamiento_actual'])));

    // Determinar qué CURP se va a insertar (uno será NULL y otro tendrá valor)
    $val_nna = $curp_nna ? "'$curp_nna'" : "NULL";
    $val_tutor = $curp_tutor ? "'$curp_tutor'" : "NULL";

    $query_insert = "INSERT INTO persona_enfermedad (
                        curp_nna, curp_tutor, id_enfermedad, 
                        es_cronica, esta_controlada, tratamiento_actual
                    ) VALUES (
                        $val_nna, $val_tutor, $id_enfermedad, 
                        $es_cronica, $esta_controlada, '$tratamiento'
                    )";

    $result = pg_query($conn, $query_insert);

    if ($result) {
        $mensaje = "Padecimiento registrado correctamente en el sistema Aurora ✅";
        $tipoMensaje = "success";
    } else {
        $mensaje = "Error al registrar: " . pg_last_error($conn);
        $tipoMensaje = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Salud - Aurora</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-salud { max-width: 600px; margin: 30px auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .info-persona { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 5px solid #2980b9; margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-top: 15px; color: #34495e; }
        select, textarea { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .opciones-medicas { display: flex; gap: 20px; margin-top: 15px; background: #fffdf0; padding: 10px; border: 1px solid #f1c40f; border-radius: 5px; }
        .btn-registrar { background-color: #27ae60; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; font-weight: bold; cursor: pointer; margin-top: 20px; transition: 0.3s; }
        .btn-registrar:hover { background-color: #219150; }
    </style>
</head>
<body>

<div class="form-salud">
    <a href="javascript:history.back();" style="text-decoration: none; color: #7f8c8d;">⬅ Regresar</a>
    <h2>🩺 Registro Médico</h2>

    <div class="info-persona">
        <?php if ($curp_nna): ?>
            Paciente (NNA): <strong><?= htmlspecialchars($curp_nna) ?></strong>
        <?php elseif ($curp_tutor): ?>
            Paciente (Tutor): <strong><?= htmlspecialchars($curp_tutor) ?></strong>
        <?php else: ?>
            <span style="color: #e74c3c;">⚠️ Error: No se ha detectado una CURP válida.</span>
        <?php endif; ?>
    </div>

    <?php if ($mensaje): ?>
        <p style="padding: 10px; border-radius: 5px; text-align: center; font-weight: bold; 
                  background: <?= $tipoMensaje=='success'?'#d4edda':'#f8d7da' ?>; 
                  color: <?= $tipoMensaje=='success'?'#155724':'#721c24' ?>;">
            <?= $mensaje ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>Padecimiento (Catálogo CIE-10):</label>
        <select name="id_enfermedad" required>
            <option value="">-- Seleccione una enfermedad --</option>
            <?php while($row = pg_fetch_assoc($res_cat)): ?>
                <option value="<?= $row['id_enfermedad'] ?>">
                    <?= htmlspecialchars($row['nombre_padecimiento']) ?> (<?= $row['tipo_enfermedad'] ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <div class="opciones-medicas">
            <label style="font-weight: normal; margin: 0;">
                <input type="checkbox" name="es_cronica"> ¿Es una condición crónica?
            </label>
            <label style="font-weight: normal; margin: 0;">
                <input type="checkbox" name="esta_controlada"> ¿Está bajo control?
            </label>
        </div>

        <label>Detalles del Tratamiento Actual:</label>
        <textarea name="tratamiento_actual" rows="4" placeholder="Indique medicamentos, dosis o terapias..."></textarea>

        <button type="submit" class="btn-registrar" <?= (!$curp_nna && !$curp_tutor) ? 'disabled' : '' ?>>
            Guardar en Expediente Multidisciplinario
        </button>
    </form>
</div>

</body>
</html>