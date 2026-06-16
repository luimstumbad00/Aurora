<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/database.php';

$curp = $_GET['curp'] ?? '';
if (empty($curp)) die("Error: CURP no proporcionada.");

// ============================================================
//  CONSULTA PRINCIPAL
// ============================================================
$query = "
    SELECT
        n.id_nna, n.folio_nna, n.curp, n.nombre,
        n.prim_ap AS apellido_paterno, n.seg_ap AS apellido_materno,
        n.fecha_nacimiento,
        s.nombre            AS sexo,
        esc.nombre          AS escolaridad,
        gs.nombre           AS grupo_sanguineo,
        mi.nombre           AS motivo_ingreso,
        ef_nac.nom_ent      AS lugar_nacimiento,
        eq.nombre_equipo    AS equipo,
        eq.estado           AS equipo_estado,
        n.situacion_calle, n.es_migrante, n.es_refugiado, n.poblacion_indigena,
        d.calle_dir AS calle, d.no_ext_dir AS num_ext, d.no_int_dir AS num_int,
        d.colonia_abierta, d.codigo_postal,
        cm.nom_mun AS municipio, ef.nom_ent AS estado_dir,
        pais.nacionalidad,
        n.fecha_registro
    FROM nna n
    LEFT JOIN cat_sexo s                ON s.id        = n.id_sexo
    LEFT JOIN cat_escolaridad esc       ON esc.id      = n.id_escolaridad
    LEFT JOIN cat_grupo_sanguineo gs    ON gs.id       = n.id_grupo_sanguineo
    LEFT JOIN cat_motivo_ingreso mi     ON mi.id       = n.id_motivo_ingreso
    LEFT JOIN entidad_federativa ef_nac ON ef_nac.id_ent = n.luga_nac_nna
    LEFT JOIN equipo eq                 ON eq.id_equipo = n.id_equipo
    LEFT JOIN direccion d               ON d.id_dir    = n.dir_actual
    LEFT JOIN cat_municipio cm          ON cm.id_municipio = d.id_municipio
    LEFT JOIN entidad_federativa ef     ON ef.id_ent   = cm.id_ent
    LEFT JOIN LATERAL (
        SELECT string_agg(cp.nombre, ', ') AS nacionalidad
        FROM nna_nacionalidad nn JOIN cat_pais cp ON cp.id = nn.id_pais
        WHERE nn.id_nna = n.id_nna
    ) pais ON true
    WHERE n.curp = :curp LIMIT 1
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([':curp' => $curp]);
    $nna = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Error al consultar el expediente."); }

if (!$nna) die("NNA no encontrado en el sistema Aurora.");
$id_nna = $nna['id_nna'];

