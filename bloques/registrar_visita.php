<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/database.php';

$usuario = $_SESSION['usuario'];
$mensaje     = "";
$tipoMensaje = "";

$curp_nna = $_GET['curp'] ?? $_POST['curp_nna'] ?? '';

// Resolver NNA
$nna = null;
if (!empty($curp_nna)) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.id_nna, n.folio_nna, n.nombre, n.prim_ap, n.curp,
                   eq.nombre_equipo
            FROM nna n
            LEFT JOIN equipo eq ON eq.id_equipo = n.id_equipo
            WHERE n.curp = :curp LIMIT 1
        ");
        $stmt->execute([':curp' => $curp_nna]);
        $nna = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $nna = null; }
}

if (!$nna) die("NNA no encontrado. <a href='ver_nnas.php'>Volver</a>");

// Catálogo de tipos de visita
$tipos_visita = [];
try {
    $tipos_visita = $pdo->query("SELECT id, nombre FROM cat_tipo_visita ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $tipos_visita = []; }

// PRG
if (isset($_GET['s']) && $_GET['s'] === 'ok') {
    $mensaje = "Visita registrada correctamente ✅";
    $tipoMensaje = "success";
}

// ============================================================
//  REGISTRAR VISITA
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['guardar_visita'])) {
    $id_tipo_visita   = !empty($_POST['id_tipo_visita'])   ? (int)$_POST['id_tipo_visita'] : null;
    $lugar            = trim($_POST['lugar_visita'] ?? '');
    $fecha_programada = !empty($_POST['fecha_programada']) ? $_POST['fecha_programada']     : null;
    $fecha_realizada  = !empty($_POST['fecha_realizada'])  ? $_POST['fecha_realizada']      : null;
    $estado_visita    = $_POST['estado_visita'] ?? 'PROGRAMADA';
    $objetivo         = trim($_POST['objetivo'] ?? '');
    $resultado        = trim($_POST['resultado'] ?? '');

    if (!$id_tipo_visita) {
        $mensaje = "El tipo de visita es obligatorio ⚠️";
        $tipoMensaje = "error";
    } else {
        try {
            $pdo->prepare("
                INSERT INTO visita_seguimiento (
                    id_nna, id_usuario, id_rol_ejecutor, id_tipo_visita,
                    lugar_visita, fecha_programada, fecha_realizada,
                    estado_visita, objetivo, resultado
                ) VALUES (
                    :id_nna, :id_usuario, :id_rol, :id_tipo,
                    :lugar, :f_prog, :f_real,
                    :estado, :objetivo, :resultado
                )
            ")->execute([
                ':id_nna'     => $nna['id_nna'],
                ':id_usuario' => $usuario['id_usuario'],
                ':id_rol'     => $usuario['id_rol'],   // rol al momento de la visita
                ':id_tipo'    => $id_tipo_visita,
                ':lugar'      => $lugar !== '' ? $lugar : null,
                ':f_prog'     => $fecha_programada,
                ':f_real'     => $fecha_realizada,
                ':estado'     => $estado_visita,
                ':objetivo'   => $objetivo !== '' ? $objetivo : null,
                ':resultado'  => $resultado !== '' ? $resultado : null,
            ]);

            header("Location: " . $_SERVER['PHP_SELF'] . "?curp=" . urlencode($curp_nna) . "&s=ok");
            exit();
        } catch (PDOException $e) {
            $mensaje = "Error al registrar la visita ❌";
            $tipoMensaje = "error";
        }
    }
}

// ============================================================
//  ACTUALIZAR ESTADO DE VISITA EXISTENTE
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cambiar_estado_visita'])) {
    $id_visita    = $_POST['id_visita'] ?? '';
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';

    if (!empty($id_visita) && in_array($nuevo_estado, ['PROGRAMADA','REALIZADA','CANCELADA'])) {
        try {
            $params = [':estado' => $nuevo_estado, ':id' => $id_visita];
            $sql = "UPDATE visita_seguimiento SET estado_visita = :estado";

            // Si se marca como realizada, poner fecha_realizada = ahora
            if ($nuevo_estado === 'REALIZADA') {
                $sql .= ", fecha_realizada = NOW()";
            }

            $sql .= " WHERE id_visita = :id";
            $pdo->prepare($sql)->execute($params);

            header("Location: " . $_SERVER['PHP_SELF'] . "?curp=" . urlencode($curp_nna) . "&s=ok");
            exit();
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar estado ❌";
            $tipoMensaje = "error";
        }
    }
}

