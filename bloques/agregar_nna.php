<?php  
session_start();  

// 1. Validar sesión y rol
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

if (isset($_GET['status']) && $_GET['status'] === 'success') {  
    $mensaje = "NNA registrado correctamente con dirección ✅";  
    $tipoMensaje = "success";  
}  

// Catálogos para poblar los <select>
$sexos = $estados = $municipios = $paises = [];
try {
    $sexos      = $pdo->query("SELECT id, nombre FROM cat_sexo ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $estados    = $pdo->query("SELECT id_ent, nom_ent FROM entidad_federativa ORDER BY nom_ent")->fetchAll(PDO::FETCH_ASSOC);
    $paises     = $pdo->query("SELECT id, nombre FROM cat_pais ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $municipios = $pdo->query("
        SELECT m.id_municipio, m.nom_mun, e.nom_ent
        FROM cat_municipio m
        INNER JOIN entidad_federativa e ON e.id_ent = m.id_ent
        ORDER BY e.nom_ent, m.nom_mun
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En producción: error_log($e->getMessage());
    $mensaje = "No se pudieron cargar los catálogos ❌";
    $tipoMensaje = "error";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {  
    // --- Datos personales del NNA ---
    $curp       = strtoupper(trim($_POST['curp'] ?? ''));  
    $nombres    = strtoupper(trim($_POST['nombres'] ?? ''));  
    $apellido_p = strtoupper(trim($_POST['apellido_p'] ?? ''));  
    $apellido_m = !empty($_POST['apellido_m']) ? strtoupper(trim($_POST['apellido_m'])) : null;  
    $nacimiento = $_POST['nacimiento'] ?? '';  
    $id_sexo    = !empty($_POST['id_sexo']) ? (int) $_POST['id_sexo'] : null;
    $id_estado_nac = !empty($_POST['luga_nac_nna']) ? (int) $_POST['luga_nac_nna'] : null;

    // --- Dirección (tabla direccion) ---
    $calle           = strtoupper(trim($_POST['calle'] ?? ''));
    $num_ext         = strtoupper(trim($_POST['num_ext'] ?? ''));
    $num_int         = !empty($_POST['num_int']) ? strtoupper(trim($_POST['num_int'])) : null;
    $colonia         = strtoupper(trim($_POST['colonia'] ?? ''));
    $cp              = trim($_POST['cp'] ?? '');
    $id_municipio    = !empty($_POST['id_municipio']) ? (int) $_POST['id_municipio'] : null;

    // --- Datos de vulnerabilidad (nna) ---
    $id_pais            = !empty($_POST['id_pais']) ? (int) $_POST['id_pais'] : null;
    $situacion_calle    = ($_POST['situacion_calle'] ?? '') === 'Si';  
    $es_migrante        = ($_POST['migrante'] ?? '') === 'Si';  
    $es_refugiado       = ($_POST['refugiado'] ?? '') === 'Si';  
    $poblacion_indigena = ($_POST['pob_indigena'] ?? '') === 'Si';  

    // Usuario que registra (para nna.registrado_por)
    $registrado_por = $_SESSION['usuario']['id_usuario'] ?? null;

    // Validaciones mínimas
    if (empty($curp) || empty($nombres) || empty($apellido_p) || empty($nacimiento)
        || !$id_sexo || empty($colonia) || empty($cp) || !$id_municipio) {  
        $mensaje = "Los campos básicos, sexo, colonia, C.P. y municipio son obligatorios ⚠️";  
        $tipoMensaje = "error";  
    } elseif (!preg_match('/^\d{5}$/', $cp)) {
        $mensaje = "El Código Postal debe tener exactamente 5 dígitos ⚠️";
        $tipoMensaje = "error";
    } else {  
        try {
            $pdo->beginTransaction();

            // PASO 1: Insertar la dirección y recuperar su id
            $sqlDir = "
                INSERT INTO direccion (
                    calle_dir, no_ext_dir, no_int_dir, colonia_abierta, codigo_postal, id_municipio
                ) VALUES (
                    :calle, :num_ext, :num_int, :colonia, :cp, :id_municipio
                ) RETURNING id_dir
            ";
            $stmtDir = $pdo->prepare($sqlDir);
            $stmtDir->execute([
                ':calle'        => $calle !== '' ? $calle : null,
                ':num_ext'      => $num_ext !== '' ? $num_ext : null,
                ':num_int'      => $num_int,
                ':colonia'      => $colonia,
                ':cp'           => $cp,
                ':id_municipio' => $id_municipio
            ]);
            $id_dir = $stmtDir->fetchColumn();

            // Folio único de ingreso autogenerado (folio_nna es NOT NULL UNIQUE)
            $folio = 'NNA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            // PASO 2: Insertar el NNA, enlazando la dirección recién creada
            $sqlNna = "
                INSERT INTO nna (
                    folio_nna, nombre, prim_ap, seg_ap, fecha_nacimiento, curp,
                    id_sexo, dir_actual, luga_nac_nna,
                    situacion_calle, es_migrante, es_refugiado, poblacion_indigena,
                    registrado_por
                ) VALUES (
                    :folio, :nombre, :prim_ap, :seg_ap, :fnac, :curp,
                    :id_sexo, :dir_actual, :luga_nac,
                    :sit_calle, :migrante, :refugiado, :indigena,
                    :registrado_por
                ) RETURNING id_nna
            ";
            $stmtNna = $pdo->prepare($sqlNna);
            $stmtNna->execute([
                ':folio'          => $folio,
                ':nombre'         => $nombres,
                ':prim_ap'        => $apellido_p,
                ':seg_ap'         => $apellido_m,
                ':fnac'           => $nacimiento,
                ':curp'           => $curp !== '' ? $curp : null,
                ':id_sexo'        => $id_sexo,
                ':dir_actual'     => $id_dir,
                ':luga_nac'       => $id_estado_nac,
                ':sit_calle'      => $situacion_calle ? 'true' : 'false',
                ':migrante'       => $es_migrante ? 'true' : 'false',
                ':refugiado'      => $es_refugiado ? 'true' : 'false',
                ':indigena'       => $poblacion_indigena ? 'true' : 'false',
                ':registrado_por' => $registrado_por
            ]);
            $id_nna = $stmtNna->fetchColumn();

            // PASO 3: Registrar la nacionalidad (N:M) si se eligió un país
            if ($id_pais) {
                $stmtNac = $pdo->prepare("
                    INSERT INTO nna_nacionalidad (id_nna, id_pais)
                    VALUES (:id_nna, :id_pais)
                ");
                $stmtNac->execute([
                    ':id_nna'  => $id_nna,
                    ':id_pais' => $id_pais
                ]);
            }

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $err = $e->getMessage();
            if (strpos($err, 'nna_curp_key') !== false) {
                $mensaje = "La CURP ya está registrada ⚠️";
            } elseif (strpos($err, 'chk_codigo_postal') !== false) {
                $mensaje = "El Código Postal no es válido (5 dígitos) ⚠️";
            } elseif (strpos($err, 'chk_curp_nna') !== false) {
                $mensaje = "La CURP debe tener 18 caracteres ⚠️";
            } else {
                $mensaje = "Error al registrar al NNA ❌";
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
    <title>Registrar NNA's</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .grid-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        label { display: block; margin-top: 15px; font-weight: bold; color: #2c3e50; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
        input[type="text"] { text-transform: uppercase; }
        .btn-submit { background: #27ae60; color: white; border: none; padding: 15px; width: 100%; margin-top: 25px; border-radius: 5px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

<div class="login-container" style="max-width: 700px;">
    <a href="dashboard.php" style="text-decoration:none; color:#7f8c8d;">⬅ Dashboard</a>
    <h1>Registrar NNA</h1>

    <?php if ($mensaje): ?>
        <p style="background:<?= $tipoMensaje=='success'?'#d4edda':'#f8d7da'?>; color:<?= $tipoMensaje=='success'?'green':'red'?>; padding:10px; border-radius:5px; text-align:center;">
            <?= htmlspecialchars($mensaje) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>CURP:</label>
        <input type="text" name="curp" maxlength="18">

        <div class="grid-form">
            <div>
                <label>Nombres:</label>
                <input type="text" name="nombres" required>
            </div>
            <div>
                <label>Apellido Paterno:</label>
                <input type="text" name="apellido_p" required>
            </div>
        </div>

        <label>Apellido Materno:</label>
        <input type="text" name="apellido_m">

        <div class="grid-form">
            <div>
                <label>Fecha de Nacimiento:</label>
                <input type="date" name="nacimiento" max="<?= date('Y-m-d') ?>" required>
            </div>
            <div>
                <label>Sexo:</label>
                <select name="id_sexo" required>
                    <option value="" disabled selected hidden>SELECCIONE SEXO</option>
                    <?php foreach ($sexos as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars(mb_strtoupper($s['nombre'], 'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid-form">
            <div>
                <label>Lugar de Nacimiento (Entidad):</label>
                <select name="luga_nac_nna">
                    <option value="">NO ESPECIFICADO</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?= (int) $e['id_ent'] ?>"><?= htmlspecialchars(mb_strtoupper($e['nom_ent'], 'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div></div>
        </div>

        <h3 style="margin-top:25px; border-bottom:1px solid #eee; color:#2980b9;">Dirección y Ubicación</h3>
        
        <label>Calle:</label>
        <input type="text" name="calle">

        <div class="grid-form">
            <div>
                <label>Núm. Exterior:</label>
                <input type="text" name="num_ext">
            </div>
            <div>
                <label>Núm. Interior:</label>
                <input type="text" name="num_int">
            </div>
        </div>

        <div class="grid-form">
            <div>
                <label>Colonia:</label>
                <input type="text" name="colonia" required>
            </div>
            <div>
                <label>Código Postal:</label>
                <input type="text" name="cp" maxlength="5" pattern="\d{5}" placeholder="5 dígitos" required>
            </div>
        </div>

        <div class="grid-form">
            <div>
                <label>Municipio/Alcaldía:</label>
                <select name="id_municipio" required>
                    <option value="" disabled selected hidden>SELECCIONE MUNICIPIO</option>
                    <?php foreach ($municipios as $m): ?>
                        <option value="<?= (int) $m['id_municipio'] ?>">
                            <?= htmlspecialchars(mb_strtoupper($m['nom_mun'] . ' — ' . $m['nom_ent'], 'UTF-8')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div></div>
        </div>

        <h3 style="margin-top:25px; border-bottom:1px solid #eee; color:#c0392b;">Datos de Vulnerabilidad</h3>
        
        <div class="grid-form">
            <div>
                <label>Nacionalidad:</label>
                <select name="id_pais">
                    <option value="">NO ESPECIFICADA</option>
                    <?php foreach ($paises as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= ($p['nombre'] === 'México') ? 'selected' : '' ?>>
                            <?= htmlspecialchars(mb_strtoupper($p['nombre'], 'UTF-8')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>¿Situación de Calle?</label>
                <select name="situacion_calle">
                    <option value="No">No</option>
                    <option value="Si">Si</option>
                </select>
            </div>
        </div>

        <div class="grid-form">
            <div>
                <label>¿Es Migrante?</label>
                <select name="migrante">
                    <option value="No">No</option>
                    <option value="Si">Si</option>
                </select>
            </div>
            <div>
                <label>¿Población Indígena?</label>
                <select name="pob_indigena">
                    <option value="No">No</option>
                    <option value="Si">Si</option>
                </select>
            </div>
        </div>

        <div class="grid-form">
            <div>
                <label>¿Es Refugiado?</label>
                <select name="refugiado">
                    <option value="No">No</option>
                    <option value="Si">Si</option>
                </select>
            </div>
            <div></div>
        </div>

        <button type="submit" class="btn-submit">Registrar al NNA</button>
    </form>
</div>

</body>
</html>