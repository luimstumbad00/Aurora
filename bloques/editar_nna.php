<?php
session_start();

// 1. Validar sesión y rol (solo Administrador)
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

$mensaje = "";
$tipoMensaje = "";

// 2. Obtener CURP desde la URL
if (!isset($_GET['curp'])) {
    header("Location: ver_nnas.php");
    exit();
}
$curp_original = trim($_GET['curp']);

// Catálogos para poblar los <select>
$sexos = $paises = $tipos_contacto = [];
try {
    $sexos          = $pdo->query("SELECT id, nombre FROM cat_sexo ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $paises         = $pdo->query("SELECT id, nombre FROM cat_pais ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $tipos_contacto = $pdo->query("SELECT id, nombre FROM cat_tipo_contacto ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En producción: error_log($e->getMessage());
}

// Resolvemos el id_nna una sola vez (lo necesitan varias acciones)
try {
    $stmtId = $pdo->prepare("SELECT id_nna FROM nna WHERE curp = :curp LIMIT 1");
    $stmtId->execute([':curp' => $curp_original]);
    $id_nna_actual = $stmtId->fetchColumn();
} catch (PDOException $e) {
    $id_nna_actual = null;
}

if (!$id_nna_actual) { die("NNA no encontrado."); }

// --- AGREGAR CONTACTO ADICIONAL ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['agregar_contacto'])) {
    $id_tipo_contacto = !empty($_POST['id_tipo_contacto']) ? (int) $_POST['id_tipo_contacto'] : null;
    $valor_contacto   = trim($_POST['valor_contacto'] ?? '');
    $descripcion      = trim($_POST['descripcion_contacto'] ?? '');

    if (!$id_tipo_contacto || $valor_contacto === '') {
        $mensaje = "Debes elegir el tipo de contacto y escribir su valor ⚠️";
        $tipoMensaje = "error";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO nna_contacto_adicional (id_nna, id_tipo_contacto, valor_contacto, descripcion)
                VALUES (:id_nna, :id_tipo, :valor, :desc)
            ");
            $stmt->execute([
                ':id_nna' => $id_nna_actual,
                ':id_tipo' => $id_tipo_contacto,
                ':valor'  => $valor_contacto,
                ':desc'   => $descripcion !== '' ? $descripcion : null
            ]);
            header("Location: " . $_SERVER['PHP_SELF'] . "?curp=" . urlencode($curp_original) . "&c=ok");
            exit();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'uq_nna_contacto') !== false) {
                $mensaje = "Ese contacto ya está registrado para el NNA ⚠️";
            } else {
                $mensaje = "Error al agregar el contacto ❌";
            }
            $tipoMensaje = "error";
        }
    }
}

