<?php
session_start();
require '../config/database.php';

$curp = $_GET['curp'] ?? '';

// CORRECCIÓN: La columna en la tabla persona_enfermedad ahora se llama 'curp', no 'curp_nna'
$query = "SELECT pe.*, ce.nombre_padecimiento, ce.tipo_enfermedad 
          FROM persona_enfermedad pe
          JOIN cat_enfermedad ce ON pe.id_enfermedad = ce.id_enfermedad
          WHERE pe.curp = '$curp'";

$res = pg_query($conn, $query);
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
    <a href="registrar_enfermedad.php?curp_nna=<?= $curp ?>" class="btn-asignar" style="display:inline-block; margin-bottom:20px;">+ Agregar Padecimiento</a>

    <?php if (pg_num_rows($res) > 0): ?>
        <?php while($enf = pg_fetch_assoc($res)): ?>
            <div class="card-enfermedad">
                <span class="badge-tipo <?= strtolower($enf['tipo_enfermedad']) == 'aguda' ? 'aguda' : 'cronica' ?>">
                    <?= $enf['tipo_enfermedad'] ?>
                </span>
                <h3><?= $enf['nombre_padecimiento'] ?></h3>
                <p><strong>Tratamiento:</strong> <?= $enf['tratamiento_actual'] ?></p>
                <p>
                    Cronica: <?= $enf['es_cronica'] == 't' ? 'SÍ' : 'NO' ?> | 
                    <span class="controlada"><?= $enf['esta_controlada'] == 't' ? '✔ Bajo Control' : '❌ Sin Control' ?></span>
                </p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No hay antecedentes médicos registrados para este NNA.</p>
    <?php endif; ?>
    
    <br><a href="ver_perfil_nna.php?curp=<?= $curp ?>">⬅ Volver al Perfil</a>
</div>
</body>
</html>