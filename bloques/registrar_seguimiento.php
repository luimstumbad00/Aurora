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

// El NNA llega por GET o POST
$curp_nna = $_GET['curp'] ?? $_POST['curp_nna'] ?? '';

// Resolver id_nna y datos básicos del NNA
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

if (!$nna) {
    die("NNA no encontrado. <a href='ver_nnas.php'>Volver</a>");
}

// PRG
if (isset($_GET['s']) && $_GET['s'] === 'ok') {
    $mensaje = "Nota de seguimiento registrada correctamente ✅";
    $tipoMensaje = "success";
}

// ============================================================
//  REGISTRAR NOTA
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['guardar_nota'])) {
    $notas   = trim($_POST['notas_evolucion'] ?? '');

    if (empty($notas)) {
        $mensaje = "Las notas de evolución son obligatorias ⚠️";
        $tipoMensaje = "error";
    } else {
        try {
            // El área de atención se fuerza al rol del usuario logueado
            $pdo->prepare("
                INSERT INTO expediente_seguimiento (id_nna, id_usuario, id_area_atencion, notas_evolucion)
                VALUES (:id_nna, :id_usuario, :id_area, :notas)
            ")->execute([
                ':id_nna'     => $nna['id_nna'],
                ':id_usuario' => $usuario['id_usuario'],
                ':id_area'    => $usuario['id_rol'],  // forzado al rol del profesionista
                ':notas'      => $notas
            ]);

            header("Location: " . $_SERVER['PHP_SELF'] . "?curp=" . urlencode($curp_nna) . "&s=ok");
            exit();
        } catch (PDOException $e) {
            $mensaje = "Error al registrar la nota ❌";
            $tipoMensaje = "error";
        }
    }
}

// ============================================================
//  CARGAR HISTORIAL DE NOTAS (todo el equipo puede ver)
// ============================================================
$notas = [];
try {
    $stmtN = $pdo->prepare("
        SELECT
            es.fecha_atencion,
            es.notas_evolucion,
            u.nombre || ' ' || u.apellido_paterno AS profesionista,
            r.nombre AS rol
        FROM expediente_seguimiento es
        JOIN usuario_sistema u    ON u.id_usuario = es.id_usuario
        JOIN cat_rol_sistema r    ON r.id = es.id_area_atencion
        WHERE es.id_nna = :id_nna
        ORDER BY es.fecha_atencion DESC
    ");
    $stmtN->execute([':id_nna' => $nna['id_nna']]);
    $notas = $stmtN->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $notas = []; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seguimiento — <?= htmlspecialchars($nna['nombre']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background:linear-gradient(135deg,#a18cd1,#fbc2eb); min-height:100vh; padding:30px 20px; font-family:Arial,sans-serif; }
        .container { max-width:850px; margin:0 auto; }
        .card { background:#fff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,.1); padding:24px 28px; margin-bottom:20px; }
        h1 { color:#2c3e50; font-size:20px; margin-bottom:4px; }
        h2 { color:#2980b9; font-size:16px; margin:0 0 12px; }
        label { display:block; margin-top:10px; font-weight:700; color:#2c3e50; font-size:13px; }
        textarea { width:100%; padding:10px; margin-top:4px; border-radius:6px; border:1px solid #ccc; box-sizing:border-box; font-size:14px; resize:vertical; min-height:120px; }
        .btn-guardar { background:#27ae60; color:#fff; border:none; padding:12px 24px; border-radius:6px; cursor:pointer; font-weight:700; font-size:15px; margin-top:14px; }
        .btn-guardar:hover { background:#219150; }
        .alerta { padding:11px; border-radius:6px; margin-bottom:14px; text-align:center; font-weight:600; }
        .alerta.success { background:#d4edda; color:#155724; }
        .alerta.error   { background:#f8d7da; color:#721c24; }
        .info-bar { background:#e3f2fd; padding:10px 14px; border-left:4px solid #2196f3; border-radius:4px; font-size:13px; margin-bottom:16px; }
        .nota-card { border-left:4px solid #34495e; background:#f9f9f9; padding:14px; border-radius:6px; margin-bottom:12px; }
        .nota-card .meta { font-size:11px; color:#7f8c8d; margin-bottom:6px; }
        .nota-card .meta strong { color:#2c3e50; }
        .nota-card .texto { font-size:14px; color:#2c3e50; line-height:1.5; white-space:pre-wrap; }
        .tag-rol { background:#8e44ad; color:#fff; font-size:10px; padding:2px 6px; border-radius:3px; margin-left:5px; }
        a.back { text-decoration:none; color:#7f8c8d; font-size:14px; }
        .sin-dato { color:#95a5a6; font-style:italic; font-size:13px; }
    </style>
</head>
<body>
<div class="container">

    <div class="card">
        <a href="ver_perfil_nna.php?curp=<?= urlencode($curp_nna) ?>" class="back">⬅ Volver al perfil</a>
        <h1>📝 Seguimiento del NNA</h1>

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
             Registrando como: <strong><?= htmlspecialchars($usuario['nombre'].' '.$usuario['apellido_paterno']) ?></strong>
            — Rol: <strong><?= htmlspecialchars($usuario['rol']) ?></strong>
            (la nota se asocia automáticamente a tu área de atención).
        </div>

        <form method="POST">
            <input type="hidden" name="curp_nna" value="<?= htmlspecialchars($curp_nna) ?>">

            <label>Notas de Evolución: *</label>
            <textarea name="notas_evolucion" required placeholder="Escriba aquí las observaciones, avances o incidencias del caso..."></textarea>

            <button type="submit" name="guardar_nota" class="btn-guardar"> Registrar Nota</button>
        </form>
    </div>

    <!-- HISTORIAL -->
    <div class="card">
        <h2> Historial de Notas</h2>

        <?php if (count($notas) > 0): ?>
            <?php foreach ($notas as $n): ?>
                <div class="nota-card">
                    <div class="meta">
                         <?= htmlspecialchars($n['fecha_atencion']) ?>
                        &nbsp;—&nbsp; <strong><?= htmlspecialchars($n['profesionista']) ?></strong>
                        <span class="tag-rol"><?= htmlspecialchars(str_replace('_',' ',$n['rol'])) ?></span>
                    </div>
                    <div class="texto"><?= htmlspecialchars($n['notas_evolucion']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="sin-dato">Sin notas de seguimiento registradas para este NNA.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>