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
//   nombre/datos vienen de nna (sin tabla persona)
//   sexo        -> cat_sexo
//   dirección   -> nna.dir_actual -> direccion -> cat_municipio -> entidad_federativa
//   tutor       -> nna_tutor(id_nna,id_tutor) -> tutor (el contacto principal)
$query = "
    SELECT 
        n.id_nna,
        n.curp,
        n.nombre, 
        n.prim_ap        AS apellido_paterno, 
        n.seg_ap         AS apellido_materno, 
        n.fecha_nacimiento, 
        s.nombre         AS sexo,
        n.situacion_calle,
        n.es_migrante,
        n.es_refugiado,
        n.poblacion_indigena,
        d.calle_dir      AS calle,
        d.no_ext_dir     AS num_ext,
        d.no_int_dir     AS num_int,
        d.colonia_abierta,
        d.codigo_postal,
        cm.nom_mun       AS municipio,
        ef.nom_ent       AS estado_dir,
        tut.t_nom,
        tut.t_ap,
        tut.t_tel,
        pais.nacionalidad
    FROM nna n
    LEFT JOIN cat_sexo s             ON s.id = n.id_sexo
    LEFT JOIN direccion d            ON d.id_dir = n.dir_actual
    LEFT JOIN cat_municipio cm       ON cm.id_municipio = d.id_municipio
    LEFT JOIN entidad_federativa ef  ON ef.id_ent = cm.id_ent
    LEFT JOIN LATERAL (
        SELECT t.nombre          AS t_nom,
               t.primer_apellido AS t_ap,
               t.telefono        AS t_tel
        FROM nna_tutor nt
        JOIN tutor t ON t.id_tutor = nt.id_tutor
        WHERE nt.id_nna = n.id_nna
        ORDER BY nt.es_contacto_ppal DESC
        LIMIT 1
    ) tut ON true
    LEFT JOIN LATERAL (
        SELECT string_agg(cp.nombre, ', ') AS nacionalidad
        FROM nna_nacionalidad nn
        JOIN cat_pais cp ON cp.id = nn.id_pais
        WHERE nn.id_nna = n.id_nna
    ) pais ON true
    WHERE n.curp = :curp
    LIMIT 1
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([':curp' => $curp]);
    $nna = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En producción: error_log($e->getMessage());
    die("Error al consultar el expediente.");
}

if (!$nna) {
    die("NNA no encontrado en el sistema Aurora.");
}

// Cargar contactos adicionales del NNA (nna_contacto_adicional + cat_tipo_contacto)
$contactos = [];
try {
    $stmtC = $pdo->prepare("
        SELECT ca.valor_contacto, ca.descripcion, tc.nombre AS tipo
        FROM nna_contacto_adicional ca
        JOIN cat_tipo_contacto tc ON tc.id = ca.id_tipo_contacto
        WHERE ca.id_nna = :id_nna
        ORDER BY tc.nombre
    ");
    $stmtC->execute([':id_nna' => $nna['id_nna']]);
    $contactos = $stmtC->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En producción: error_log($e->getMessage());
    $contactos = [];
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
        
        .btn-acciones { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-medico { background: #9b59b6; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 13px; transition: 0.3s; }
        .btn-medico:hover { opacity: 0.85; }
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
            <a href="ver_seguimientos.php?curp=<?= urlencode($curp) ?>" class="btn-medico" style="background:#27ae60;">📋 Ver Seguimientos</a>
            <a href="registrar_seguimiento.php?curp=<?= urlencode($curp) ?>" class="btn-medico" style="background:#2980b9;">📝 Nuevo Seguimiento</a>
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <a href="ver_nnas.php" style="text-decoration:none; color:#34495e; font-weight:bold;">⬅ Volver a la lista</a>
    </div>

    <div class="seccion-titulo">Datos de Identidad</div>
    <div class="grid-datos">
        <div class="dato-box"><label>Nombre Completo</label><span><?= htmlspecialchars(trim($nna['nombre'] . " " . $nna['apellido_paterno'] . " " . ($nna['apellido_materno'] ?? ''))) ?></span></div>
        <div class="dato-box"><label>CURP</label><span><?= htmlspecialchars($nna['curp'] ?? 'No registrada') ?></span></div>
        <div class="dato-box"><label>Fecha de Nacimiento</label><span><?= htmlspecialchars($nna['fecha_nacimiento']) ?></span></div>
        <div class="dato-box"><label>Sexo</label><span><?= htmlspecialchars(strtoupper($nna['sexo'] ?? 'No especificado')) ?></span></div>
        <div class="dato-box"><label>Nacionalidad</label><span><?= htmlspecialchars(strtoupper($nna['nacionalidad'] ?? 'No especificada')) ?></span></div>
    </div>

    <div class="seccion-titulo">Ubicación y Domicilio</div>
    <div class="grid-datos">
        <div class="dato-box"><label>Calle</label><span><?= htmlspecialchars($nna['calle'] ?? 'No registrado') ?></span></div>
        <div class="dato-box"><label>Núm. Exterior</label><span><?= htmlspecialchars($nna['num_ext'] ?? 'No registrado') ?></span></div>
        <div class="dato-box"><label>Núm. Interior</label><span><?= htmlspecialchars($nna['num_int'] ?? 'N/A') ?></span></div>
        <div class="dato-box"><label>Colonia</label><span><?= htmlspecialchars($nna['colonia_abierta'] ?? 'No registrada') ?></span></div>
        <div class="dato-box"><label>Código Postal</label><span><?= htmlspecialchars($nna['codigo_postal'] ?? 'N/A') ?></span></div>
        <div class="dato-box"><label>Municipio</label><span><?= htmlspecialchars($nna['municipio'] ?? 'No registrado') ?></span></div>
        <div class="dato-box"><label>Estado</label><span><?= htmlspecialchars($nna['estado_dir'] ?? 'No registrado') ?></span></div>
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

    <div class="seccion-titulo">Contactos Adicionales</div>
    <div class="grid-datos">
        <?php if (count($contactos) > 0): ?>
            <?php foreach ($contactos as $c): ?>
                <div class="dato-box">
                    <label><?= htmlspecialchars($c['tipo']) ?></label>
                    <span><?= htmlspecialchars($c['valor_contacto']) ?></span>
                    <?php if (!empty($c['descripcion'])): ?>
                        <br><small style="color:#7f8c8d;"><?= htmlspecialchars($c['descripcion']) ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="dato-box"><span style="color:#95a5a6; font-style:italic;">Sin contactos adicionales registrados</span></div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>