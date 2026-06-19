<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$rolActual = $_SESSION['usuario']['rol'];
if ($rolActual !== 'Administrador' && $rolActual !== 'Trabajador_Social') {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

require '../config/database.php';

$mensaje     = "";
$tipoMensaje = "";

if (!isset($_GET['curp'])) {
    header("Location: ver_nnas.php");
    exit();
}
$curp_original = trim($_GET['curp']);

// Catálogos
$sexos = $paises = $tipos_contacto = $escolaridades = [];
$grupos_sanguineos = $motivos = $estados = $municipios = [];
$equipos = [];

try {
    $sexos             = $pdo->query("SELECT id, nombre FROM cat_sexo            ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $paises            = $pdo->query("SELECT id, nombre FROM cat_pais             ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $tipos_contacto    = $pdo->query("SELECT id, nombre FROM cat_tipo_contacto    ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $escolaridades     = $pdo->query("SELECT id, nombre FROM cat_escolaridad      ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $grupos_sanguineos = $pdo->query("SELECT id, nombre FROM cat_grupo_sanguineo  ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $motivos           = $pdo->query("SELECT id, nombre FROM cat_motivo_ingreso   ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $estados           = $pdo->query("SELECT id_ent, nom_ent FROM entidad_federativa ORDER BY nom_ent")->fetchAll(PDO::FETCH_ASSOC);
    $equipos           = $pdo->query("SELECT id_equipo, nombre_equipo FROM equipo WHERE estado = 'ACTIVO' ORDER BY nombre_equipo")->fetchAll(PDO::FETCH_ASSOC);
    $municipios        = $pdo->query("
        SELECT m.id_municipio, m.nom_mun, e.nom_ent
        FROM   cat_municipio m
        JOIN   entidad_federativa e ON e.id_ent = m.id_ent
        ORDER BY e.nom_ent, m.nom_mun
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje     = "Error al cargar catálogos ❌";
    $tipoMensaje = "error";
}

// Resolver id_nna
try {
    $stmtId = $pdo->prepare("SELECT id_nna FROM nna WHERE curp = :curp LIMIT 1");
    $stmtId->execute([':curp' => $curp_original]);
    $id_nna_actual = $stmtId->fetchColumn();
} catch (PDOException $e) { $id_nna_actual = null; }
if (!$id_nna_actual) die("NNA no encontrado.");

// AGREGAR CONTACTO
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['agregar_contacto'])) {
    $id_tipo_contacto = !empty($_POST['id_tipo_contacto']) ? (int)$_POST['id_tipo_contacto'] : null;
    $valor_contacto   = trim($_POST['valor_contacto'] ?? '');
    $descripcion      = trim($_POST['descripcion_contacto'] ?? '');
    if (!$id_tipo_contacto || $valor_contacto === '') {
        $mensaje = "Debes elegir el tipo de contacto y escribir su valor ⚠️"; $tipoMensaje = "error";
    } else {
        try {
            $pdo->prepare("INSERT INTO nna_contacto_adicional (id_nna, id_tipo_contacto, valor_contacto, descripcion) VALUES (:id_nna, :id_tipo, :valor, :desc)")
                ->execute([':id_nna'=>$id_nna_actual, ':id_tipo'=>$id_tipo_contacto, ':valor'=>$valor_contacto, ':desc'=>$descripcion!==''?$descripcion:null]);
            header("Location: ".$_SERVER['PHP_SELF']."?curp=".urlencode($curp_original)."&c=ok"); exit();
        } catch (PDOException $e) {
            $mensaje = strpos($e->getMessage(),'uq_nna_contacto')!==false ? "Ese contacto ya está registrado ⚠️" : "Error al agregar el contacto ❌";
            $tipoMensaje = "error";
        }
    }
}

// BORRAR CONTACTO
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['borrar_contacto'])) {
    $id_contacto = (int)($_POST['id_contacto'] ?? 0);
    try {
        $pdo->prepare("DELETE FROM nna_contacto_adicional WHERE id_contacto = :id AND id_nna = :id_nna")
            ->execute([':id'=>$id_contacto, ':id_nna'=>$id_nna_actual]);
        header("Location: ".$_SERVER['PHP_SELF']."?curp=".urlencode($curp_original)."&c=del"); exit();
    } catch (PDOException $e) { $mensaje = "Error al borrar el contacto ❌"; $tipoMensaje = "error"; }
}

// PRG
if (isset($_GET['c'])) {
    if ($_GET['c']==='ok')  { $mensaje = "Contacto agregado correctamente ✅"; $tipoMensaje = "success"; }
    if ($_GET['c']==='del') { $mensaje = "Contacto eliminado correctamente ✅"; $tipoMensaje = "success"; }
}

// ELIMINAR NNA
if (isset($_POST['eliminar_nna'])) {
    try {
        $pdo->prepare("DELETE FROM nna WHERE curp = :curp")->execute([':curp' => $curp_original]);
        header("Location: ver_nnas.php?mensaje=eliminado_exito"); exit();
    } catch (PDOException $e) { $mensaje = "Error al eliminar el registro ❌"; $tipoMensaje = "error"; }
}

// ACTUALIZAR NNA (v8: +apodo)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['actualizar_nna'])) {
    $nombres         = strtoupper(trim($_POST['nombres']    ?? ''));
    $apodo           = trim($_POST['apodo'] ?? '');
    $apellido_p      = strtoupper(trim($_POST['apellido_p'] ?? ''));
    $apellido_m      = strtoupper(trim($_POST['apellido_m'] ?? ''));
    $nacimiento      = $_POST['nacimiento'] ?? '';
    $id_sexo         = !empty($_POST['id_sexo'])            ? (int)$_POST['id_sexo']            : null;
    $id_escolaridad  = !empty($_POST['id_escolaridad'])     ? (int)$_POST['id_escolaridad']     : null;
    $id_grupo_sang   = !empty($_POST['id_grupo_sanguineo']) ? (int)$_POST['id_grupo_sanguineo'] : null;
    $id_motivo       = !empty($_POST['id_motivo_ingreso'])  ? (int)$_POST['id_motivo_ingreso']  : null;
    $id_estado_nac   = !empty($_POST['luga_nac_nna'])       ? (int)$_POST['luga_nac_nna']       : null;
    $id_pais         = !empty($_POST['id_pais'])            ? (int)$_POST['id_pais']            : null;
    $id_equipo       = !empty($_POST['id_equipo'])          ? (int)$_POST['id_equipo']          : null;

    $situacion_calle    = ($_POST['situacion_calle']    ?? '') === 'Si' ? 'true' : 'false';
    $es_migrante        = ($_POST['es_migrante']        ?? '') === 'Si' ? 'true' : 'false';
    $es_refugiado       = ($_POST['es_refugiado']       ?? '') === 'Si' ? 'true' : 'false';
    $poblacion_indigena = ($_POST['poblacion_indigena'] ?? '') === 'Si' ? 'true' : 'false';

    $calle        = strtoupper(trim($_POST['calle']    ?? ''));
    $num_ext      = strtoupper(trim($_POST['num_ext']  ?? ''));
    $num_int      = !empty($_POST['num_int']) ? strtoupper(trim($_POST['num_int'])) : null;
    $colonia      = strtoupper(trim($_POST['colonia']  ?? ''));
    $cp           = trim($_POST['cp'] ?? '');
    $id_municipio = !empty($_POST['id_municipio']) ? (int)$_POST['id_municipio'] : null;

    if (!$id_sexo) {
        $mensaje = "El sexo es obligatorio ⚠️"; $tipoMensaje = "error";
    } elseif (!empty($cp) && !preg_match('/^\d{5}$/', $cp)) {
        $mensaje = "El Código Postal debe tener 5 dígitos ⚠️"; $tipoMensaje = "error";
    } else {
        try {
            $pdo->beginTransaction();

            $pdo->prepare("
                UPDATE nna SET
                    nombre = :nombre, apodo = :apodo, prim_ap = :ap_p, seg_ap = :ap_m,
                    fecha_nacimiento = :fnac, id_sexo = :id_sexo, id_escolaridad = :id_escolaridad,
                    id_grupo_sanguineo = :id_grupo_sang, id_motivo_ingreso = :id_motivo,
                    luga_nac_nna = :luga_nac, id_equipo = :id_equipo,
                    situacion_calle = :sit_calle, es_migrante = :migrante,
                    es_refugiado = :refugiado, poblacion_indigena = :indigena
                WHERE id_nna = :id_nna
            ")->execute([
                ':nombre'         => $nombres,
                ':apodo'          => $apodo !== '' ? $apodo : null,
                ':ap_p'           => $apellido_p,
                ':ap_m'           => $apellido_m !== '' ? $apellido_m : null,
                ':fnac'           => $nacimiento,
                ':id_sexo'        => $id_sexo,
                ':id_escolaridad' => $id_escolaridad,
                ':id_grupo_sang'  => $id_grupo_sang,
                ':id_motivo'      => $id_motivo,
                ':luga_nac'       => $id_estado_nac,
                ':id_equipo'      => $id_equipo,
                ':sit_calle'      => $situacion_calle,
                ':migrante'       => $es_migrante,
                ':refugiado'      => $es_refugiado,
                ':indigena'       => $poblacion_indigena,
                ':id_nna'         => $id_nna_actual
            ]);

            if ($id_municipio && $colonia !== '') {
                $id_dir_actual = $pdo->prepare("SELECT dir_actual FROM nna WHERE id_nna = :id");
                $id_dir_actual->execute([':id' => $id_nna_actual]);
                $id_dir = $id_dir_actual->fetchColumn();

                if ($id_dir) {
                    $pdo->prepare("UPDATE direccion SET calle_dir=:calle, no_ext_dir=:num_ext, no_int_dir=:num_int, colonia_abierta=:colonia, codigo_postal=:cp, id_municipio=:id_municipio WHERE id_dir=:id_dir")
                        ->execute([':calle'=>$calle!==''?$calle:null, ':num_ext'=>$num_ext!==''?$num_ext:null, ':num_int'=>$num_int, ':colonia'=>$colonia, ':cp'=>$cp, ':id_municipio'=>$id_municipio, ':id_dir'=>$id_dir]);
                } else {
                    $stmtDir = $pdo->prepare("INSERT INTO direccion (calle_dir, no_ext_dir, no_int_dir, colonia_abierta, codigo_postal, id_municipio) VALUES (:calle, :num_ext, :num_int, :colonia, :cp, :id_municipio) RETURNING id_dir");
                    $stmtDir->execute([':calle'=>$calle!==''?$calle:null, ':num_ext'=>$num_ext!==''?$num_ext:null, ':num_int'=>$num_int, ':colonia'=>$colonia, ':cp'=>$cp, ':id_municipio'=>$id_municipio]);
                    $nuevo_id_dir = $stmtDir->fetchColumn();
                    $pdo->prepare("UPDATE nna SET dir_actual = :id_dir WHERE id_nna = :id_nna")
                        ->execute([':id_dir'=>$nuevo_id_dir, ':id_nna'=>$id_nna_actual]);
                }
            }

            $pdo->prepare("DELETE FROM nna_nacionalidad WHERE id_nna = :id_nna")->execute([':id_nna'=>$id_nna_actual]);
            if ($id_pais) {
                $pdo->prepare("INSERT INTO nna_nacionalidad (id_nna, id_pais) VALUES (:id_nna, :id_pais)")
                    ->execute([':id_nna'=>$id_nna_actual, ':id_pais'=>$id_pais]);
            }

            $pdo->commit();
            $mensaje = "Información del NNA actualizada correctamente ✅"; $tipoMensaje = "success";

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = $e->getMessage();
            if     (strpos($err, 'AURORA-001') !== false) $mensaje = "El usuario registrador no existe o no está activo ⚠️";
            elseif (strpos($err, 'AURORA-002') !== false) $mensaje = "Solo un Trabajador Social puede modificar el registrador ⚠️";
            elseif (strpos($err, 'nna_dir_actual_key') !== false) $mensaje = "Esa dirección ya está asignada a otro NNA ⚠️";
            else $mensaje = "Error al actualizar ❌ " . $err;
            $tipoMensaje = "error";
        }
    }
}

