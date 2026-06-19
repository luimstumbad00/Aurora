<?php
session_start();

// 1. Seguridad: Validar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/database.php';

// 2. Obtener las CURP (Detectar si es NNA o Tutor)
$curp_nna   = $_GET['curp_nna'] ?? $_POST['curp_nna_oculto'] ?? null;
$curp_tutor = $_GET['curp_tutor'] ?? $_POST['curp_tutor_oculto'] ?? null;

// Determinar el tipo de paciente y su CURP final
$tipo_paciente = null;
$curp_final = null;

if (!empty($curp_nna)) {
    $tipo_paciente = 'NNA';
    $curp_final = $curp_nna;
} elseif (!empty($curp_tutor)) {
    $tipo_paciente = 'Tutor';
    $curp_final = $curp_tutor;
}

$mensaje = "";
$tipoMensaje = "";

// 3. Cargar el catálogo de enfermedades (CIE-10)
$catalogo = [];
try {
    $catalogo = $pdo->query("
        SELECT id_enfermedad, codigo_cie, nombre
        FROM cat_enfermedad
        ORDER BY nombre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $catalogo = [];
}

// 4. Procesar el registro
if ($_SERVER["REQUEST_METHOD"] === "POST" && $curp_final) {

    $id_enfermedad    = (int) ($_POST['id_enfermedad'] ?? 0);
    $bajo_tratamiento = isset($_POST['bajo_tratamiento']) ? 'true' : 'false';
    $observaciones    = strtoupper(trim($_POST['observaciones'] ?? ''));

    try {
        if ($id_enfermedad <= 0) {
            $mensaje = "Debes seleccionar un padecimiento ⚠️";
            $tipoMensaje = "error";
        } else {
            // Lógica dividida dependiendo de si es NNA o Tutor
            if ($tipo_paciente === 'NNA') {
                
                // Buscar ID del NNA
                $stmtId = $pdo->prepare("SELECT id_nna FROM nna WHERE curp = :curp LIMIT 1");
                $stmtId->execute([':curp' => $curp_final]);
                $id_persona = $stmtId->fetchColumn();

                if (!$id_persona) {
                    throw new Exception("No se encontró un NNA con esa CURP.");
                }

                // Insertar en nna_enfermedad
                $query_insert = "
                    INSERT INTO nna_enfermedad (id_nna, id_enfermedad, bajo_tratamiento, observaciones) 
                    VALUES (:id_persona, :id_enfermedad, :bajo_tratamiento, :observaciones)
                ";

            } else {
                
                // Buscar ID del Tutor
                $stmtId = $pdo->prepare("SELECT id_tutor FROM tutor WHERE curp_tutor = :curp LIMIT 1");
                $stmtId->execute([':curp' => $curp_final]);
                $id_persona = $stmtId->fetchColumn();

                if (!$id_persona) {
                    throw new Exception("No se encontró un Tutor con esa CURP.");
                }

                // Insertar en tutor_enfermedad
                $query_insert = "
                    INSERT INTO tutor_enfermedad (id_tutor, id_enfermedad, bajo_tratamiento, observaciones) 
                    VALUES (:id_persona, :id_enfermedad, :bajo_tratamiento, :observaciones)
                ";
            }

            // Ejecutar la inserción correspondiente
            $stmt = $pdo->prepare($query_insert);
            $stmt->execute([
                ':id_persona'       => $id_persona,
                ':id_enfermedad'    => $id_enfermedad,
                ':bajo_tratamiento' => $bajo_tratamiento,
                ':observaciones'    => $observaciones !== '' ? $observaciones : null
            ]);

            $mensaje = "Padecimiento registrado correctamente al $tipo_paciente ✅";
            $tipoMensaje = "success";
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage() . " ⚠️";
        $tipoMensaje = "error";
    } catch (PDOException $e) {
        // Validación de llaves primarias duplicadas para ambas tablas
        if (strpos($e->getMessage(), 'nna_enfermedad_pkey') !== false || strpos($e->getMessage(), 'tutor_enfermedad_pkey') !== false) {
            $mensaje = "Este padecimiento ya está registrado para el $tipo_paciente ⚠️";
        } else {
            $mensaje = "Error en la base de datos al registrar el padecimiento ❌";
        }
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
            Paciente (<?= $tipo_paciente ?>): <strong style="color: #1a2a6c;"><?= htmlspecialchars($curp_final) ?></strong>
        <?php else: ?>
            <span style="color: #e74c3c; font-weight: bold;">⚠️ Error: No se seleccionó ningún paciente.</span>
        <?php endif; ?>
    </div>

    <?php if ($mensaje): ?>
        <p style="padding: 15px; border-radius: 8px; text-align: center; font-weight: bold; 
                  background: <?= $tipoMensaje=='success'?'#d4edda':'#f8d7da' ?>; 
                  color: <?= $tipoMensaje=='success'?'#155724':'#721c24' ?>;">
            <?= htmlspecialchars($mensaje) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="curp_nna_oculto" value="<?= htmlspecialchars($curp_nna ?? '') ?>">
        <input type="hidden" name="curp_tutor_oculto" value="<?= htmlspecialchars($curp_tutor ?? '') ?>">

        <label>Padecimiento (Catálogo CIE-10):</label>
        <select name="id_enfermedad" required>
            <option value="" disabled selected>-- Seleccione una enfermedad --</option>
            <?php foreach ($catalogo as $row): ?>
                <option value="<?= (int) $row['id_enfermedad'] ?>">
                    <?= htmlspecialchars($row['nombre']) ?><?= $row['codigo_cie'] ? ' (' . htmlspecialchars($row['codigo_cie']) . ')' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="opciones-medicas">
            <label style="font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="bajo_tratamiento"> ¿Está bajo tratamiento?
            </label>
        </div>

        <label>Observaciones / Detalles del Tratamiento:</label>
        <textarea name="observaciones" rows="4" placeholder="Indique medicamentos, dosis o terapias recomendadas..."></textarea>

        <button type="submit" class="btn-registrar" <?= !$curp_final ? 'disabled' : '' ?>>
            Guardar en Historial Clínico
        </button>
    </form>
</div>

</body>
</html>