// --- BORRAR CONTACTO ADICIONAL ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['borrar_contacto'])) {
    $id_contacto = (int) ($_POST['id_contacto'] ?? 0);
    try {
        // Atamos el borrado al id_nna para que nadie borre contactos de otro NNA
        $stmt = $pdo->prepare("
            DELETE FROM nna_contacto_adicional
            WHERE id_contacto = :id_contacto AND id_nna = :id_nna
        ");
        $stmt->execute([':id_contacto' => $id_contacto, ':id_nna' => $id_nna_actual]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?curp=" . urlencode($curp_original) . "&c=del");
        exit();
    } catch (PDOException $e) {
        $mensaje = "Error al borrar el contacto ❌";
        $tipoMensaje = "error";
    }
}

// Mensajes PRG de los contactos
if (isset($_GET['c'])) {
    if ($_GET['c'] === 'ok')  { $mensaje = "Contacto agregado correctamente ✅"; $tipoMensaje = "success"; }
    if ($_GET['c'] === 'del') { $mensaje = "Contacto eliminado correctamente ✅"; $tipoMensaje = "success"; }
}

// --- LÓGICA PARA ELIMINAR NNA ---
if (isset($_POST['eliminar_nna'])) {
    try {
        $stmtDel = $pdo->prepare("DELETE FROM nna WHERE curp = :curp");
        $stmtDel->execute([':curp' => $curp_original]);
        header("Location: ver_nnas.php?mensaje=eliminado_exito");
        exit();
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar el registro ❌";
        $tipoMensaje = "error";
    }
}

// --- LÓGICA PARA ACTUALIZAR NNA ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['actualizar_nna'])) {
    $nombres    = strtoupper(trim($_POST['nombres'] ?? ''));
    $apellido_p = strtoupper(trim($_POST['apellido_p'] ?? ''));
    $apellido_m = strtoupper(trim($_POST['apellido_m'] ?? ''));
    $nacimiento = $_POST['nacimiento'] ?? '';
    $id_sexo    = !empty($_POST['id_sexo']) ? (int) $_POST['id_sexo'] : null;
    $id_pais    = !empty($_POST['id_pais']) ? (int) $_POST['id_pais'] : null;

    $situacion_calle    = ($_POST['situacion_calle'] ?? '') === 'Si' ? 'true' : 'false';
    $es_migrante        = ($_POST['es_migrante'] ?? '') === 'Si' ? 'true' : 'false';
    $es_refugiado       = ($_POST['es_refugiado'] ?? '') === 'Si' ? 'true' : 'false';
    $poblacion_indigena = ($_POST['poblacion_indigena'] ?? '') === 'Si' ? 'true' : 'false';

    if (!$id_sexo) {
        $mensaje = "Debes seleccionar el sexo ⚠️";
        $tipoMensaje = "error";
    } else {
        try {
            $pdo->beginTransaction();

            $sqlNna = "UPDATE nna SET 
                          nombre             = :nombre,
                          prim_ap            = :ap_p,
                          seg_ap             = :ap_m,
                          fecha_nacimiento   = :fnac,
                          id_sexo            = :id_sexo,
                          situacion_calle    = :sit_calle,
                          es_migrante        = :migrante,
                          es_refugiado       = :refugiado,
                          poblacion_indigena = :indigena
                       WHERE id_nna = :id_nna";
            $stmt = $pdo->prepare($sqlNna);
            $stmt->execute([
                ':nombre'    => $nombres,
                ':ap_p'      => $apellido_p,
                ':ap_m'      => $apellido_m !== '' ? $apellido_m : null,
                ':fnac'      => $nacimiento,
                ':id_sexo'   => $id_sexo,
                ':sit_calle' => $situacion_calle,
                ':migrante'  => $es_migrante,
                ':refugiado' => $es_refugiado,
                ':indigena'  => $poblacion_indigena,
                ':id_nna'    => $id_nna_actual
            ]);

            $pdo->prepare("DELETE FROM nna_nacionalidad WHERE id_nna = :id_nna")
                ->execute([':id_nna' => $id_nna_actual]);

            if ($id_pais) {
                $pdo->prepare("INSERT INTO nna_nacionalidad (id_nna, id_pais) VALUES (:id_nna, :id_pais)")
                    ->execute([':id_nna' => $id_nna_actual, ':id_pais' => $id_pais]);
            }

            $pdo->commit();
            $mensaje = "Información del NNA actualizada correctamente ✅";
            $tipoMensaje = "success";
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $mensaje = "Error al actualizar la información ❌";
            $tipoMensaje = "error";
        }
    }
}

// 3. Cargar datos actuales del NNA + país vinculado
try {
    $sql = "
        SELECT 
            n.id_nna, n.curp, n.nombre,
            n.prim_ap AS apellido_paterno,
            n.seg_ap  AS apellido_materno,
            n.fecha_nacimiento, n.id_sexo,
            n.situacion_calle, n.es_migrante, n.es_refugiado, n.poblacion_indigena,
            (SELECT nn.id_pais FROM nna_nacionalidad nn WHERE nn.id_nna = n.id_nna LIMIT 1) AS id_pais
        FROM nna n
        WHERE n.curp = :curp
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':curp' => $curp_original]);
    $nna = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $nna = null;
}

