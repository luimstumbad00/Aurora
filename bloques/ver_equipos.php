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
 
// PRG
if (isset($_GET['s'])) {
    if ($_GET['s'] === 'created') { $mensaje = "Equipo creado correctamente ✅"; $tipoMensaje = "success"; }
    if ($_GET['s'] === 'updated') { $mensaje = "Equipo actualizado ✅";         $tipoMensaje = "success"; }
    if ($_GET['s'] === 'toggled') { $mensaje = "Estado del equipo cambiado ✅"; $tipoMensaje = "success"; }
}
 
// ============================================================
//  CREAR EQUIPO
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['crear_equipo'])) {
    $nombre = strtoupper(trim($_POST['nombre_equipo'] ?? ''));
    $desc   = trim($_POST['descripcion'] ?? '');
 
    if (empty($nombre)) {
        $mensaje = "El nombre del equipo es obligatorio ⚠️";
        $tipoMensaje = "error";
    } else {
        try {
            $pdo->prepare("INSERT INTO equipo (nombre_equipo, descripcion) VALUES (:nombre, :desc)")
                ->execute([':nombre' => $nombre, ':desc' => $desc !== '' ? $desc : null]);
            header("Location: " . $_SERVER['PHP_SELF'] . "?s=created");
            exit();
        } catch (PDOException $e) {
            $mensaje = strpos($e->getMessage(), 'equipo_nombre_equipo_key') !== false
                     ? "Ya existe un equipo con ese nombre ⚠️"
                     : "Error al crear equipo ❌";
            $tipoMensaje = "error";
        }
    }
}
 
