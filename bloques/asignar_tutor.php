<?php
session_start();
require '../config/database.php';

// 1. Validar sesión y rol
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Administrador') {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

// 2. Identificar al NNA (CURP llega por GET la 1ª vez, por POST después)
$curp_nna = $_GET['curp_nna'] ?? $_POST['curp_nna_oculto'] ?? '';

$mensaje = "";
$tipoMensaje = "";

// Catálogo de parentesco para el <select>
$parentescos = [];
try {
    $parentescos = $pdo->query("SELECT id, nombre FROM cat_parentesco ORDER BY nombre")
                       ->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En producción: error_log($e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($curp_nna)) {

    // Datos del tutor (el esquema NO guarda sexo, nacimiento ni domicilio del tutor)
    $curp_tutor    = strtoupper(trim($_POST['curp_tutor'] ?? ''));
    $nombre        = strtoupper(trim($_POST['nombre'] ?? ''));
    $apellido_p    = strtoupper(trim($_POST['apellido_p'] ?? ''));
    $apellido_m    = strtoupper(trim($_POST['apellido_m'] ?? ''));
    $telefono      = trim($_POST['telefono'] ?? '');
    $correo        = strtolower(trim($_POST['correo'] ?? ''));
    $es_adulto     = (($_POST['es_adulto_mayor'] ?? '') === 'Si') ? 'true' : 'false';
    $id_parentesco = !empty($_POST['id_parentesco']) ? (int) $_POST['id_parentesco'] : null;

    if (empty($curp_tutor) || empty($nombre) || empty($apellido_p) || !$id_parentesco) {
        $mensaje = "CURP, nombre, apellido paterno y parentesco son obligatorios ⚠️";
        $tipoMensaje = "error";
    } else {
        try {
            $pdo->beginTransaction();

            // PASO 1: Resolver el id_nna a partir de su CURP
            $stmtNna = $pdo->prepare("SELECT id_nna FROM nna WHERE curp = :curp LIMIT 1");
            $stmtNna->execute([':curp' => $curp_nna]);
            $id_nna = $stmtNna->fetchColumn();

            if (!$id_nna) {
                throw new PDOException("NNA no encontrado");
            }

            // PASO 2: UPSERT del tutor por su CURP (única). Recuperamos id_tutor.
            $qTutor = "
                INSERT INTO tutor (curp_tutor, nombre, primer_apellido, segundo_apellido, telefono, correo, es_adulto_mayor)
                VALUES (:curp, :nombre, :ap_p, :ap_m, :tel, :correo, :adulto)
                ON CONFLICT (curp_tutor) DO UPDATE SET
                    nombre           = EXCLUDED.nombre,
                    primer_apellido  = EXCLUDED.primer_apellido,
                    segundo_apellido = EXCLUDED.segundo_apellido,
                    telefono         = EXCLUDED.telefono,
                    correo           = EXCLUDED.correo,
                    es_adulto_mayor  = EXCLUDED.es_adulto_mayor
                RETURNING id_tutor
            ";
            $stmtTutor = $pdo->prepare($qTutor);
            $stmtTutor->execute([
                ':curp'   => $curp_tutor,
                ':nombre' => $nombre,
                ':ap_p'   => $apellido_p,
                ':ap_m'   => $apellido_m !== '' ? $apellido_m : null,
                ':tel'    => $telefono !== '' ? $telefono : null,
                ':correo' => $correo !== '' ? $correo : null,
                ':adulto' => $es_adulto
            ]);
            $id_tutor = $stmtTutor->fetchColumn();

            // PASO 3: Vincular NNA-tutor con el parentesco (tabla puente)
            $qRel = "
                INSERT INTO nna_tutor (id_nna, id_tutor, id_parentesco, es_contacto_ppal, fecha_vinculacion)
                VALUES (:id_nna, :id_tutor, :id_parentesco, TRUE, CURRENT_DATE)
                ON CONFLICT (id_nna, id_tutor) DO UPDATE SET
                    id_parentesco = EXCLUDED.id_parentesco
            ";
            $stmtRel = $pdo->prepare($qRel);
            $stmtRel->execute([
                ':id_nna'        => $id_nna,
                ':id_tutor'      => $id_tutor,
                ':id_parentesco' => $id_parentesco
            ]);

            $pdo->commit();
            $mensaje = "¡Tutor vinculado exitosamente! ✅";
            $tipoMensaje = "success";

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (strpos($e->getMessage(), 'chk_curp_tutor') !== false) {
                $mensaje = "La CURP del tutor debe tener 18 caracteres ⚠️";
            } elseif (strpos($e->getMessage(), 'chk_correo_tutor') !== false) {
                $mensaje = "El formato del correo no es válido ⚠️";
            } else {
                $mensaje = "Error al vincular el tutor ❌";
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
    <title>Asignar Tutor - Aurora</title>
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; padding: 20px; }
        .card { background: white; width: 100%; max-width: 650px; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        h1 { color: #1a2a6c; text-align: center; }
        label { display: block; margin-top: 12px; font-weight: bold; color: #34495e; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .row { display: flex; gap: 15px; }
        .row > div { flex: 1; }
        .btn { background: #27ae60; color: white; border: none; width: 100%; padding: 15px; margin-top: 25px; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; color: white; font-weight: bold; }
        .success { background: #2ecc71; }
        .error { background: #e74c3c; }
    </style>
</head>
<body>
<div class="card">
    <a href="ver_nnas.php" style="text-decoration:none; color:#7f8c8d; font-size:13px;">⬅ Volver a Directorio</a>
    <h1>Asignar Tutor</h1>

    <?php if ($mensaje): ?>
        <div class="alert <?= $tipoMensaje ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div style="background:#e3f2fd; padding:10px; border-left:5px solid #2196f3; margin-bottom:20px;">
        Expediente del Niño: <strong><?= htmlspecialchars($curp_nna) ?></strong>
    </div>

    <form method="POST">
        <input type="hidden" name="curp_nna_oculto" value="<?= htmlspecialchars($curp_nna) ?>">
        
        <label>CURP del Tutor:</label>
        <input type="text" name="curp_tutor" required maxlength="18" placeholder="CURP a 18 caracteres">

        <div class="row">
            <div>
                <label>Nombre(s):</label>
                <input type="text" name="nombre" required>
            </div>
            <div>
                <label>Apellido Paterno:</label>
                <input type="text" name="apellido_p" required>
            </div>
        </div>

        <div class="row">
            <div>
                <label>Apellido Materno:</label>
                <input type="text" name="apellido_m">
            </div>
            <div>
                <label>Parentesco / Relación:</label>
                <select name="id_parentesco" required>
                    <option value="" disabled selected>Seleccione...</option>
                    <?php foreach ($parentescos as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars(mb_strtoupper($p['nombre'], 'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h3 style="margin-top:20px; color:#27ae60; border-bottom:1px solid #ddd;">Contacto</h3>
        <div class="row">
            <div>
                <label>Teléfono:</label>
                <input type="text" name="telefono">
            </div>
            <div>
                <label>¿Adulto Mayor?</label>
                <select name="es_adulto_mayor">
                    <option value="No">No</option>
                    <option value="Si">Si</option>
                </select>
            </div>
        </div>

        <label>Correo Electrónico:</label>
        <input type="email" name="correo">

        <button type="submit" class="btn">FINALIZAR REGISTRO</button>
    </form>
</div>
</body>
</html>