if (!$nna) { die("NNA no encontrado."); }

// Cargar los contactos adicionales ya registrados
$contactos = [];
try {
    $stmt = $pdo->prepare("
        SELECT ca.id_contacto, ca.valor_contacto, ca.descripcion, tc.nombre AS tipo
        FROM nna_contacto_adicional ca
        JOIN cat_tipo_contacto tc ON tc.id = ca.id_tipo_contacto
        WHERE ca.id_nna = :id_nna
        ORDER BY tc.nombre
    ");
    $stmt->execute([':id_nna' => $id_nna_actual]);
    $contactos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contactos = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar NNA</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-container { max-width: 600px; margin: 20px auto; padding: 30px; }
        label { display: block; margin-top: 15px; font-weight: bold; color: #2c3e50; }
        input[type="text"], select, input[type="date"] { width: 100%; padding: 8px; margin-top: 5px; text-transform: uppercase; }
        .btn-update { background-color: #27ae60; color: white; padding: 12px; border: none; width: 100%; cursor: pointer; border-radius: 5px; font-weight: bold; margin-top: 20px; }
        .btn-delete { background-color: #e74c3c; color: white; padding: 12px; border: none; width: 100%; cursor: pointer; border-radius: 5px; font-weight: bold; margin-top: 10px; }
        /* Estilos nuevos para contactos */
        .contacto-item { display: flex; justify-content: space-between; align-items: center; background: #f1f9ff; border: 1px solid #d1e9ff; border-radius: 6px; padding: 10px; margin-top: 8px; }
        .contacto-item .info { font-size: 14px; color: #2c3e50; }
        .contacto-item .info small { color: #7f8c8d; }
        .btn-mini-del { background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .contacto-form { background: #fafafa; border: 1px dashed #ccc; padding: 15px; border-radius: 6px; margin-top: 10px; }
        .seccion-contactos h2 { margin-top: 30px; color: #2980b9; border-bottom: 2px solid #eee; padding-bottom: 8px; }
    </style>
</head>
<body>

<div class="login-container">
    <a href="ver_nnas.php" style="text-decoration: none; color: #34495e;">⬅ Volver a la lista</a>
    <h1>Editar Información de NNA</h1>

    <?php if ($mensaje): ?>
        <p style="background-color: <?= $tipoMensaje=='success'?'#d4edda':'#f8d7da' ?>; color: <?= $tipoMensaje=='success'?'green':'red' ?>; padding: 10px; border-radius: 5px; font-weight: bold; text-align: center;">
            <?= htmlspecialchars($mensaje) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>CURP (No editable):</label>
        <input type="text" value="<?= htmlspecialchars($nna['curp'] ?? 'Sin CURP') ?>" disabled style="background-color: #eee;">

        <label>Nombres:</label>
        <input type="text" name="nombres" value="<?= htmlspecialchars($nna['nombre']) ?>" required>

        <label>Apellido Paterno:</label>
        <input type="text" name="apellido_p" value="<?= htmlspecialchars($nna['apellido_paterno']) ?>" required>

        <label>Apellido Materno:</label>
        <input type="text" name="apellido_m" value="<?= htmlspecialchars($nna['apellido_materno'] ?? '') ?>">

        <label>Fecha de Nacimiento:</label>
        <input type="date" name="nacimiento" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($nna['fecha_nacimiento']) ?>" required>

        <label>Sexo:</label>
        <select name="id_sexo" required>
            <option value="" disabled <?= empty($nna['id_sexo']) ? 'selected' : '' ?> hidden>SELECCIONE SEXO</option>
            <?php foreach ($sexos as $s): ?>
                <option value="<?= (int) $s['id'] ?>" <?= ($nna['id_sexo'] == $s['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(mb_strtoupper($s['nombre'], 'UTF-8')) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Nacionalidad:</label>
        <select name="id_pais">
            <option value="">NO ESPECIFICADA</option>
            <?php foreach ($paises as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= ($nna['id_pais'] == $p['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(mb_strtoupper($p['nombre'], 'UTF-8')) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>¿Situación de Calle?</label>
        <select name="situacion_calle">
            <option value="Si" <?= $nna['situacion_calle']=='t'?'selected':'' ?>>Si</option>
            <option value="No" <?= $nna['situacion_calle']=='f'?'selected':'' ?>>No</option>
        </select>

        <label>¿Es Migrante?</label>
        <select name="es_migrante">
            <option value="Si" <?= $nna['es_migrante']=='t'?'selected':'' ?>>Si</option>
            <option value="No" <?= $nna['es_migrante']=='f'?'selected':'' ?>>No</option>
        </select>

        <label>¿Es Refugiado?</label>
        <select name="es_refugiado">
            <option value="Si" <?= $nna['es_refugiado']=='t'?'selected':'' ?>>Si</option>
            <option value="No" <?= $nna['es_refugiado']=='f'?'selected':'' ?>>No</option>
        </select>

        <label>¿Pertenece a Población Indígena?</label>
        <select name="poblacion_indigena">
            <option value="Si" <?= $nna['poblacion_indigena']=='t'?'selected':'' ?>>Si</option>
            <option value="No" <?= $nna['poblacion_indigena']=='f'?'selected':'' ?>>No</option>
        </select>

        <button type="submit" name="actualizar_nna" class="btn-update">Guardar Cambios</button>
        <button type="submit" name="eliminar_nna" class="btn-delete" onclick="return confirm('¿Seguro que deseas eliminar este registro?')">Eliminar NNA</button>
    </form>

    <!-- ===== SECCIÓN NUEVA: CONTACTOS ADICIONALES ===== -->
    <div class="seccion-contactos">
        <h2>📇 Contactos Adicionales</h2>

        <?php if (count($contactos) > 0): ?>
            <?php foreach ($contactos as $c): ?>
                <div class="contacto-item">
                    <div class="info">
                        <strong><?= htmlspecialchars($c['tipo']) ?>:</strong> <?= htmlspecialchars($c['valor_contacto']) ?>
                        <?php if (!empty($c['descripcion'])): ?>
                            <br><small><?= htmlspecialchars($c['descripcion']) ?></small>
                        <?php endif; ?>
                    </div>
                    <form method="POST" style="margin:0;" onsubmit="return confirm('¿Borrar este contacto?');">
                        <input type="hidden" name="id_contacto" value="<?= (int) $c['id_contacto'] ?>">
                        <button type="submit" name="borrar_contacto" class="btn-mini-del">🗑️</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:#7f8c8d; font-style:italic; margin-top:10px;">Sin contactos adicionales registrados.</p>
        <?php endif; ?>

        <!-- Formulario para agregar un nuevo contacto -->
        <div class="contacto-form">
            <form method="POST">
                <label>Tipo de Contacto:</label>
                <select name="id_tipo_contacto" required style="text-transform:none;">
                    <option value="" disabled selected>-- Seleccione --</option>
                    <?php foreach ($tipos_contacto as $tc): ?>
                        <option value="<?= (int) $tc['id'] ?>"><?= htmlspecialchars($tc['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Valor (usuario, número, enlace...):</label>
                <input type="text" name="valor_contacto" required style="text-transform:none;" placeholder="Ej. @usuario_instagram">

                <label>Descripción (opcional):</label>
                <input type="text" name="descripcion_contacto" style="text-transform:none;" placeholder="Ej. Cuenta personal">

                <button type="submit" name="agregar_contacto" class="btn-update" style="margin-top:15px;">➕ Agregar Contacto</button>
            </form>
        </div>
    </div>

</div>

</body>
</html>