// ============================================================
//  CARGAR HISTORIAL DE VISITAS
// ============================================================
$visitas = [];
try {
    $stmtV = $pdo->prepare("
        SELECT
            vs.id_visita,
            vs.fecha_programada,
            vs.fecha_realizada,
            vs.estado_visita,
            vs.lugar_visita,
            vs.objetivo,
            vs.resultado,
            tv.nombre         AS tipo_visita,
            u.nombre || ' ' || u.apellido_paterno AS profesionista,
            r.nombre          AS rol
        FROM visita_seguimiento vs
        JOIN cat_tipo_visita tv  ON tv.id = vs.id_tipo_visita
        JOIN usuario_sistema u   ON u.id_usuario = vs.id_usuario
        JOIN cat_rol_sistema r   ON r.id = vs.id_rol_ejecutor
        WHERE vs.id_nna = :id_nna
        ORDER BY vs.fecha_programada DESC NULLS LAST, vs.estado_visita
    ");
    $stmtV->execute([':id_nna' => $nna['id_nna']]);
    $visitas = $stmtV->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $visitas = []; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Visitas — <?= htmlspecialchars($nna['nombre']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background:linear-gradient(135deg,#a18cd1,#fbc2eb); min-height:100vh; padding:30px 20px; font-family:Arial,sans-serif; }
        .container { max-width:900px; margin:0 auto; }
        .card { background:#fff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,.1); padding:24px 28px; margin-bottom:20px; }
        h1 { color:#2c3e50; font-size:20px; margin-bottom:4px; }
        h2 { color:#2980b9; font-size:16px; margin:0 0 12px; }
        label { display:block; margin-top:10px; font-weight:700; color:#2c3e50; font-size:13px; }
        input, select, textarea { width:100%; padding:9px 10px; margin-top:4px; border-radius:6px; border:1px solid #ccc; box-sizing:border-box; font-size:13px; }
        textarea { resize:vertical; min-height:80px; }
        .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
        .btn-guardar { background:#2980b9; color:#fff; border:none; padding:12px 24px; border-radius:6px; cursor:pointer; font-weight:700; font-size:15px; margin-top:14px; }
        .btn-guardar:hover { background:#2471a3; }
        .alerta { padding:11px; border-radius:6px; margin-bottom:14px; text-align:center; font-weight:600; }
        .alerta.success { background:#d4edda; color:#155724; }
        .alerta.error   { background:#f8d7da; color:#721c24; }
        .info-bar { background:#e3f2fd; padding:10px 14px; border-left:4px solid #2196f3; border-radius:4px; font-size:13px; margin-bottom:16px; }

        .visita-card { border:1px solid #e0e0e0; border-radius:8px; padding:14px; margin-bottom:12px; position:relative; }
        .visita-card .meta { font-size:11px; color:#7f8c8d; margin-bottom:6px; }
        .visita-card .meta strong { color:#2c3e50; }
        .tag-prog   { background:#f39c12; color:#fff; font-size:10px; padding:2px 8px; border-radius:3px; }
        .tag-real   { background:#27ae60; color:#fff; font-size:10px; padding:2px 8px; border-radius:3px; }
        .tag-cancel { background:#e74c3c; color:#fff; font-size:10px; padding:2px 8px; border-radius:3px; }
        .tag-rol    { background:#8e44ad; color:#fff; font-size:10px; padding:2px 6px; border-radius:3px; margin-left:4px; }
        .tag-tipo   { background:#34495e; color:#fff; font-size:10px; padding:2px 6px; border-radius:3px; }

        .acciones-visita { position:absolute; top:12px; right:12px; display:flex; gap:4px; }
        .btn-mini { border:none; padding:4px 8px; border-radius:3px; cursor:pointer; font-size:11px; font-weight:700; color:#fff; }
        .btn-mini-ok  { background:#27ae60; }
        .btn-mini-can { background:#e74c3c; }

        a.back { text-decoration:none; color:#7f8c8d; font-size:14px; }
        .sin-dato { color:#95a5a6; font-style:italic; font-size:13px; }
    </style>
</head>
<body>
<div class="container">

    <div class="card">
        <a href="ver_perfil_nna.php?curp=<?= urlencode($curp_nna) ?>" class="back">⬅ Volver al perfil</a>
        <h1>📅 Visitas y Diligencias</h1>

        <div class="info-bar">
            <strong><?= htmlspecialchars($nna['nombre'].' '.$nna['prim_ap']) ?></strong>
            &nbsp;|&nbsp; Folio: <?= htmlspecialchars($nna['folio_nna']) ?>
            <?php if ($nna['nombre_equipo']): ?>
                &nbsp;|&nbsp; Equipo: <strong><?= htmlspecialchars($nna['nombre_equipo']) ?></strong>
            <?php endif; ?>
        </div>

        <?php if ($mensaje): ?>
            <div class="alerta <?= $tipoMensaje ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div style="background:#fff3cd; border:1px solid #ffc107; border-radius:6px; padding:10px; font-size:12px; color:#856404; margin-bottom:14px;">
            📌 Registrando como: <strong><?= htmlspecialchars($usuario['nombre'].' '.$usuario['apellido_paterno']) ?></strong>
            — Rol: <strong><?= htmlspecialchars($usuario['rol']) ?></strong>
        </div>

        <h2>➕ Nueva Visita</h2>
        <form method="POST">
            <input type="hidden" name="curp_nna" value="<?= htmlspecialchars($curp_nna) ?>">

            <div class="grid2">
                <div>
                    <label>Tipo de Visita: *</label>
                    <select name="id_tipo_visita" required>
                        <option value="">SELECCIONE</option>
                        <?php foreach ($tipos_visita as $tv): ?>
                            <option value="<?= $tv['id'] ?>"><?= htmlspecialchars(mb_strtoupper($tv['nombre'],'UTF-8')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Estado:</label>
                    <select name="estado_visita">
                        <option value="PROGRAMADA">Programada</option>
                        <option value="REALIZADA">Realizada</option>
                    </select>
                </div>
            </div>

            <div class="grid2">
                <div>
                    <label>Fecha Programada:</label>
                    <input type="datetime-local" name="fecha_programada">
                </div>
                <div>
                    <label>Fecha Realizada:</label>
                    <input type="datetime-local" name="fecha_realizada">
                </div>
            </div>

            <label>Lugar de la Visita:</label>
            <input type="text" name="lugar_visita" placeholder="Ej. Domicilio del NNA, Juzgado 3er Distrito, Hospital General...">

            <div class="grid2">
                <div>
                    <label>Objetivo:</label>
                    <textarea name="objetivo" placeholder="¿Cuál es el propósito de la visita?"></textarea>
                </div>
                <div>
                    <label>Resultado:</label>
                    <textarea name="resultado" placeholder="Hallazgos, observaciones, acuerdos..."></textarea>
                </div>
            </div>

            <button type="submit" name="guardar_visita" class="btn-guardar"> Registrar Visita</button>
        </form>
    </div>

    <!-- HISTORIAL -->
    <div class="card">
        <h2> Historial de Visitas</h2>

        <?php if (count($visitas) > 0): ?>
            <?php foreach ($visitas as $v): ?>
                <?php
                    $estado_cls = 'tag-prog';
                    if ($v['estado_visita'] === 'REALIZADA')  $estado_cls = 'tag-real';
                    if ($v['estado_visita'] === 'CANCELADA')  $estado_cls = 'tag-cancel';
                ?>
                <div class="visita-card">
                    <!-- Botones rápidos para cambiar estado -->
                    <?php if ($v['estado_visita'] === 'PROGRAMADA'): ?>
                        <div class="acciones-visita">
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="curp_nna" value="<?= htmlspecialchars($curp_nna) ?>">
                                <input type="hidden" name="id_visita" value="<?= $v['id_visita'] ?>">
                                <input type="hidden" name="nuevo_estado" value="REALIZADA">
                                <button type="submit" name="cambiar_estado_visita" class="btn-mini btn-mini-ok" title="Marcar como realizada">✔️</button>
                            </form>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="curp_nna" value="<?= htmlspecialchars($curp_nna) ?>">
                                <input type="hidden" name="id_visita" value="<?= $v['id_visita'] ?>">
                                <input type="hidden" name="nuevo_estado" value="CANCELADA">
                                <button type="submit" name="cambiar_estado_visita" class="btn-mini btn-mini-can" title="Cancelar visita"
                                        onclick="return confirm('¿Cancelar esta visita?');">✕</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="meta">
                        <span class="tag-tipo"><?= htmlspecialchars($v['tipo_visita']) ?></span>
                        <span class="<?= $estado_cls ?>"><?= $v['estado_visita'] ?></span>
                        <span class="tag-rol"><?= htmlspecialchars(str_replace('_',' ',$v['rol'])) ?></span>
                        &nbsp;—&nbsp; <strong><?= htmlspecialchars($v['profesionista']) ?></strong>
                    </div>

                    <div style="font-size:12px; color:#555; margin:6px 0;">
                        <?php if ($v['fecha_programada']): ?>
                             Programada: <?= htmlspecialchars($v['fecha_programada']) ?>
                        <?php endif; ?>
                        <?php if ($v['fecha_realizada']): ?>
                            &nbsp;|&nbsp; Realizada: <?= htmlspecialchars($v['fecha_realizada']) ?>
                        <?php endif; ?>
                        <?php if ($v['lugar_visita']): ?>
                            &nbsp;|&nbsp; <?= htmlspecialchars($v['lugar_visita']) ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($v['objetivo']): ?>
                        <p style="font-size:13px; margin:4px 0;"><strong>Objetivo:</strong> <?= htmlspecialchars($v['objetivo']) ?></p>
                    <?php endif; ?>
                    <?php if ($v['resultado']): ?>
                        <p style="font-size:13px; margin:4px 0;"><strong>Resultado:</strong> <?= htmlspecialchars($v['resultado']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="sin-dato">Sin visitas registradas para este NNA.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>