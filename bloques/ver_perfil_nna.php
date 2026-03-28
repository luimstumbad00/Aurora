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

// Consulta completa incluyendo todos los campos de tu formulario de registro
$query = "SELECT n.*, t.nombre as t_nom, t.apellido_paterno as t_ap, t.telefono as t_tel 
          FROM nna n 
          LEFT JOIN nna_tutor nt ON n.curp = nt.curp_nna 
          LEFT JOIN tutor t ON nt.curp_tutor = t.curp 
          WHERE n.curp = '" . pg_escape_string($conn, $curp) . "'";

$res = pg_query($conn, $query);
$nna = pg_fetch_assoc($res);

if (!$nna) {
    die("NNA no encontrado en el sistema Aurora.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil - <?= htmlspecialchars($nna['nombre']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .perfil-container { max-width: 900px; margin: 30px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); font-family: Arial, sans-serif; }
        .header-perfil { border-bottom: 3px solid #34495e; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        
        .seccion-titulo { background: #34495e; color: white; padding: 8px 15px; border-radius: 4px; margin: 20px 0 10px 0; font-size: 14px; text-transform: uppercase; }
        
        .grid-datos { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        
        .dato-box { background: #fdfdfd; padding: 12px; border: 1px solid #eee; border-radius: 6px; }
        .dato-box label { display: block; font-size: 11px; color: #7f8c8d; text-transform: uppercase; font-weight: bold; margin-bottom: 4px; }
        .dato-box span { font-size: 15px; color: #2c3e50; font-weight: 600; }
        
        .badge-perfil { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; color: white; }
        .bg-true { background-color: #27ae60; }
        .bg-false { background-color: #95a5a6; }
        
        .btn-acciones { display: flex; gap: 10px; }
        .btn-medico { background: #9b59b6; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 13px; transition: 0.3s; }
        .btn-medico:hover { background: #8e44ad; }
    </style>
</head>
<body>

<div class="perfil-container">
    <div class="header-perfil">
        <div>
            <h1 style="margin:0; color: #2c3e50;">Expediente del NNA</h1>
            <small style="color: #7f8c8d;">Sistema de Gestión Multidisciplinario Aurora</small>
        </div>
        <div class="btn-acciones">
            <a href="ver_salud_nna.php?curp=<?= urlencode($curp) ?>" class="btn-medico">🩺 Historial Médico</a>
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <a href="ver_nnas.php" style="text-decoration:none; color:#34495e; font-weight:bold;">⬅ Volver a la lista</a>
    </div>

    <div class="seccion-titulo">Datos de Identidad</div>
    <div class="grid-datos">
        <div class="dato-box"><label>Nombre Completo</label><span><?= htmlspecialchars($nna['nombre'] . " " . $nna['apellido_paterno'] . " " . $nna['apellido_materno']) ?></span></div>
        <div class="dato-box"><label>CURP</label><span><?= htmlspecialchars($nna['curp']) ?></span></div>
        <div class="dato-box"><label>Fecha de Nacimiento</label><span><?= htmlspecialchars($nna['fecha_nacimiento']) ?></span></div>
        <div class="dato-box"><label>Sexo</label><span><?= htmlspecialchars(strtoupper($nna['sexo'])) ?></span></div>
        <div class="dato-box"><label>Nacionalidad</label><span><?= htmlspecialchars(strtoupper($nna['nacionalidad'])) ?></span></div>
    </div>

    <div class="seccion-titulo">Ubicación y Domicilio</div>
    <div class="grid-datos">
        <div class="dato-box"><label>Calle</label><span><?= htmlspecialchars($nna['calle']) ?></span></div>
        <div class="dato-box"><label>Núm. Exterior</label><span><?= htmlspecialchars($nna['num_ext']) ?></span></div>
        <div class="dato-box"><label>Núm. Interior</label><span><?= htmlspecialchars($nna['num_int'] ?? 'N/A') ?></span></div>
        <div class="dato-box"><label>Tutor Responsable</label><span><?= $nna['t_nom'] ? htmlspecialchars($nna['t_nom']." ".$nna['t_ap']) : 'SIN TUTOR ASIGNADO' ?></span></div>
    </div>

    <div class="seccion-titulo">Indicadores Sociales y Vulnerabilidad</div>
    <div class="grid-datos">
        <div class="dato-box">
            <label>Situación de Calle</label>
            <span class="badge-perfil <?= $nna['situacion_calle'] == 't' ? 'bg-true' : 'bg-false' ?>">
                <?= $nna['situacion_calle'] == 't' ? 'SÍ' : 'NO' ?>
            </span>
        </div>
        <div class="dato-box">
            <label>Es Migrante</label>
            <span class="badge-perfil <?= $nna['es_migrante'] == 't' ? 'bg-true' : 'bg-false' ?>">
                <?= $nna['es_migrante'] == 't' ? 'SÍ' : 'NO' ?>
            </span>
        </div>
        <div class="dato-box">
            <label>Es Refugiado</label>
            <span class="badge-perfil <?= $nna['es_refugiado'] == 't' ? 'bg-true' : 'bg-false' ?>">
                <?= $nna['es_refugiado'] == 't' ? 'SÍ' : 'NO' ?>
            </span>
        </div>
        <div class="dato-box">
            <label>Población Indígena</label>
            <span class="badge-perfil <?= $nna['poblacion_indigena'] == 't' ? 'bg-true' : 'bg-false' ?>">
                <?= $nna['poblacion_indigena'] == 't' ? 'SÍ' : 'NO' ?>
            </span>
        </div>
    </div>

</div>

</body>
</html>