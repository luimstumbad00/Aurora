<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/database.php';

$curp = $_GET['curp'] ?? '';

if (empty($curp)) {
    die("Error: CURP no proporcionada.");
}

// Modelo normalizado:
//   nna_enfermedad usa id_nna (no curp) -> resolvemos el id por CURP
//   cat_enfermedad tiene: nombre, codigo_cie (no nombre_padecimiento/tipo_enfermedad)
//   nna_enfermedad tiene: bajo_tratamiento (bool), observaciones (text)
$enfermedades = [];
try {
    $query = "
        SELECT 
            ce.nombre        AS nombre_padecimiento,
            ce.codigo_cie,
            ne.bajo_tratamiento,
            ne.observaciones
        FROM nna n
        JOIN nna_enfermedad ne ON ne.id_nna = n.id_nna
        JOIN cat_enfermedad ce ON ce.id_enfermedad = ne.id_enfermedad
        WHERE n.curp = :curp
        ORDER BY ce.nombre
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':curp' => $curp]);
    $enfermedades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En producción: error_log($e->getMessage());
    $enfermedades = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Expediente Médico</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.container { max-width: 900px; margin: 30px auto; background: white; padding: 20px; border-radius: 8px; }
.card-enfermedad { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; position: relative; }
.badge-tipo { position: absolute; top: 15px; right: 15px; padding: 5px 10px; border-radius: 20px; font-size: 10px; color: white; font-weight: bold; }
.aguda { background: #e67e22; }
.cronica { background: #e74c3c; }
.controlada { color: #27ae60; font-weight: bold; }
</style>
</head>
<body>
<div class="container">
<h2>🏥 Historial de Salud: <?= htmlspecialchars($curp) ?></h2>
<a href="registrar_enfermedad.php?curp_nna=<?= urlencode($curp) ?>" class="btn-asignar" style="display:inline-block; margin-bottom:20px;">+ Agregar Padecimiento</a>
<?php if (count($enfermedades) > 0): ?>
<?php foreach ($enfermedades as $enf): ?>
<div class="card-enfermedad">
<span class="badge-tipo cronica">
<?= htmlspecialchars($enf['codigo_cie'] ?? 'CIE N/D') ?>
</span>
<h3><?= htmlspecialchars($enf['nombre_padecimiento']) ?></h3>
<p><strong>Observaciones:</strong> <?= htmlspecialchars($enf['observaciones'] ?? 'Sin observaciones') ?></p>
<p>
<span class="controlada"><?= $enf['bajo_tratamiento'] == 't' ? '✔ En tratamiento' : '❌ Sin tratamiento' ?></span>
</p>
</div>
<?php endforeach; ?>
<?php else: ?>
<p>No hay antecedentes médicos registrados para este NNA.</p>
<?php endif; ?>
<br><a href="ver_perfil_nna.php?curp=<?= urlencode($curp) ?>">⬅ Volver al Perfil</a>
</div>
</body>
</html>