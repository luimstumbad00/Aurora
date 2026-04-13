<?php
session_start();

// 1. Seguridad: Validar sesión y roles (Administrador o Médico si lo tienes)
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/database.php';

// 2. Obtener la CURP (Viene por GET la primera vez, por POST después)
$curp_nna = $_GET['curp_nna'] ?? $_POST['curp_nna_oculto'] ?? null;
$curp_tutor = $_GET['curp_tutor'] ?? $_POST['curp_tutor_oculto'] ?? null;
$curp_final = $curp_nna ? $curp_nna : $curp_tutor;

$mensaje = "";
$tipoMensaje = "";

// 3. Cargar el catálogo de enfermedades (CIE-10)
$query_cat = "SELECT id_enfermedad, nombre_padecimiento, tipo_enfermedad FROM cat_enfermedad ORDER BY nombre_padecimiento ASC";
$res_cat = pg_query($conn, $query_cat);

// 4. Procesar el registro
if ($_SERVER["REQUEST_METHOD"] === "POST" && $curp_final) {
    
    $id_enfermedad = (int)$_POST['id_enfermedad'];
    $es_cronica = isset($_POST['es_cronica']) ? 'true' : 'false';
    $esta_controlada = isset($_POST['esta_controlada']) ? 'true' : 'false';
    $tratamiento = pg_escape_string($conn, strtoupper(trim($_POST['tratamiento_actual'])));

    // INSERT ajustado a tu tabla 'persona_enfermedad'
    $query_insert = "INSERT INTO persona_enfermedad (
                        curp, id_enfermedad, es_cronica, esta_controlada, tratamiento_actual
                    ) VALUES (
                        '$curp_final', $id_enfermedad, $es_cronica, $esta_controlada, '$tratamiento'
                    )";

    $result = pg_query($conn, $query_insert);

    if ($result) {
        $mensaje = "Padecimiento registrado correctamente ✅";
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
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .form-salud { max-width: 600px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .info-persona { background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 5px solid #2196f3; margin-bottom: 25px; }
        label { font-weight: bold; display: block; margin-top: 20px; color: #2c3e50; }
        select, textarea { width: 100%; padding: 12px; margin-top: 8px; border: 1px solid #dcdde1; border-radius: 8px; font-size: 15px; box-sizing: border-box; }
        .opciones-medicas { display: flex; gap: 25px; margin-top: 20px; background: #fffbe6; padding: 15px; border: 1px solid #ffe58f; border-radius: 8px; }
        .opciones-medicas input { margin-right: 8px; transform: scale(1.2); }
        .btn-registrar { background-color: #27ae60; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 25px; transition: background 0.3s; }
        .btn-registrar:hover { background-color: #219150; }
        .btn-registrar:disabled { background-color: #bdc3c7; cursor: not-allowed; }
    </style>
</head>
<body>

<div class="form-salud">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">🩺 Registro de Salud</h2>
        <a href="ver_nnas.php" style="text-decoration: none; color: #7f8c8d; font-weight: bold;">⬅ Volver</a>
    </div>

    <div class="info-persona">
        <?php if ($curp_final): ?>
            Paciente: <strong style="color: #1a2a6c;"><?= htmlspecialchars($curp_final) ?></strong>
        <?php else: ?>
            <span style="color: #e74c3c; font-weight: bold;">⚠️ Error: No se seleccionó ningún paciente.</span>
        <?php endif; ?>
    </div>

    <?php if ($mensaje): ?>
        <p style="padding: 15px; border-radius: 8px; text-align: center; font-weight: bold; 
                  background: <?= $tipoMensaje=='success'?'#d4edda':'#f8d7da' ?>; 
                  color: <?= $tipoMensaje=='success'?'#155724':'#721c24' ?>;">
            <?= $mensaje ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="curp_nna_oculto" value="<?= htmlspecialchars($curp_nna) ?>">
        <input type="hidden" name="curp_tutor_oculto" value="<?= htmlspecialchars($curp_tutor) ?>">

        <label>Padecimiento (Catálogo CIE-10):</label>
        <select name="id_enfermedad" required>
            <option value="" disabled selected>-- Seleccione una enfermedad --</option>
            <?php while($row = pg_fetch_assoc($res_cat)): ?>
                <option value="<?= $row['id_enfermedad'] ?>">
                    <?= htmlspecialchars($row['nombre_padecimiento']) ?> (<?= $row['tipo_enfermedad'] ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <div class="opciones-medicas">
            <label style="font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="es_cronica"> ¿Es condición crónica?
            </label>
            <label style="font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="esta_controlada"> ¿Está controlada?
            </label>
        </div>

        <label>Detalles del Tratamiento Actual:</label>
        <textarea name="tratamiento_actual" rows="4" placeholder="Indique medicamentos, dosis o terapias recomendadas..."></textarea>

        <button type="submit" class="btn-registrar" <?= !$curp_final ? 'disabled' : '' ?>>
            Guardar en Historial Clínico
        </button>
    </form>
</div>

</body>
</html>