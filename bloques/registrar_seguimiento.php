<?php
session_start();

// Cualquier profesional con sesión puede registrar seguimientos
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/database.php';

$id_usuario = $_SESSION['usuario']['id_usuario'] ?? null;

// El NNA llega por GET la 1ª vez, por POST después
$curp_nna = $_GET['curp'] ?? $_POST['curp_oculto'] ?? '';

$mensaje = "";
$tipoMensaje = "";

if (empty($curp_nna)) {
    die("Error: no se especificó el NNA.");
}

// Catálogo de áreas de atención (cat_rol_sistema)
$areas = [];
try {
    $areas = $pdo->query("SELECT id, nombre FROM cat_rol_sistema ORDER BY nombre")
                 ->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En producción: error_log($e->getMessage());
}

// Resolver el NNA (necesitamos id_nna y su nombre para el encabezado)
try {
    $stmt = $pdo->prepare("SELECT id_nna, nombre, prim_ap, seg_ap FROM nna WHERE curp = :curp LIMIT 1");
    $stmt->execute([':curp' => $curp_nna]);
    $nna = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $nna = null;
}

if (!$nna) {
    die("NNA no encontrado en el sistema Aurora.");
}

// PRG: mensaje de éxito tras redirigir
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $mensaje = "Seguimiento registrado correctamente ✅";
    $tipoMensaje = "success";
}

// Procesar el registro
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['registrar'])) {
    $id_area      = !empty($_POST['id_area_atencion']) ? (int) $_POST['id_area_atencion'] : null;
    $notas        = trim($_POST['notas_evolucion'] ?? '');
    $archivo_path = trim($_POST['archivo_adjunto_path'] ?? '');

    if (!$id_usuario) {
        $mensaje = "No se pudo identificar al usuario en sesión ⚠️";
        $tipoMensaje = "error";
    } elseif (!$id_area) {
        $mensaje = "Debes seleccionar el área de atención ⚠️";
        $tipoMensaje = "error";
    } else {
        try {
            $sql = "
                INSERT INTO expediente_seguimiento (
                    id_nna, id_usuario, id_area_atencion, notas_evolucion, archivo_adjunto_path
                ) VALUES (
                    :id_nna, :id_usuario, :id_area, :notas, :archivo
                )
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_nna'     => $nna['id_nna'],
                ':id_usuario' => $id_usuario,
                ':id_area'    => $id_area,
                ':notas'      => $notas !== '' ? $notas : null,
                ':archivo'    => $archivo_path !== '' ? $archivo_path : null
            ]);

            header("Location: " . $_SERVER['PHP_SELF'] . "?curp=" . urlencode($curp_nna) . "&status=success");
            exit();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'chk_archivo_path') !== false) {
                $mensaje = "La ruta del archivo contiene caracteres no permitidos ⚠️";
            } else {
                $mensaje = "Error al registrar el seguimiento ❌";
            }
            $tipoMensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Seguimiento - Aurora</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI', sans-serif; display:flex; justify-content:center; padding:20px; }
        .card { background:white; width:100%; max-width:650px; padding:30px; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.1); }
        h1 { color:#1a2a6c; text-align:center; }
        label { display:block; margin-top:15px; font-weight:bold; color:#34495e; }
        select, textarea, input[type="text"] { width:100%; padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; }
        .info-nna { background:#e3f2fd; padding:12px; border-left:5px solid #2196f3; margin-bottom:20px; border-radius:6px; }
        .btn { background:#27ae60; color:white; border:none; width:100%; padding:15px; margin-top:25px; border-radius:8px; font-size:16px; font-weight:bold; cursor:pointer; }
        .alert { padding:15px; border-radius:6px; margin-bottom:20px; text-align:center; font-weight:bold; }
        .success { background:#d4edda; color:#155724; }
        .error { background:#f8d7da; color:#721c24; }
    </style>
</head>
<body>
<div class="card">
    <a href="ver_seguimientos.php?curp=<?= urlencode($curp_nna) ?>" style="text-decoration:none; color:#7f8c8d; font-size:13px;">⬅ Ver historial</a>
    <h1>📝 Registrar Seguimiento</h1>

    <?php if ($mensaje): ?>
        <div class="alert <?= $tipoMensaje ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="info-nna">
        NNA: <strong><?= htmlspecialchars(trim($nna['nombre'] . " " . $nna['prim_ap'] . " " . ($nna['seg_ap'] ?? ''))) ?></strong>
        <br><small><?= htmlspecialchars($curp_nna) ?></small>
    </div>

    <form method="POST">
        <input type="hidden" name="curp_oculto" value="<?= htmlspecialchars($curp_nna) ?>">

        <label>Área de Atención:</label>
        <select name="id_area_atencion" required>
            <option value="" disabled selected>-- Seleccione el área --</option>
            <?php foreach ($areas as $a): ?>
                <option value="<?= (int) $a['id'] ?>"><?= htmlspecialchars(str_replace('_', ' ', $a['nombre'])) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Notas de Evolución:</label>
        <textarea name="notas_evolucion" rows="6" placeholder="Describa la intervención, observaciones y evolución del caso..."></textarea>

        <label>Ruta de Archivo Adjunto (opcional):</label>
        <input type="text" name="archivo_adjunto_path" placeholder="Ej. expedientes/nna123/valoracion.pdf">

        <button type="submit" name="registrar" class="btn">Guardar Seguimiento</button>
    </form>
</div>
</body>
</html>