// ============================================================
//  CAMBIAR ESTADO (ACTIVO ↔ INACTIVO)
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['toggle_estado'])) {
    $id_eq    = (int)($_POST['id_equipo'] ?? 0);
    $estado_a = $_POST['estado_actual'] ?? 'ACTIVO';
    $nuevo    = $estado_a === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
 
    try {
        $pdo->prepare("UPDATE equipo SET estado = :estado WHERE id_equipo = :id")
            ->execute([':estado' => $nuevo, ':id' => $id_eq]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?s=toggled");
        exit();
    } catch (PDOException $e) {
        $mensaje = "Error al cambiar estado ❌";
        $tipoMensaje = "error";
    }
}
 
// ============================================================
//  CARGAR EQUIPOS CON ESTADÍSTICAS
// ============================================================
$equipos = [];
try {
    $equipos = $pdo->query("
        SELECT
            eq.id_equipo,
            eq.nombre_equipo,
            eq.descripcion,
            eq.estado,
            eq.fecha_creacion,
            (SELECT COUNT(*) FROM usuario_sistema u WHERE u.id_equipo = eq.id_equipo AND u.estado = 'ACTIVO') AS num_profesionistas,
            (SELECT COUNT(*) FROM nna n WHERE n.id_equipo = eq.id_equipo) AS num_casos
        FROM equipo eq
        ORDER BY eq.estado DESC, eq.nombre_equipo
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $equipos = [];
}
 
// Para cada equipo, traer sus miembros y NNAs
$detalle = [];
foreach ($equipos as $eq) {
    $id = $eq['id_equipo'];
 
    // Miembros
    $miembros = [];
    try {
        $stmtM = $pdo->prepare("
            SELECT u.nombre, u.apellido_paterno, r.nombre AS rol, u.correo, u.estado
            FROM   usuario_sistema u
            JOIN   cat_rol_sistema r ON r.id = u.id_rol
            WHERE  u.id_equipo = :id
            ORDER BY r.nombre, u.apellido_paterno
        ");
        $stmtM->execute([':id' => $id]);
        $miembros = $stmtM->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $miembros = []; }
 
    // NNAs asignados
    $casos = [];
    try {
        $stmtC = $pdo->prepare("
            SELECT n.folio_nna, n.nombre, n.prim_ap, n.curp, n.fecha_registro
            FROM   nna n
            WHERE  n.id_equipo = :id
            ORDER BY n.fecha_registro DESC
        ");
        $stmtC->execute([':id' => $id]);
        $casos = $stmtC->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $casos = []; }
 
    $detalle[$id] = ['miembros' => $miembros, 'casos' => $casos];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Equipos - Aurora</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background:linear-gradient(135deg,#a18cd1,#fbc2eb); min-height:100vh; padding:30px 20px; font-family:Arial,sans-serif; }
        .container { max-width:1100px; margin:0 auto; }
        .card { background:#fff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,.1); padding:24px 28px; margin-bottom:20px; }
        h1 { color:#2c3e50; margin-bottom:5px; }
        h2 { color:#2c3e50; font-size:18px; margin:0 0 10px; }
        h3 { color:#34495e; font-size:14px; margin:14px 0 8px; text-transform:uppercase; }
 
        .alerta { padding:11px; border-radius:6px; margin-bottom:14px; text-align:center; font-weight:600; }
        .alerta.success { background:#d4edda; color:#155724; }
        .alerta.error   { background:#f8d7da; color:#721c24; }
 
        .stats { display:flex; gap:10px; margin:10px 0 14px; flex-wrap:wrap; }
        .stat-box { background:#f0f4ff; border:1px solid #d0d8f0; border-radius:8px; padding:10px 16px; text-align:center; min-width:120px; }
        .stat-box .num { font-size:28px; font-weight:700; color:#2c3e50; }
        .stat-box .lbl { font-size:11px; color:#7f8c8d; text-transform:uppercase; }
 
        .tag-activo   { background:#27ae60; color:#fff; font-size:11px; padding:3px 10px; border-radius:4px; font-weight:700; }
        .tag-inactivo { background:#e74c3c; color:#fff; font-size:11px; padding:3px 10px; border-radius:4px; font-weight:700; }
 
        table.mini { width:100%; border-collapse:collapse; font-size:13px; margin-top:6px; }
        table.mini th { background:#34495e; color:#fff; padding:7px 10px; text-align:left; font-size:11px; }
        table.mini td { padding:7px 10px; border-bottom:1px solid #eee; }
        table.mini tr:hover td { background:#f5f5f5; }
 
        .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        label { display:block; margin-top:10px; font-weight:700; color:#2c3e50; font-size:13px; }
        input, textarea { width:100%; padding:8px 10px; margin-top:4px; border-radius:6px; border:1px solid #ccc; box-sizing:border-box; font-size:13px; }
        textarea { resize:vertical; min-height:60px; }
 
        .btn-crear  { background:#27ae60; color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:700; font-size:14px; margin-top:14px; }
        .btn-toggle { border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-weight:700; font-size:12px; color:#fff; }
        .btn-activar   { background:#27ae60; }
        .btn-desactivar { background:#e74c3c; }
 
        a.back { text-decoration:none; color:#7f8c8d; font-size:14px; display:inline-block; margin-bottom:14px; }
        .sin-dato { color:#95a5a6; font-style:italic; font-size:13px; }
 
        .equipo-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
    </style>
</head>
<body>
<div class="container">
 
    <!-- ENCABEZADO -->
    <div class="card">
        <a href="dashboard.php" class="back">⬅ Dashboard</a>
        <h1>Dashboard de Equipos Multidisciplinarios</h1>
        <p style="color:#7f8c8d; font-size:13px;">Gestión de equipos, asignación de profesionistas y seguimiento de casos.</p>
 
        <?php if ($mensaje): ?>
            <div class="alerta <?= $tipoMensaje ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
    </div>
 
    <!-- CREAR EQUIPO -->
    <div class="card">
        <h2>➕ Crear Nuevo Equipo</h2>
        <form method="POST">
            <div class="grid2">
                <div>
                    <label>Nombre del Equipo: *</label>
                    <input type="text" name="nombre_equipo" required placeholder="EJ. EQUIPO DELTA" style="text-transform:uppercase;">
                </div>
                <div>
                    <label>Descripción (opcional):</label>
                    <textarea name="descripcion" placeholder="Breve descripción del enfoque del equipo..."></textarea>
                </div>
            </div>
            <button type="submit" name="crear_equipo" class="btn-crear"> Crear Equipo</button>
        </form>
    </div>
 
    <!-- LISTADO DE EQUIPOS -->
    <?php if (count($equipos) > 0): ?>
        <?php foreach ($equipos as $eq): ?>
            <?php $id = $eq['id_equipo']; ?>
            <div class="card" style="border-left:5px solid <?= $eq['estado']==='ACTIVO' ? '#27ae60' : '#e74c3c' ?>;">
 
                <div class="equipo-header">
                    <div>
                        <h2 style="margin:0;">
                            <?= htmlspecialchars($eq['nombre_equipo']) ?>
                            <span class="<?= $eq['estado']==='ACTIVO' ? 'tag-activo' : 'tag-inactivo' ?>">
                                <?= $eq['estado'] ?>
                            </span>
                        </h2>
                        <?php if ($eq['descripcion']): ?>
                            <p style="color:#7f8c8d; font-size:12px; margin-top:4px;"><?= htmlspecialchars($eq['descripcion']) ?></p>
                        <?php endif; ?>
                        <small style="color:#95a5a6; font-size:11px;">Creado: <?= htmlspecialchars($eq['fecha_creacion']) ?></small>
                    </div>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="id_equipo" value="<?= $id ?>">
                        <input type="hidden" name="estado_actual" value="<?= $eq['estado'] ?>">
                        <button type="submit" name="toggle_estado"
                                class="btn-toggle <?= $eq['estado']==='ACTIVO' ? 'btn-desactivar' : 'btn-activar' ?>"
                                onclick="return confirm('¿Cambiar estado del equipo?');">
                            <?= $eq['estado']==='ACTIVO' ? '⏸ Desactivar' : '▶ Activar' ?>
                        </button>
                    </form>
                </div>
 
                <!-- Estadísticas -->
                <div class="stats">
                    <div class="stat-box">
                        <div class="num"><?= (int)$eq['num_profesionistas'] ?></div>
                        <div class="lbl">Profesionistas</div>
                    </div>
                    <div class="stat-box">
                        <div class="num"><?= (int)$eq['num_casos'] ?></div>
                        <div class="lbl">Casos (NNA)</div>
                    </div>
                </div>
 
                <!-- Miembros del equipo -->
                <h3>👥 Miembros del Equipo</h3>
                <?php if (count($detalle[$id]['miembros']) > 0): ?>
                    <table class="mini">
                        <thead><tr><th>Profesionista</th><th>Rol</th><th>Correo</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach ($detalle[$id]['miembros'] as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['nombre'].' '.$m['apellido_paterno']) ?></td>
                                    <td><?= htmlspecialchars(str_replace('_',' ',$m['rol'])) ?></td>
                                    <td><?= htmlspecialchars($m['correo'] ?? '—') ?></td>
                                    <td>
                                        <span class="<?= $m['estado']==='ACTIVO' ? 'tag-activo' : 'tag-inactivo' ?>"
                                              style="font-size:10px;">
                                            <?= $m['estado'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="sin-dato">Sin profesionistas asignados. Asígnalos desde "Editar Usuario".</p>
                <?php endif; ?>
 
                <!-- Casos asignados -->
                <h3> Casos Asignados (NNA)</h3>
                <?php if (count($detalle[$id]['casos']) > 0): ?>
                    <table class="mini">
                        <thead><tr><th>Folio</th><th>NNA</th><th>CURP</th><th>Registro</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($detalle[$id]['casos'] as $c): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($c['folio_nna']) ?></strong></td>
                                    <td><?= htmlspecialchars($c['nombre'].' '.$c['prim_ap']) ?></td>
                                    <td><?= htmlspecialchars($c['curp'] ?? 'Sin CURP') ?></td>
                                    <td><?= htmlspecialchars($c['fecha_registro']) ?></td>
                                    <td>
                                        <?php if ($c['curp']): ?>
                                            <a href="ver_perfil_nna.php?curp=<?= urlencode($c['curp']) ?>"
                                               style="color:#3498db; font-size:12px; font-weight:700; text-decoration:none;">
                                                 Ver
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="sin-dato">Sin casos asignados. Asígnalos desde "Agregar NNA" o "Editar NNA".</p>
                <?php endif; ?>
 
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card">
            <p class="sin-dato">No hay equipos creados todavía.</p>
        </div>
    <?php endif; ?>
 
</div>
</body>
</html>
