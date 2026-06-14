<?php  
session_start();  

if (!isset($_SESSION['usuario'])) {  
    header("Location: ../index.php");  
    exit();  
}  

$rolActual = $_SESSION['usuario']['rol'];  
if ($rolActual !== 'Administrador') {  
    header("Location: dashboard.php?error=acceso_denegado");  
    exit();  
}  

require '../config/database.php';  

$mensaje     = "";  
$tipoMensaje = "";  

if (isset($_GET['status']) && $_GET['status'] === 'success') {  
    $mensaje     = "NNA registrado correctamente ✅";  
    $tipoMensaje = "success";  
}  

// ============================================================
//  Cargar TODOS los catálogos
// ============================================================
$sexos        = $estados = $municipios = $paises  = [];
$escolaridades = $grupos_sanguineos = $motivos   = [];
$lenguas      = $niveles = $tipos_disc = $grados  = [];
$tipos_contacto = [];

try {
    $sexos           = $pdo->query("SELECT id, nombre FROM cat_sexo            ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $estados         = $pdo->query("SELECT id_ent, nom_ent FROM entidad_federativa ORDER BY nom_ent")->fetchAll(PDO::FETCH_ASSOC);
    $paises          = $pdo->query("SELECT id, nombre FROM cat_pais             ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $escolaridades   = $pdo->query("SELECT id, nombre FROM cat_escolaridad      ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $grupos_sanguineos = $pdo->query("SELECT id, nombre FROM cat_grupo_sanguineo ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $motivos         = $pdo->query("SELECT id, nombre FROM cat_motivo_ingreso   ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $lenguas         = $pdo->query("SELECT id, nombre FROM cat_lengua           ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $niveles         = $pdo->query("SELECT id, nombre FROM cat_nivel_competencia ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $tipos_disc      = $pdo->query("SELECT id, nombre FROM cat_tipo_discapacidad ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $grados          = $pdo->query("SELECT id, nombre FROM cat_grado_dependencia ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $tipos_contacto  = $pdo->query("SELECT id, nombre FROM cat_tipo_contacto    ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $municipios      = $pdo->query("
        SELECT m.id_municipio, m.nom_mun, e.nom_ent
        FROM   cat_municipio m
        INNER JOIN entidad_federativa e ON e.id_ent = m.id_ent
        ORDER BY e.nom_ent, m.nom_mun
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje     = "No se pudieron cargar los catálogos ❌";
    $tipoMensaje = "error";
}

// ============================================================
//  Procesar POST
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --- Datos personales ---
    $curp            = strtoupper(trim($_POST['curp']       ?? ''));
    $nombres         = strtoupper(trim($_POST['nombres']    ?? ''));
    $apellido_p      = strtoupper(trim($_POST['apellido_p'] ?? ''));
    $apellido_m      = !empty($_POST['apellido_m']) ? strtoupper(trim($_POST['apellido_m'])) : null;
    $nacimiento      = $_POST['nacimiento'] ?? '';
    $id_sexo         = !empty($_POST['id_sexo'])           ? (int)$_POST['id_sexo']           : null;
    $id_escolaridad  = !empty($_POST['id_escolaridad'])    ? (int)$_POST['id_escolaridad']    : null;
    $id_grupo_sang   = !empty($_POST['id_grupo_sanguineo']) ? (int)$_POST['id_grupo_sanguineo'] : null;
    $id_motivo       = !empty($_POST['id_motivo_ingreso']) ? (int)$_POST['id_motivo_ingreso'] : null;
    $id_estado_nac   = !empty($_POST['luga_nac_nna'])      ? (int)$_POST['luga_nac_nna']      : null;

    // --- Dirección ---
    $calle        = strtoupper(trim($_POST['calle']    ?? ''));
    $num_ext      = strtoupper(trim($_POST['num_ext']  ?? ''));
    $num_int      = !empty($_POST['num_int']) ? strtoupper(trim($_POST['num_int'])) : null;
    $colonia      = strtoupper(trim($_POST['colonia']  ?? ''));
    $cp           = trim($_POST['cp'] ?? '');
    $id_municipio = !empty($_POST['id_municipio']) ? (int)$_POST['id_municipio'] : null;

    // --- Vulnerabilidad ---
    $id_pais            = !empty($_POST['id_pais'])       ? (int)$_POST['id_pais'] : null;
    $situacion_calle    = ($_POST['situacion_calle']  ?? '') === 'Si';
    $es_migrante        = ($_POST['migrante']         ?? '') === 'Si';
    $es_refugiado       = ($_POST['refugiado']        ?? '') === 'Si';
    $poblacion_indigena = ($_POST['pob_indigena']     ?? '') === 'Si';

    // --- Lenguas (múltiples) ---
    $lenguas_ids    = $_POST['lengua_id']    ?? [];   // array de id_lengua
    $lenguas_nivel  = $_POST['lengua_nivel'] ?? [];   // array de id_nivel_competencia
    $lenguas_pref   = $_POST['lengua_pref']  ?? [];   // array de checkboxes
    $lenguas_interp = $_POST['lengua_interp']?? [];   // array de checkboxes

    // --- Discapacidades (múltiples) ---
    $disc_tipo  = $_POST['disc_tipo']  ?? [];
    $disc_grado = $_POST['disc_grado'] ?? [];
    $disc_diag  = $_POST['disc_diag']  ?? [];

    // --- Contactos adicionales (múltiples) ---
    $cont_tipo  = $_POST['cont_tipo']  ?? [];
    $cont_valor = $_POST['cont_valor'] ?? [];
    $cont_desc  = $_POST['cont_desc']  ?? [];

    $registrado_por = $_SESSION['usuario']['id_usuario'] ?? null;

    // Validaciones mínimas
    if (empty($nombres) || empty($apellido_p) || empty($nacimiento) || !$id_sexo || !$id_municipio || empty($colonia) || empty($cp)) {
        $mensaje     = "Nombre, apellido paterno, fecha de nacimiento, sexo, colonia, CP y municipio son obligatorios ⚠️";
        $tipoMensaje = "error";
    } elseif (!preg_match('/^\d{5}$/', $cp)) {
        $mensaje     = "El Código Postal debe tener exactamente 5 dígitos ⚠️";
        $tipoMensaje = "error";
    } else {
        try {
            $pdo->beginTransaction();

            // PASO 1: Insertar dirección
            $stmtDir = $pdo->prepare("
                INSERT INTO direccion (calle_dir, no_ext_dir, no_int_dir, colonia_abierta, codigo_postal, id_municipio)
                VALUES (:calle, :num_ext, :num_int, :colonia, :cp, :id_municipio)
                RETURNING id_dir
            ");
            $stmtDir->execute([
                ':calle'        => $calle      !== '' ? $calle    : null,
                ':num_ext'      => $num_ext    !== '' ? $num_ext  : null,
                ':num_int'      => $num_int,
                ':colonia'      => $colonia,
                ':cp'           => $cp,
                ':id_municipio' => $id_municipio
            ]);
            $id_dir = $stmtDir->fetchColumn();

            // PASO 2: Insertar NNA
            $folio = 'NNA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $stmtNna = $pdo->prepare("
                INSERT INTO nna (
                    folio_nna, nombre, prim_ap, seg_ap, fecha_nacimiento, curp,
                    id_sexo, id_escolaridad, id_motivo_ingreso, id_grupo_sanguineo,
                    dir_actual, luga_nac_nna,
                    situacion_calle, es_migrante, es_refugiado, poblacion_indigena,
                    registrado_por
                ) VALUES (
                    :folio, :nombre, :prim_ap, :seg_ap, :fnac, :curp,
                    :id_sexo, :id_escolaridad, :id_motivo, :id_grupo_sang,
                    :dir_actual, :luga_nac,
                    :sit_calle, :migrante, :refugiado, :indigena,
                    :registrado_por
                ) RETURNING id_nna
            ");
            $stmtNna->execute([
                ':folio'          => $folio,
                ':nombre'         => $nombres,
                ':prim_ap'        => $apellido_p,
                ':seg_ap'         => $apellido_m,
                ':fnac'           => $nacimiento,
                ':curp'           => $curp !== '' ? $curp : null,
                ':id_sexo'        => $id_sexo,
                ':id_escolaridad' => $id_escolaridad,
                ':id_motivo'      => $id_motivo,
                ':id_grupo_sang'  => $id_grupo_sang,
                ':dir_actual'     => $id_dir,
                ':luga_nac'       => $id_estado_nac,
                ':sit_calle'      => $situacion_calle    ? 'true' : 'false',
                ':migrante'       => $es_migrante        ? 'true' : 'false',
                ':refugiado'      => $es_refugiado       ? 'true' : 'false',
                ':indigena'       => $poblacion_indigena ? 'true' : 'false',
                ':registrado_por' => $registrado_por
            ]);
            $id_nna = $stmtNna->fetchColumn();

            // PASO 3: Nacionalidad
            if ($id_pais) {
                $pdo->prepare("INSERT INTO nna_nacionalidad (id_nna, id_pais) VALUES (:id_nna, :id_pais)")
                    ->execute([':id_nna' => $id_nna, ':id_pais' => $id_pais]);
            }

            // PASO 4: Lenguas
            $stmtLen = $pdo->prepare("
                INSERT INTO nna_lengua (id_nna, id_lengua, es_preferente, id_nivel_competencia, requiere_interprete)
                VALUES (:id_nna, :id_lengua, :es_pref, :id_nivel, :req_interp)
            ");
            foreach ($lenguas_ids as $i => $id_lengua) {
                if (empty($id_lengua)) continue;
                $stmtLen->execute([
                    ':id_nna'     => $id_nna,
                    ':id_lengua'  => (int)$id_lengua,
                    ':es_pref'    => isset($lenguas_pref[$i])   ? 'true' : 'false',
                    ':id_nivel'   => !empty($lenguas_nivel[$i]) ? (int)$lenguas_nivel[$i] : 1,
                    ':req_interp' => isset($lenguas_interp[$i]) ? 'true' : 'false',
                ]);
            }

            // PASO 5: Discapacidades
            $stmtDisc = $pdo->prepare("
                INSERT INTO nna_discapacidad (id_nna, id_tipo_discapacidad, id_grado_dependencia, diagnostico_medico_oficial)
                VALUES (:id_nna, :id_tipo, :id_grado, :diag)
            ");
            foreach ($disc_tipo as $i => $id_tipo) {
                if (empty($id_tipo)) continue;
                $stmtDisc->execute([
                    ':id_nna'  => $id_nna,
                    ':id_tipo' => (int)$id_tipo,
                    ':id_grado'=> !empty($disc_grado[$i]) ? (int)$disc_grado[$i] : 1,
                    ':diag'    => isset($disc_diag[$i])   ? 'true' : 'false',
                ]);
            }

            // PASO 6: Contactos adicionales
            $stmtCont = $pdo->prepare("
                INSERT INTO nna_contacto_adicional (id_nna, id_tipo_contacto, valor_contacto, descripcion)
                VALUES (:id_nna, :id_tipo, :valor, :desc)
            ");
            foreach ($cont_tipo as $i => $id_tipo) {
                if (empty($id_tipo) || empty($cont_valor[$i])) continue;
                $stmtCont->execute([
                    ':id_nna'  => $id_nna,
                    ':id_tipo' => (int)$id_tipo,
                    ':valor'   => trim($cont_valor[$i]),
                    ':desc'    => !empty($cont_desc[$i]) ? trim($cont_desc[$i]) : null,
                ]);
            }

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = $e->getMessage();
            if     (strpos($err, 'nna_curp_key')       !== false) $mensaje = "La CURP ya está registrada ⚠️";
            elseif (strpos($err, 'chk_curp_nna')       !== false) $mensaje = "La CURP debe tener 18 caracteres ⚠️";
            elseif (strpos($err, 'chk_codigo_postal')  !== false) $mensaje = "El Código Postal no es válido ⚠️";
            else                                                    $mensaje = "Error al registrar al NNA ❌ " . $err;
            $tipoMensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar NNA</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background: linear-gradient(135deg,#a18cd1,#fbc2eb); min-height:100vh; padding:30px 20px; }
        .card { background:#fff; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.1); padding:35px 40px; max-width:800px; margin:0 auto 30px; }
        h1 { color:#2c3e50; margin-bottom:5px; }
        h3 { color:#2980b9; margin:25px 0 10px; border-bottom:2px solid #eee; padding-bottom:6px; }
        label { display:block; margin-top:12px; font-weight:600; color:#2c3e50; font-size:14px; }
        input, select { width:100%; padding:9px 12px; margin-top:4px; border-radius:6px; border:1px solid #ccc; box-sizing:border-box; font-size:14px; }
        input[type=text], input[type=date] { text-transform:uppercase; }
        .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
        .grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; }
        .seccion { border:1px solid #e0e0e0; border-radius:10px; padding:20px; margin-top:20px; background:#fafafa; }
        .fila-dinamica { background:#fff; border:1px solid #ddd; border-radius:8px; padding:12px; margin-top:10px; position:relative; }
        .btn-add  { background:#2980b9; color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; margin-top:10px; font-size:13px; }
        .btn-del  { background:#e74c3c; color:#fff; border:none; padding:4px 10px; border-radius:4px; cursor:pointer; font-size:12px; position:absolute; top:10px; right:10px; }
        .btn-submit { background:#27ae60; color:#fff; border:none; padding:15px; width:100%; margin-top:25px; border-radius:8px; font-weight:700; font-size:16px; cursor:pointer; }
        .btn-submit:hover { background:#219150; }
        .alerta { padding:12px; border-radius:6px; margin-bottom:15px; text-align:center; font-weight:500; }
        .alerta.success { background:#d4edda; color:#155724; }
        .alerta.error   { background:#f8d7da; color:#721c24; }
        .check-row { display:flex; align-items:center; gap:8px; margin-top:8px; }
        .check-row input[type=checkbox] { width:auto; margin:0; }
        a.back { text-decoration:none; color:#7f8c8d; font-size:14px; display:inline-block; margin-bottom:15px; }
    </style>
</head>
<body>
<div class="card">
    <a href="dashboard.php" class="back">⬅ Dashboard</a>
    <h1>Registrar NNA</h1>

    <?php if ($mensaje): ?>
        <div class="alerta <?= $tipoMensaje ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="formNna">

        <!-- ====================================================
             SECCIÓN 1 — IDENTIDAD
        ==================================================== -->
        <h3>📋 Datos de Identidad</h3>

        <label>CURP (opcional):</label>
        <input type="text" name="curp" maxlength="18" placeholder="18 CARACTERES">

        <div class="grid2">
            <div>
                <label>Nombre(s): *</label>
                <input type="text" name="nombres" required>
            </div>
            <div>
                <label>Apellido Paterno: *</label>
                <input type="text" name="apellido_p" required>
            </div>
        </div>

        <label>Apellido Materno:</label>
        <input type="text" name="apellido_m">

        <div class="grid2">
            <div>
                <label>Fecha de Nacimiento: *</label>
                <input type="date" name="nacimiento" max="<?= date('Y-m-d') ?>" required>
            </div>
            <div>
                <label>Sexo: *</label>
                <select name="id_sexo" required>
                    <option value="" disabled selected>SELECCIONE</option>
                    <?php foreach ($sexos as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= mb_strtoupper($s['nombre'],'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid2">
            <div>
                <label>Escolaridad:</label>
                <select name="id_escolaridad">
                    <option value="">NO ESPECIFICADA</option>
                    <?php foreach ($escolaridades as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars(mb_strtoupper($e['nombre'],'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Grupo Sanguíneo:</label>
                <select name="id_grupo_sanguineo">
                    <option value="">DESCONOCIDO</option>
                    <?php foreach ($grupos_sanguineos as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid2">
            <div>
                <label>Motivo de Ingreso:</label>
                <select name="id_motivo_ingreso">
                    <option value="">NO ESPECIFICADO</option>
                    <?php foreach ($motivos as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars(mb_strtoupper($m['nombre'],'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Lugar de Nacimiento (Entidad):</label>
                <select name="luga_nac_nna">
                    <option value="">NO ESPECIFICADO</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?= $e['id_ent'] ?>"><?= htmlspecialchars(mb_strtoupper($e['nom_ent'],'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- ====================================================
             SECCIÓN 2 — DIRECCIÓN
        ==================================================== -->
        <h3>🏠 Dirección Actual</h3>

        <label>Calle:</label>
        <input type="text" name="calle">

        <div class="grid2">
            <div>
                <label>Núm. Exterior:</label>
                <input type="text" name="num_ext">
            </div>
            <div>
                <label>Núm. Interior:</label>
                <input type="text" name="num_int">
            </div>
        </div>

        <div class="grid2">
            <div>
                <label>Colonia: *</label>
                <input type="text" name="colonia" required>
            </div>
            <div>
                <label>Código Postal: *</label>
                <input type="text" name="cp" maxlength="5" pattern="\d{5}" placeholder="5 DÍGITOS" required>
            </div>
        </div>

        <label>Municipio/Alcaldía: *</label>
        <select name="id_municipio" required>
            <option value="" disabled selected>SELECCIONE MUNICIPIO</option>
            <?php foreach ($municipios as $m): ?>
                <option value="<?= $m['id_municipio'] ?>">
                    <?= htmlspecialchars(mb_strtoupper($m['nom_mun'] . ' — ' . $m['nom_ent'],'UTF-8')) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- ====================================================
             SECCIÓN 3 — VULNERABILIDAD
        ==================================================== -->
        <h3>⚠️ Datos de Vulnerabilidad</h3>

        <div class="grid2">
            <div>
                <label>Nacionalidad:</label>
                <select name="id_pais">
                    <option value="">NO ESPECIFICADA</option>
                    <?php foreach ($paises as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($p['nombre']==='México')?'selected':'' ?>>
                            <?= htmlspecialchars(mb_strtoupper($p['nombre'],'UTF-8')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>¿Situación de Calle?</label>
                <select name="situacion_calle">
                    <option value="No">No</option>
                    <option value="Si">Sí</option>
                </select>
            </div>
        </div>

        <div class="grid2">
            <div>
                <label>¿Es Migrante?</label>
                <select name="migrante">
                    <option value="No">No</option>
                    <option value="Si">Sí</option>
                </select>
            </div>
            <div>
                <label>¿Es Refugiado?</label>
                <select name="refugiado">
                    <option value="No">No</option>
                    <option value="Si">Sí</option>
                </select>
            </div>
        </div>

        <div class="grid2">
            <div>
                <label>¿Población Indígena?</label>
                <select name="pob_indigena">
                    <option value="No">No</option>
                    <option value="Si">Sí</option>
                </select>
            </div>
            <div></div>
        </div>

        <!-- ====================================================
             SECCIÓN 4 — LENGUAS
        ==================================================== -->
        <h3>🗣️ Lenguas</h3>
        <div class="seccion" id="contenedor-lenguas">
            <div class="fila-dinamica" id="lengua-0">
                <button type="button" class="btn-del" onclick="eliminarFila(this)">✕</button>
                <div class="grid2">
                    <div>
                        <label>Lengua:</label>
                        <select name="lengua_id[]">
                            <option value="">SELECCIONE</option>
                            <?php foreach ($lenguas as $l): ?>
                                <option value="<?= $l['id'] ?>"><?= htmlspecialchars(mb_strtoupper($l['nombre'],'UTF-8')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Nivel de Competencia:</label>
                        <select name="lengua_nivel[]">
                            <?php foreach ($niveles as $n): ?>
                                <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="check-row">
                    <input type="checkbox" name="lengua_pref[0]" value="1"> <span>¿Es lengua preferente?</span>
                    &nbsp;&nbsp;
                    <input type="checkbox" name="lengua_interp[0]" value="1"> <span>¿Requiere intérprete?</span>
                </div>
            </div>
        </div>
        <button type="button" class="btn-add" onclick="agregarLengua()">+ Agregar Lengua</button>

        <!-- ====================================================
             SECCIÓN 5 — DISCAPACIDADES
        ==================================================== -->
        <h3>♿ Discapacidades</h3>
        <div class="seccion" id="contenedor-disc">
            <div class="fila-dinamica" id="disc-0">
                <button type="button" class="btn-del" onclick="eliminarFila(this)">✕</button>
                <div class="grid2">
                    <div>
                        <label>Tipo de Discapacidad:</label>
                        <select name="disc_tipo[]">
                            <option value="">NINGUNA</option>
                            <?php foreach ($tipos_disc as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars(mb_strtoupper($t['nombre'],'UTF-8')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Grado de Dependencia:</label>
                        <select name="disc_grado[]">
                            <?php foreach ($grados as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="check-row">
                    <input type="checkbox" name="disc_diag[0]" value="1"> <span>¿Tiene diagnóstico médico oficial?</span>
                </div>
            </div>
        </div>
        <button type="button" class="btn-add" onclick="agregarDiscapacidad()">+ Agregar Discapacidad</button>

        <!-- ====================================================
             SECCIÓN 6 — CONTACTOS ADICIONALES
        ==================================================== -->
        <h3>📞 Contactos Adicionales</h3>
        <div class="seccion" id="contenedor-cont">
            <div class="fila-dinamica" id="cont-0">
                <button type="button" class="btn-del" onclick="eliminarFila(this)">✕</button>
                <div class="grid3">
                    <div>
                        <label>Tipo de Contacto:</label>
                        <select name="cont_tipo[]">
                            <option value="">SELECCIONE</option>
                            <?php foreach ($tipos_contacto as $tc): ?>
                                <option value="<?= $tc['id'] ?>"><?= htmlspecialchars(mb_strtoupper($tc['nombre'],'UTF-8')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Valor (número, usuario, etc.):</label>
                        <input type="text" name="cont_valor[]" placeholder="EJ: 55-1234-5678" style="text-transform:none;">
                    </div>
                    <div>
                        <label>Descripción (opcional):</label>
                        <input type="text" name="cont_desc[]" placeholder="EJ: FACEBOOK DE LA ABUELA">
                    </div>
                </div>
            </div>
        </div>
        <button type="button" class="btn-add" onclick="agregarContacto()">+ Agregar Contacto</button>

        <button type="submit" class="btn-submit">💾 Registrar NNA</button>
    </form>
</div>

<script>
// ---- Contadores para índices de checkboxes dinámicos ----
let cntLengua = 1;
let cntDisc   = 1;

function eliminarFila(btn) {
    btn.parentElement.remove();
}

function agregarLengua() {
    const i   = cntLengua++;
    const div = document.createElement('div');
    div.className = 'fila-dinamica';
    div.innerHTML = `
        <button type="button" class="btn-del" onclick="eliminarFila(this)">✕</button>
        <div class="grid2">
            <div>
                <label>Lengua:</label>
                <select name="lengua_id[]">
                    <option value="">SELECCIONE</option>
                    <?php foreach ($lenguas as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= htmlspecialchars(mb_strtoupper($l['nombre'],'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Nivel de Competencia:</label>
                <select name="lengua_nivel[]">
                    <?php foreach ($niveles as $n): ?>
                    <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="check-row">
            <input type="checkbox" name="lengua_pref[${i}]" value="1"> <span>¿Es lengua preferente?</span>
            &nbsp;&nbsp;
            <input type="checkbox" name="lengua_interp[${i}]" value="1"> <span>¿Requiere intérprete?</span>
        </div>`;
    document.getElementById('contenedor-lenguas').appendChild(div);
}

function agregarDiscapacidad() {
    const i   = cntDisc++;
    const div = document.createElement('div');
    div.className = 'fila-dinamica';
    div.innerHTML = `
        <button type="button" class="btn-del" onclick="eliminarFila(this)">✕</button>
        <div class="grid2">
            <div>
                <label>Tipo de Discapacidad:</label>
                <select name="disc_tipo[]">
                    <option value="">NINGUNA</option>
                    <?php foreach ($tipos_disc as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars(mb_strtoupper($t['nombre'],'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Grado de Dependencia:</label>
                <select name="disc_grado[]">
                    <?php foreach ($grados as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="check-row">
            <input type="checkbox" name="disc_diag[${i}]" value="1"> <span>¿Tiene diagnóstico médico oficial?</span>
        </div>`;
    document.getElementById('contenedor-disc').appendChild(div);
}

function agregarContacto() {
    const div = document.createElement('div');
    div.className = 'fila-dinamica';
    div.innerHTML = `
        <button type="button" class="btn-del" onclick="eliminarFila(this)">✕</button>
        <div class="grid3">
            <div>
                <label>Tipo de Contacto:</label>
                <select name="cont_tipo[]">
                    <option value="">SELECCIONE</option>
                    <?php foreach ($tipos_contacto as $tc): ?>
                    <option value="<?= $tc['id'] ?>"><?= htmlspecialchars(mb_strtoupper($tc['nombre'],'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Valor:</label>
                <input type="text" name="cont_valor[]" placeholder="EJ: 55-1234-5678" style="text-transform:none;">
            </div>
            <div>
                <label>Descripción (opcional):</label>
                <input type="text" name="cont_desc[]">
            </div>
        </div>`;
    document.getElementById('contenedor-cont').appendChild(div);
}
</script>
</body>
</html>