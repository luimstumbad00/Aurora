<?php
session_start();
require '../config/database.php';

// 1. Validar sesión y rol
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Administrador') {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

// 2. Identificar al niño (CURP_NNA)
$curp_nna = $_GET['curp_nna'] ?? $_POST['curp_nna_oculto'] ?? ''; 

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($curp_nna)) {
    
    // Captura de datos
    $curp_tutor   = pg_escape_string($conn, strtoupper(trim($_POST['curp_tutor'])));
    $nombre       = pg_escape_string($conn, strtoupper(trim($_POST['nombre'])));
    $apellido_p   = pg_escape_string($conn, strtoupper(trim($_POST['apellido_p'])));
    $apellido_m   = pg_escape_string($conn, strtoupper(trim($_POST['apellido_m'])));
    $sexo         = pg_escape_string($conn, $_POST['sexo']);
    $nacimiento   = pg_escape_string($conn, $_POST['nacimiento']);
    
    // Dirección (Superentidad Persona)
    $calle        = pg_escape_string($conn, strtoupper(trim($_POST['calle'])));
    $num_ext      = pg_escape_string($conn, strtoupper(trim($_POST['num_ext'])));
    $num_int      = !empty($_POST['num_int']) ? pg_escape_string($conn, strtoupper(trim($_POST['num_int']))) : null;
    $municipio    = pg_escape_string($conn, strtoupper(trim($_POST['municipio'])));
    $estado_dir   = pg_escape_string($conn, $_POST['estado_dir']);

    // Datos Tutor y Parentesco
    $telefono     = pg_escape_string($conn, trim($_POST['telefono']));
    $correo       = pg_escape_string($conn, strtolower(trim($_POST['correo'])));
    $es_adulto    = ($_POST['es_adulto_mayor'] === 'Si') ? 'true' : 'false';
    $relacion     = pg_escape_string($conn, strtoupper(trim($_POST['relacion'])));

    pg_query($conn, "BEGIN");

    // PASO 1: Insertar/Actualizar Persona (Base)
    $qPersona = "INSERT INTO persona (curp, nombre, apellido_paterno, apellido_materno, sexo, fecha_nacimiento, tipo_persona, municipio, estado_dir, calle, numero_exterior, numero_interior) 
                 VALUES ('$curp_tutor', '$nombre', '$apellido_p', " . ($apellido_m ? "'$apellido_m'" : "NULL") . ", '$sexo', '$nacimiento', 'TUTOR', '$municipio', '$estado_dir', '$calle', '$num_ext', " . ($num_int ? "'$num_int'" : "NULL") . ")
                 ON CONFLICT (curp) DO UPDATE SET 
                 nombre = EXCLUDED.nombre, apellido_paterno = EXCLUDED.apellido_paterno, calle = EXCLUDED.calle, municipio = EXCLUDED.municipio, estado_dir = EXCLUDED.estado_dir;";
    $res1 = pg_query($conn, $qPersona);

    // PASO 2: Insertar/Actualizar Tutor (Extensión)
    $qTutor = "INSERT INTO tutor (curp, es_adulto_mayor, telefono, correo) 
               VALUES ('$curp_tutor', $es_adulto, '$telefono', '$correo')
               ON CONFLICT (curp) DO UPDATE SET telefono = EXCLUDED.telefono, correo = EXCLUDED.correo;";
    $res2 = pg_query($conn, $qTutor);

    // PASO 3: Vincular con el Niño y guardar parentesco
    $qRel = "INSERT INTO nna_tutor (curp_nna, curp_tutor, relacion) 
             VALUES ('$curp_nna', '$curp_tutor', '$relacion')
             ON CONFLICT (curp_nna, curp_tutor) 
             DO UPDATE SET relacion = EXCLUDED.relacion;";
    $res3 = pg_query($conn, $qRel);

    if ($res1 && $res2 && $res3) {
        pg_query($conn, "COMMIT");
        $mensaje = "¡Tutor vinculado exitosamente! ✅";
        $tipoMensaje = "success";
    } else {
        pg_query($conn, "ROLLBACK");
        $mensaje = "Error en Base de Datos: " . pg_last_error($conn);
        $tipoMensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Tutor - Aurora</title>
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; padding: 20px; }
        .card { background: white; width: 100%; max-width: 650px; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        h1 { color: #1a2a6c; text-align: center; }
        label { display: block; margin-top: 12px; font-weight: bold; color: #34495e; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .row { display: flex; gap: 15px; }
        .row > div { flex: 1; }
        .btn { background: #27ae60; color: white; border: none; width: 100%; padding: 15px; margin-top: 25px; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; color: white; font-weight: bold; }
        .success { background: #2ecc71; }
        .error { background: #e74c3c; }
    </style>
</head>
<body>
<div class="card">
    <a href="ver_nnas.php" style="text-decoration:none; color:#7f8c8d; font-size:13px;">⬅ Volver a Directorio</a>
    <h1>Asignar Tutor</h1>

    <?php if ($mensaje): ?>
        <div class="alert <?= $tipoMensaje ?>"><?= $mensaje ?></div>
    <?php endif; ?>

    <div style="background:#e3f2fd; padding:10px; border-left:5px solid #2196f3; margin-bottom:20px;">
        Expediente del Niño: <strong><?= htmlspecialchars($curp_nna) ?></strong>
    </div>

    <form method="POST">
        <input type="hidden" name="curp_nna_oculto" value="<?= htmlspecialchars($curp_nna) ?>">
        
        <label>CURP del Tutor:</label>
        <input type="text" name="curp_tutor" required maxlength="18" placeholder="CURP a 18 caracteres">

        <div class="row">
            <div>
                <label>Nombre(s):</label>
                <input type="text" name="nombre" required>
            </div>
            <div>
                <label>Apellido Paterno:</label>
                <input type="text" name="apellido_p" required>
            </div>
        </div>

        <div class="row">
            <div>
                <label>Apellido Materno:</label>
                <input type="text" name="apellido_m">
            </div>
            <div>
                <label>Parentesco / Relación:</label>
                <select name="relacion" required>
                    <option value="" disabled selected>Seleccione...</option>
                    <option value="MADRE">MADRE</option>
                    <option value="PADRE">PADRE</option>
                    <option value="ABUELO/A">ABUELO/A</option>
                    <option value="TÍO/A">TÍO/A</option>
                    <option value="HERMANO/A">HERMANO/A</option>
                    <option value="TUTOR LEGAL">TUTOR LEGAL</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div>
                <label>Sexo:</label>
                <select name="sexo">
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                </select>
            </div>
            <div>
                <label>Fecha de Nacimiento:</label>
                <input type="date" name="nacimiento" required>
            </div>
        </div>

        <h3 style="margin-top:20px; color:#2196f3; border-bottom:1px solid #ddd;">Domicilio del Tutor</h3>
        <label>Calle:</label>
        <input type="text" name="calle" required>

        <div class="row">
            <div>
                <label>Núm Ext:</label>
                <input type="text" name="num_ext" required>
            </div>
            <div>
                <label>Núm Int:</label>
                <input type="text" name="num_int">
            </div>
        </div>

        <div class="row">
            <div>
                <label>Municipio:</label>
                <input type="text" name="municipio" required>
            </div>
            <div>
                <label>Estado:</label>
                <input type="text" name="estado_dir" value="CIUDAD DE MÉXICO" required>
            </div>
        </div>

        <h3 style="margin-top:20px; color:#27ae60; border-bottom:1px solid #ddd;">Contacto</h3>
        <div class="row">
            <div>
                <label>Teléfono:</label>
                <input type="text" name="telefono">
            </div>
            <div>
                <label>¿Adulto Mayor?</label>
                <select name="es_adulto_mayor">
                    <option value="No">No</option>
                    <option value="Si">Si</option>
                </select>
            </div>
        </div>

        <label>Correo Electrónico:</label>
        <input type="email" name="correo">

        <button type="submit" class="btn">FINALIZAR REGISTRO</button>
    </form>
</div>
</body>
</html>