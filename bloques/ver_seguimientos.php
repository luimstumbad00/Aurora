<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/database.php';

$curp_nna = $_GET['curp'] ?? '';

if (empty($curp_nna)) {
    die("Error: no se especificó el NNA.");
}

// Datos del NNA + todos sus seguimientos (de todo el equipo), más reciente primero
try {
    $stmtNna = $pdo->prepare("SELECT id_nna, nombre, prim_ap, seg_ap FROM nna WHERE curp = :curp LIMIT 1");
    $stmtNna->execute([':curp' => $curp_nna]);
    $nna = $stmtNna->fetch(PDO::FETCH_ASSOC);

    if (!$nna) {
        die("NNA no encontrado.");
    }

    $stmt = $pdo->prepare("
        SELECT 
            es.fecha_atencion,
            es.notas_evolucion,
            es.archivo_adjunto_path,
            ar.nombre        AS area,
            u.nombre         AS u_nom,
            u.apellido_paterno AS u_ap
        FROM expediente_seguimiento es
        JOIN cat_rol_sistema ar    ON ar.id = es.id_area_atencion
        JOIN usuario_sistema u     ON u.id_usuario = es.id_usuario
        WHERE es.id_nna = :id_nna
        ORDER BY es.fecha_atencion DESC
    ");
    $stmt->execute([':id_nna' => $nna['id_nna']]);
    $seguimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En producción: error_log($e->getMessage());
    $seguimientos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Seguimiento - Aurora</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container { max-width:900px; margin:30px auto; background:white; padding:30px; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.1); font-family:Arial, sans-serif; }
        .header { border-bottom:3px solid #34495e; padding-bottom:15px; margin-bottom:25px; display:flex; justify-content:space-between; align-items:center; }
        .btn-nuevo { background:#27ae60; color:white; padding:10px 15px; text-decoration:none; border-radius:5px; font-weight:bold; font-size:13px; }
        .seg-card { border:1px solid #ddd; border-left:5px solid #3498db; padding:15px; margin-bottom:12px; border-radius:6px; background:#fdfdfd; }
        .seg-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
        .seg-area { background:#34495e; color:white; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:bold; }
        .seg-fecha { color:#7f8c8d; font-size:13px; }
        .seg-autor { color:#2c3e50; font-size:13px; font-weight:bold; margin-bottom:6px; }
        .seg-notas { color:#2c3e50; font-size:14px; white-space:pre-wrap; }
        .seg-archivo { margin-top:8px; font-size:12px; color:#8e44ad; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1 style="margin:0; color:#2c3e50;">Historial de Seguimiento</h1>
            <small style="color:#7f8c8d;"><?= htmlspecialchars(trim($nna['nombre'] . " " . $nna['prim_ap'] . " " . ($nna['seg_ap'] ?? ''))) ?></small>
        </div>
        <a href="registrar_seguimiento.php?curp=<?= urlencode($curp_nna) ?>" class="btn-nuevo">➕ Nuevo Seguimiento</a>
    </div>

    <div style="margin-bottom:20px;">
        <a href="ver_perfil_nna.php?curp=<?= urlencode($curp_nna) ?>" style="text-decoration:none; color:#34495e; font-weight:bold;">⬅ Volver al Perfil</a>
    </div>

    <?php if (count($seguimientos) > 0): ?>
        <?php foreach ($seguimientos as $s): ?>
            <div class="seg-card">
                <div class="seg-head">
                    <span class="seg-area"><?= htmlspecialchars(str_replace('_', ' ', $s['area'])) ?></span>
                    <span class="seg-fecha"><?= htmlspecialchars($s['fecha_atencion']) ?></span>
                </div>
                <div class="seg-autor">👤 <?= htmlspecialchars($s['u_nom'] . " " . $s['u_ap']) ?></div>
                <div class="seg-notas"><?= htmlspecialchars($s['notas_evolucion'] ?? 'Sin notas registradas') ?></div>
                <?php if (!empty($s['archivo_adjunto_path'])): ?>
                    <div class="seg-archivo">📎 <?= htmlspecialchars($s['archivo_adjunto_path']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align:center; color:#7f8c8d;">Aún no hay seguimientos registrados para este NNA.</p>
    <?php endif; ?>
</div>
</body>
</html>