// ============================================================
//  TUTORES
// ============================================================
$tutores = [];
try {
    $stmtT = $pdo->prepare("
        SELECT t.nombre AS t_nom, t.primer_apellido AS t_ap, t.segundo_apellido AS t_ap2,
               t.telefono, t.correo, t.es_adulto_mayor,
               cp.nombre AS parentesco, nt.es_contacto_ppal, nt.fecha_vinculacion
        FROM nna_tutor nt
        JOIN tutor t ON t.id_tutor = nt.id_tutor
        JOIN cat_parentesco cp ON cp.id = nt.id_parentesco
        WHERE nt.id_nna = :id ORDER BY nt.es_contacto_ppal DESC, t.primer_apellido
    ");
    $stmtT->execute([':id' => $id_nna]);
    $tutores = $stmtT->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $tutores = []; }

// ============================================================
//  LENGUAS
// ============================================================
$lenguas = [];
try {
    $stmtL = $pdo->prepare("
        SELECT cl.nombre AS lengua, cnc.nombre AS nivel, nl.es_preferente, nl.requiere_interprete
        FROM nna_lengua nl
        JOIN cat_lengua cl ON cl.id = nl.id_lengua
        JOIN cat_nivel_competencia cnc ON cnc.id = nl.id_nivel_competencia
        WHERE nl.id_nna = :id ORDER BY nl.es_preferente DESC, cl.nombre
    ");
    $stmtL->execute([':id' => $id_nna]);
    $lenguas = $stmtL->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $lenguas = []; }

// ============================================================
//  DISCAPACIDADES
// ============================================================
$discapacidades = [];
try {
    $stmtD = $pdo->prepare("
        SELECT ctd.nombre AS tipo, cgd.nombre AS grado, nd.diagnostico_medico_oficial, nd.descripcion_adicional
        FROM nna_discapacidad nd
        JOIN cat_tipo_discapacidad ctd ON ctd.id = nd.id_tipo_discapacidad
        JOIN cat_grado_dependencia cgd ON cgd.id = nd.id_grado_dependencia
        WHERE nd.id_nna = :id ORDER BY ctd.nombre
    ");
    $stmtD->execute([':id' => $id_nna]);
    $discapacidades = $stmtD->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $discapacidades = []; }

// ============================================================
//  ENFERMEDADES
// ============================================================
$enfermedades = [];
try {
    $stmtE = $pdo->prepare("
        SELECT ce.codigo_cie, ce.nombre AS enfermedad, ne.bajo_tratamiento, ne.observaciones
        FROM nna_enfermedad ne
        JOIN cat_enfermedad ce ON ce.id_enfermedad = ne.id_enfermedad
        WHERE ne.id_nna = :id ORDER BY ce.nombre
    ");
    $stmtE->execute([':id' => $id_nna]);
    $enfermedades = $stmtE->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $enfermedades = []; }

// ============================================================
//  CONTACTOS
// ============================================================
$contactos = [];
try {
    $stmtC = $pdo->prepare("
        SELECT tc.nombre AS tipo, ca.valor_contacto, ca.descripcion
        FROM nna_contacto_adicional ca
        JOIN cat_tipo_contacto tc ON tc.id = ca.id_tipo_contacto
        WHERE ca.id_nna = :id ORDER BY tc.nombre
    ");
    $stmtC->execute([':id' => $id_nna]);
    $contactos = $stmtC->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $contactos = []; }

// ============================================================
//  MIEMBROS DEL EQUIPO
// ============================================================
$miembros_equipo = [];
if ($nna['equipo']) {
    try {
        $stmtEq = $pdo->prepare("
            SELECT u.nombre, u.apellido_paterno, r.nombre AS rol, u.correo
            FROM usuario_sistema u
            JOIN cat_rol_sistema r ON r.id = u.id_rol
            WHERE u.id_equipo = (SELECT id_equipo FROM nna WHERE id_nna = :id)
              AND u.estado = 'ACTIVO'
            ORDER BY r.nombre, u.apellido_paterno
        ");
        $stmtEq->execute([':id' => $id_nna]);
        $miembros_equipo = $stmtEq->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $miembros_equipo = []; }
}

function bool_badge($val, $label) {
    $si = ($val === 't' || $val === true || $val === '1');
    $cls = $si ? 'bg-true' : 'bg-false';
    $txt = $si ? 'SÍ' : 'NO';
    return "<span class=\"badge-perfil $cls\">$label: $txt</span>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil — <?= htmlspecialchars($nna['nombre']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background:linear-gradient(135deg,#a18cd1,#fbc2eb); min-height:100vh; padding:30px 20px; }
        .perfil-container { max-width:1000px; margin:0 auto; font-family:Arial,sans-serif; }
        .card { background:#fff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,.1); padding:28px 32px; margin-bottom:22px; }
        .header-perfil { display:flex; justify-content:space-between; align-items:center; border-bottom:3px solid #34495e; padding-bottom:14px; margin-bottom:6px; flex-wrap:wrap; gap:12px; }
        .header-perfil h1 { margin:0; color:#2c3e50; font-size:22px; }
        .folio { font-size:12px; color:#7f8c8d; margin-top:3px; }
        .seccion-titulo { background:#34495e; color:#fff; padding:7px 14px; border-radius:4px; margin:18px 0 12px; font-size:13px; text-transform:uppercase; font-weight:700; }
        .grid-datos { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; }
        .dato-box { background:#f9f9f9; padding:11px 14px; border:1px solid #eee; border-radius:6px; }
        .dato-box label { display:block; font-size:10px; color:#7f8c8d; text-transform:uppercase; font-weight:700; margin-bottom:3px; }
        .dato-box span { font-size:14px; color:#2c3e50; font-weight:600; }
        .badge-perfil { display:inline-block; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:700; color:#fff; margin:2px; }
        .bg-true  { background:#27ae60; }
        .bg-false { background:#95a5a6; }
        table.mini { width:100%; border-collapse:collapse; font-size:13px; }
        table.mini th { background:#34495e; color:#fff; padding:8px 10px; text-align:left; font-size:12px; }
        table.mini td { padding:8px 10px; border-bottom:1px solid #eee; vertical-align:top; }
        table.mini tr:hover td { background:#f5f5f5; }
        .btn-acciones { display:flex; gap:8px; flex-wrap:wrap; }
        .btn-acc { padding:9px 14px; border-radius:5px; text-decoration:none; font-weight:700; font-size:12px; color:#fff; transition:.2s; }
        .btn-acc:hover { opacity:.85; }
        .tag-pref   { background:#2980b9; color:#fff; font-size:10px; padding:2px 6px; border-radius:3px; margin-left:5px; }
        .tag-inter  { background:#e67e22; color:#fff; font-size:10px; padding:2px 6px; border-radius:3px; margin-left:5px; }
        .tag-diag   { background:#c0392b; color:#fff; font-size:10px; padding:2px 6px; border-radius:3px; margin-left:5px; }
        .tag-trat   { background:#16a085; color:#fff; font-size:10px; padding:2px 6px; border-radius:3px; margin-left:5px; }
        .tag-equipo { background:#8e44ad; color:#fff; font-size:11px; padding:3px 8px; border-radius:4px; }
        .sin-dato   { color:#95a5a6; font-style:italic; font-size:13px; }
        a.btn-back  { color:#34495e; font-weight:700; text-decoration:none; font-size:14px; }
    </style>
</head>
<body>
<div class="perfil-container">

    <!-- ENCABEZADO -->
    <div class="card">
        <div class="header-perfil">
            <div>
                <h1>📋 Expediente del NNA</h1>
                <div class="folio">Folio: <strong><?= htmlspecialchars($nna['folio_nna']) ?></strong>
                    &nbsp;|&nbsp; Registro: <?= htmlspecialchars($nna['fecha_registro']) ?></div>
            </div>
            <div class="btn-acciones">
                <a href="ver_salud_nna.php?curp=<?= urlencode($curp) ?>"           class="btn-acc" style="background:#9b59b6;">🩺 Salud</a>
                <a href="registrar_seguimiento.php?curp=<?= urlencode($curp) ?>"   class="btn-acc" style="background:#2980b9;">📝 Notas</a>
                <a href="registrar_visita.php?curp=<?= urlencode($curp) ?>"        class="btn-acc" style="background:#e67e22;">📅 Visitas</a>
                <a href="editar_nna.php?curp=<?= urlencode($curp) ?>"              class="btn-acc" style="background:#f39c12;">✏️ Editar</a>
                <a href="asignar_tutor.php?curp_nna=<?= urlencode($curp) ?>"       class="btn-acc" style="background:#27ae60;">👨‍👩‍👧 Tutor</a>
            </div>
        </div>
        <a href="ver_nnas.php" class="btn-back">⬅ Volver a la lista</a>
    </div>

    <!-- IDENTIDAD -->
    <div class="card">
        <div class="seccion-titulo">👤 Datos de Identidad</div>
        <div class="grid-datos">
            <div class="dato-box"><label>Nombre Completo</label><span><?= htmlspecialchars(trim($nna['nombre'].' '.$nna['apellido_paterno'].' '.($nna['apellido_materno']??''))) ?></span></div>
            <div class="dato-box"><label>CURP</label><span><?= htmlspecialchars($nna['curp'] ?? 'No registrada') ?></span></div>
            <div class="dato-box"><label>Fecha de Nacimiento</label><span><?= htmlspecialchars($nna['fecha_nacimiento']) ?></span></div>
            <div class="dato-box"><label>Sexo</label><span><?= htmlspecialchars(strtoupper($nna['sexo'] ?? 'No especificado')) ?></span></div>
            <div class="dato-box"><label>Escolaridad</label><span><?= htmlspecialchars($nna['escolaridad'] ?? 'No especificada') ?></span></div>
            <div class="dato-box"><label>Grupo Sanguíneo</label><span><?= htmlspecialchars($nna['grupo_sanguineo'] ?? 'Desconocido') ?></span></div>
            <div class="dato-box"><label>Motivo de Ingreso</label><span><?= htmlspecialchars($nna['motivo_ingreso'] ?? 'No especificado') ?></span></div>
            <div class="dato-box"><label>Lugar de Nacimiento</label><span><?= htmlspecialchars($nna['lugar_nacimiento'] ?? 'No especificado') ?></span></div>
            <div class="dato-box"><label>Nacionalidad(es)</label><span><?= htmlspecialchars(strtoupper($nna['nacionalidad'] ?? 'No especificada')) ?></span></div>
        </div>
    </div>

    <!-- EQUIPO -->
    <div class="card">
        <div class="seccion-titulo">🏥 Equipo Multidisciplinario</div>
        <?php if ($nna['equipo']): ?>
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                <span class="tag-equipo"><?= htmlspecialchars(strtoupper($nna['equipo'])) ?></span>
                <span style="font-size:13px; color:#7f8c8d;">Estado: <?= htmlspecialchars($nna['equipo_estado']) ?></span>
            </div>
            <?php if (count($miembros_equipo) > 0): ?>
                <table class="mini">
                    <thead><tr><th>Profesionista</th><th>Rol</th><th>Correo</th></tr></thead>
                    <tbody>
                        <?php foreach ($miembros_equipo as $me): ?>
                            <tr>
                                <td><?= htmlspecialchars($me['nombre'].' '.$me['apellido_paterno']) ?></td>
                                <td><?= htmlspecialchars(str_replace('_',' ',$me['rol'])) ?></td>
                                <td><?= htmlspecialchars($me['correo'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="sin-dato">El equipo no tiene profesionistas activos.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="sin-dato">⚠️ Sin equipo asignado.</p>
        <?php endif; ?>
    </div>

    <!-- VULNERABILIDAD -->
    <div class="card">
        <div class="seccion-titulo">⚠️ Indicadores de Vulnerabilidad</div>
        <div style="display:flex; flex-wrap:wrap; gap:8px; padding:5px 0;">
            <?= bool_badge($nna['situacion_calle'],    'Situación de Calle') ?>
            <?= bool_badge($nna['es_migrante'],        'Migrante') ?>
            <?= bool_badge($nna['es_refugiado'],       'Refugiado') ?>
            <?= bool_badge($nna['poblacion_indigena'], 'Población Indígena') ?>
        </div>
    </div>

    <!-- DOMICILIO -->
    <div class="card">
        <div class="seccion-titulo">🏠 Domicilio Actual</div>
        <div class="grid-datos">
            <div class="dato-box"><label>Calle</label><span><?= htmlspecialchars($nna['calle'] ?? 'No registrado') ?></span></div>
            <div class="dato-box"><label>Núm. Exterior</label><span><?= htmlspecialchars($nna['num_ext'] ?? 'S/N') ?></span></div>
            <div class="dato-box"><label>Núm. Interior</label><span><?= htmlspecialchars($nna['num_int'] ?? 'N/A') ?></span></div>
            <div class="dato-box"><label>Colonia</label><span><?= htmlspecialchars($nna['colonia_abierta'] ?? 'No registrada') ?></span></div>
            <div class="dato-box"><label>Código Postal</label><span><?= htmlspecialchars($nna['codigo_postal'] ?? 'N/A') ?></span></div>
            <div class="dato-box"><label>Municipio</label><span><?= htmlspecialchars($nna['municipio'] ?? 'No registrado') ?></span></div>
            <div class="dato-box"><label>Estado</label><span><?= htmlspecialchars($nna['estado_dir'] ?? 'No registrado') ?></span></div>
        </div>
    </div>

    <!-- TUTORES -->
    <div class="card">
        <div class="seccion-titulo">👨‍👩‍👧 Tutores / Responsables</div>
        <?php if (count($tutores) > 0): ?>
            <table class="mini">
                <thead><tr><th>Nombre</th><th>Parentesco</th><th>Teléfono</th><th>Correo</th><th>Adulto Mayor</th><th>Vinculación</th></tr></thead>
                <tbody>
                    <?php foreach ($tutores as $t): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($t['t_nom'].' '.$t['t_ap'].' '.($t['t_ap2']??'')) ?>
                                <?php if ($t['es_contacto_ppal']==='t'): ?><span class="tag-pref">PRINCIPAL</span><?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($t['parentesco']) ?></td>
                            <td><?= htmlspecialchars($t['telefono'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($t['correo'] ?? '—') ?></td>
                            <td><?= $t['es_adulto_mayor']==='t' ? '✔️ Sí' : 'No' ?></td>
                            <td><?= htmlspecialchars($t['fecha_vinculacion'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="sin-dato">⚠️ Sin tutores asignados.</p>
        <?php endif; ?>
    </div>

    <!-- LENGUAS -->
    <div class="card">
        <div class="seccion-titulo">🗣️ Lenguas</div>
        <?php if (count($lenguas) > 0): ?>
            <table class="mini">
                <thead><tr><th>Lengua</th><th>Nivel</th><th>Indicadores</th></tr></thead>
                <tbody>
                    <?php foreach ($lenguas as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars($l['lengua']) ?></td>
                            <td><?= htmlspecialchars($l['nivel']) ?></td>
                            <td>
                                <?php if ($l['es_preferente']==='t'): ?><span class="tag-pref">PREFERENTE</span><?php endif; ?>
                                <?php if ($l['requiere_interprete']==='t'): ?><span class="tag-inter">REQUIERE INTÉRPRETE</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="sin-dato">Sin lenguas registradas.</p>
        <?php endif; ?>
    </div>

    <!-- DISCAPACIDADES -->
    <div class="card">
        <div class="seccion-titulo">♿ Discapacidades</div>
        <?php if (count($discapacidades) > 0): ?>
            <table class="mini">
                <thead><tr><th>Tipo</th><th>Grado</th><th>Diagnóstico</th><th>Descripción</th></tr></thead>
                <tbody>
                    <?php foreach ($discapacidades as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['tipo']) ?></td>
                            <td><?= htmlspecialchars($d['grado']) ?></td>
                            <td><?= $d['diagnostico_medico_oficial']==='t' ? '<span class="tag-diag">SÍ</span>' : 'No' ?></td>
                            <td><?= htmlspecialchars($d['descripcion_adicional'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="sin-dato">Sin discapacidades registradas.</p>
        <?php endif; ?>
    </div>

    <!-- ENFERMEDADES -->
    <div class="card">
        <div class="seccion-titulo">🏥 Enfermedades Diagnosticadas</div>
        <?php if (count($enfermedades) > 0): ?>
            <table class="mini">
                <thead><tr><th>CIE-10</th><th>Enfermedad</th><th>Tratamiento</th><th>Observaciones</th></tr></thead>
                <tbody>
                    <?php foreach ($enfermedades as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['codigo_cie'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($e['enfermedad']) ?></td>
                            <td><?= $e['bajo_tratamiento']==='t' ? '<span class="tag-trat">EN TRATAMIENTO</span>' : 'No' ?></td>
                            <td><?= htmlspecialchars($e['observaciones'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="sin-dato">Sin enfermedades registradas.</p>
        <?php endif; ?>
    </div>

    <!-- CONTACTOS -->
    <div class="card">
        <div class="seccion-titulo">📞 Contactos Adicionales</div>
        <?php if (count($contactos) > 0): ?>
            <table class="mini">
                <thead><tr><th>Tipo</th><th>Valor</th><th>Descripción</th></tr></thead>
                <tbody>
                    <?php foreach ($contactos as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['tipo']) ?></td>
                            <td><?= htmlspecialchars($c['valor_contacto']) ?></td>
                            <td><?= htmlspecialchars($c['descripcion'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="sin-dato">Sin contactos adicionales registrados.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>