// Cargar datos actuales (v8: +apodo)
try {
    $stmt = $pdo->prepare("
        SELECT n.id_nna, n.curp, n.nombre, n.apodo,
            n.prim_ap AS apellido_paterno, n.seg_ap AS apellido_materno,
            n.fecha_nacimiento, n.id_sexo, n.id_escolaridad, n.id_grupo_sanguineo,
            n.id_motivo_ingreso, n.luga_nac_nna, n.id_equipo,
            n.situacion_calle, n.es_migrante, n.es_refugiado, n.poblacion_indigena,
            d.id_dir, d.calle_dir AS calle, d.no_ext_dir AS num_ext, d.no_int_dir AS num_int,
            d.colonia_abierta AS colonia, d.codigo_postal AS cp, d.id_municipio,
            (SELECT nn.id_pais FROM nna_nacionalidad nn WHERE nn.id_nna = n.id_nna LIMIT 1) AS id_pais
        FROM nna n LEFT JOIN direccion d ON d.id_dir = n.dir_actual
        WHERE n.curp = :curp LIMIT 1
    ");
    $stmt->execute([':curp' => $curp_original]);
    $nna = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $nna = null; }
if (!$nna) die("NNA no encontrado.");

// Contactos
$contactos = [];
try {
    $stmtC = $pdo->prepare("SELECT ca.id_contacto, ca.valor_contacto, ca.descripcion, tc.nombre AS tipo FROM nna_contacto_adicional ca JOIN cat_tipo_contacto tc ON tc.id = ca.id_tipo_contacto WHERE ca.id_nna = :id_nna ORDER BY tc.nombre");
    $stmtC->execute([':id_nna' => $id_nna_actual]);
    $contactos = $stmtC->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $contactos = []; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar NNA</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background:linear-gradient(135deg,#a18cd1,#fbc2eb); min-height:100vh; padding:30px 20px; }
        .card { background:#fff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,.1); padding:28px 32px; max-width:750px; margin:0 auto 22px; }
        h1 { color:#2c3e50; margin-bottom:5px; }
        h2 { color:#2980b9; border-bottom:2px solid #eee; padding-bottom:8px; margin:0 0 14px; font-size:16px; }
        label { display:block; margin-top:13px; font-weight:700; color:#2c3e50; font-size:14px; }
        input[type="text"], input[type="date"], select { width:100%; padding:9px 12px; margin-top:4px; border-radius:6px; border:1px solid #ccc; box-sizing:border-box; font-size:14px; }
        input[type="text"] { text-transform:uppercase; }
        .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .btn-update { background:#27ae60; color:#fff; padding:13px; border:none; width:100%; cursor:pointer; border-radius:6px; font-weight:700; margin-top:20px; font-size:15px; }
        .btn-delete { background:#e74c3c; color:#fff; padding:13px; border:none; width:100%; cursor:pointer; border-radius:6px; font-weight:700; margin-top:8px; font-size:15px; }
        .alerta { padding:11px; border-radius:6px; margin-bottom:14px; text-align:center; font-weight:600; }
        .alerta.success { background:#d4edda; color:#155724; }
        .alerta.error   { background:#f8d7da; color:#721c24; }
        .contacto-item { display:flex; justify-content:space-between; align-items:center; background:#f1f9ff; border:1px solid #d1e9ff; border-radius:6px; padding:10px; margin-top:8px; }
        .contacto-item .info { font-size:14px; color:#2c3e50; }
        .contacto-item .info small { color:#7f8c8d; }
        .btn-mini-del { background:#e74c3c; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer; font-size:12px; }
        .contacto-form { background:#fafafa; border:1px dashed #ccc; padding:15px; border-radius:6px; margin-top:12px; }
        a.back { text-decoration:none; color:#34495e; font-weight:700; font-size:14px; display:inline-block; margin-bottom:14px; }
        .seccion { margin-top:18px; border-top:1px solid #eee; padding-top:14px; }
    </style>
</head>
<body>
<div class="card">
    <a href="ver_nnas.php" class="back">⬅ Volver a la lista</a>
    <h1>Editar NNA</h1>

    <?php if ($mensaje): ?>
        <div class="alerta <?= $tipoMensaje ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST">
        <h2> Datos de Identidad</h2>

        <label>CURP (no editable):</label>
        <input type="text" value="<?= htmlspecialchars($nna['curp'] ?? 'Sin CURP') ?>" disabled style="background:#eee;">

        <div class="grid2">
            <div><label>Nombre(s):</label><input type="text" name="nombres" value="<?= htmlspecialchars($nna['nombre']) ?>" required></div>
            <div><label>Apellido Paterno:</label><input type="text" name="apellido_p" value="<?= htmlspecialchars($nna['apellido_paterno']) ?>" required></div>
        </div>

        <div class="grid2">
            <div><label>Apellido Materno:</label><input type="text" name="apellido_m" value="<?= htmlspecialchars($nna['apellido_materno'] ?? '') ?>"></div>
            <div>
                <!-- v8: apodo -->
                <label> Apodo / ¿Cómo le gusta que le llamen?</label>
                <input type="text" name="apodo" value="<?= htmlspecialchars($nna['apodo'] ?? '') ?>" style="text-transform:none;" placeholder="Ej. Paco, Lupita...">
            </div>
        </div>

        <div class="grid2">
            <div><label>Fecha de Nacimiento:</label><input type="date" name="nacimiento" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($nna['fecha_nacimiento']) ?>" required></div>
            <div>
                <label>Sexo:</label>
                <select name="id_sexo" required>
                    <option value="">SELECCIONE</option>
                    <?php foreach ($sexos as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($nna['id_sexo']==$s['id'])?'selected':'' ?>><?= htmlspecialchars(mb_strtoupper($s['nombre'],'UTF-8')) ?></option>
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
                        <option value="<?= $e['id'] ?>" <?= ($nna['id_escolaridad']==$e['id'])?'selected':'' ?>><?= htmlspecialchars(mb_strtoupper($e['nombre'],'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Grupo Sanguíneo:</label>
                <select name="id_grupo_sanguineo">
                    <option value="">DESCONOCIDO</option>
                    <?php foreach ($grupos_sanguineos as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($nna['id_grupo_sanguineo']==$g['id'])?'selected':'' ?>><?= htmlspecialchars($g['nombre']) ?></option>
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
                        <option value="<?= $m['id'] ?>" <?= ($nna['id_motivo_ingreso']==$m['id'])?'selected':'' ?>><?= htmlspecialchars(mb_strtoupper($m['nombre'],'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Lugar de Origen (Entidad):</label>
                <select name="luga_nac_nna">
                    <option value="">NO ESPECIFICADO</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?= $e['id_ent'] ?>" <?= ($nna['luga_nac_nna']==$e['id_ent'])?'selected':'' ?>><?= htmlspecialchars(mb_strtoupper($e['nom_ent'],'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid2">
            <div>
                <label>Nacionalidad:</label>
                <select name="id_pais">
                    <option value="">NO ESPECIFICADA</option>
                    <?php foreach ($paises as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($nna['id_pais']==$p['id'])?'selected':'' ?>><?= htmlspecialchars(mb_strtoupper($p['nombre'],'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label> Equipo Asignado:</label>
                <select name="id_equipo">
                    <option value="">SIN EQUIPO</option>
                    <?php foreach ($equipos as $eq): ?>
                        <option value="<?= $eq['id_equipo'] ?>" <?= ($nna['id_equipo']==$eq['id_equipo'])?'selected':'' ?>><?= htmlspecialchars(mb_strtoupper($eq['nombre_equipo'],'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="seccion">
            <h2>⚠️ Vulnerabilidad</h2>
            <div class="grid2">
                <div><label>¿Situación de Calle?</label><select name="situacion_calle"><option value="Si" <?= $nna['situacion_calle']==='t'?'selected':'' ?>>Sí</option><option value="No" <?= $nna['situacion_calle']==='f'?'selected':'' ?>>No</option></select></div>
                <div><label>¿Es Migrante?</label><select name="es_migrante"><option value="Si" <?= $nna['es_migrante']==='t'?'selected':'' ?>>Sí</option><option value="No" <?= $nna['es_migrante']==='f'?'selected':'' ?>>No</option></select></div>
                <div><label>¿Es Refugiado?</label><select name="es_refugiado"><option value="Si" <?= $nna['es_refugiado']==='t'?'selected':'' ?>>Sí</option><option value="No" <?= $nna['es_refugiado']==='f'?'selected':'' ?>>No</option></select></div>
                <div><label>¿Población Indígena?</label><select name="poblacion_indigena"><option value="Si" <?= $nna['poblacion_indigena']==='t'?'selected':'' ?>>Sí</option><option value="No" <?= $nna['poblacion_indigena']==='f'?'selected':'' ?>>No</option></select></div>
            </div>
        </div>

        <div class="seccion">
            <h2>🏠 Dirección Actual</h2>
            <label>Calle:</label><input type="text" name="calle" value="<?= htmlspecialchars($nna['calle'] ?? '') ?>">
            <div class="grid2">
                <div><label>Núm. Exterior:</label><input type="text" name="num_ext" value="<?= htmlspecialchars($nna['num_ext'] ?? '') ?>"></div>
                <div><label>Núm. Interior:</label><input type="text" name="num_int" value="<?= htmlspecialchars($nna['num_int'] ?? '') ?>"></div>
            </div>
            <div class="grid2">
                <div><label>Colonia:</label><input type="text" name="colonia" value="<?= htmlspecialchars($nna['colonia'] ?? '') ?>"></div>
                <div><label>Código Postal:</label><input type="text" name="cp" maxlength="5" pattern="\d{5}" value="<?= htmlspecialchars($nna['cp'] ?? '') ?>"></div>
            </div>
            <label>Municipio/Alcaldía:</label>
            <select name="id_municipio">
                <option value="">NO ESPECIFICADO</option>
                <?php foreach ($municipios as $m): ?>
                    <option value="<?= $m['id_municipio'] ?>" <?= ($nna['id_municipio']==$m['id_municipio'])?'selected':'' ?>><?= htmlspecialchars(mb_strtoupper($m['nom_mun'].' — '.$m['nom_ent'],'UTF-8')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" name="actualizar_nna" class="btn-update"> Guardar Cambios</button>
        <button type="submit" name="eliminar_nna" class="btn-delete" onclick="return confirm('¿Seguro que deseas eliminar este registro?');">🗑️ Eliminar NNA</button>
    </form>
</div>

<!-- CONTACTOS -->
<div class="card">
    <h2>📇 Contactos Adicionales</h2>
    <?php if (count($contactos) > 0): ?>
        <?php foreach ($contactos as $c): ?>
            <div class="contacto-item">
                <div class="info">
                    <strong><?= htmlspecialchars($c['tipo']) ?>:</strong> <?= htmlspecialchars($c['valor_contacto']) ?>
                    <?php if (!empty($c['descripcion'])): ?><br><small><?= htmlspecialchars($c['descripcion']) ?></small><?php endif; ?>
                </div>
                <form method="POST" style="margin:0;" onsubmit="return confirm('¿Borrar este contacto?');">
                    <input type="hidden" name="id_contacto" value="<?= (int)$c['id_contacto'] ?>">
                    <button type="submit" name="borrar_contacto" class="btn-mini-del">🗑️</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#7f8c8d;font-style:italic;">Sin contactos adicionales registrados.</p>
    <?php endif; ?>

    <div class="contacto-form">
        <form method="POST">
            <label>Tipo de Contacto:</label>
            <select name="id_tipo_contacto" required style="text-transform:none;">
                <option value="" disabled selected>-- Seleccione --</option>
                <?php foreach ($tipos_contacto as $tc): ?>
                    <option value="<?= $tc['id'] ?>"><?= htmlspecialchars($tc['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Valor:</label><input type="text" name="valor_contacto" required style="text-transform:none;" placeholder="Ej. 55-1234-5678">
            <label>Descripción (opcional):</label><input type="text" name="descripcion_contacto" style="text-transform:none;" placeholder="Ej. Teléfono de la abuela">
            <button type="submit" name="agregar_contacto" class="btn-update" style="margin-top:14px;">➕ Agregar Contacto</button>
        </form>
    </div>
</div>
</body>
</html>