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

if ($_SERVER["REQUEST_METHOD"] === "POST") {  
    // --- Datos Personales (Superentidad Persona) ---
    $curp = strtoupper(trim($_POST['curp'] ?? ''));  
    $nombres = strtoupper(trim($_POST['nombres'] ?? ''));  
    $apellido_p = strtoupper(trim($_POST['apellido_p'] ?? ''));  
    $apellido_m = strtoupper(trim($_POST['apellido_m'] ?? ''));  
    $nacimiento = $_POST['nacimiento'] ?? '';  
    $sexo = $_POST['sexo'] ?? '';  

    // --- Dirección Completa (Ahora en Persona) ---
    $calle = strtoupper(trim($_POST['calle'] ?? ''));
    $num_ext = strtoupper(trim($_POST['num_ext'] ?? ''));
    $num_int = !empty($_POST['num_int']) ? strtoupper(trim($_POST['num_int'])) : 'NULL';
    $municipio = strtoupper(trim($_POST['municipio'] ?? ''));
    $estado_dir = $_POST['estado_dir'] ?? '';

    // --- Datos Específicos (Subentidad NNA) ---
    $nacionalidad = $_POST['nacionalidad'] ?? '';  
    $situacion_calle = ($_POST['situacion_calle'] ?? '') === 'Si' ? 'true' : 'false';  
    $es_migrante = ($_POST['migrante'] ?? '') === 'Si' ? 'true' : 'false';  
    $es_refugiado = ($_POST['refugiado'] ?? '') === 'Si' ? 'true' : 'false';  
    $poblacion_indigena = ($_POST['pob_indigena'] ?? '') === 'Si' ? 'true' : 'false';  

    if (empty($curp) || empty($nombres) || empty($apellido_p) || empty($nacimiento)) {  
        $mensaje = "Los campos básicos son obligatorios ⚠️";  
        $tipoMensaje = "error";  
    } else {  
        pg_query($conn, "BEGIN");

        // PASO 1: Insertar en la tabla 'persona' (incluye la dirección)
        $query_persona = "
            INSERT INTO persona (
                curp, nombre, apellido_paterno, apellido_materno,
                fecha_nacimiento, sexo, tipo_persona, 
                calle, numero_exterior, numero_interior, municipio, estado_dir
            ) VALUES (
                '$curp', '$nombres', '$apellido_p', " . ($apellido_m ? "'$apellido_m'" : 'NULL') . ",
                '$nacimiento', '$sexo', 'NNA',
                '$calle', '$num_ext', " . ($num_int !== 'NULL' ? "'$num_int'" : 'NULL') . ", '$municipio', '$estado_dir'
            )
        ";

        // PASO 2: Insertar en la tabla 'nna'
        $query_nna = "
            INSERT INTO nna (
                curp, nacionalidad, situacion_calle, es_migrante, es_refugiado, poblacion_indigena
            ) VALUES (
                '$curp', '$nacionalidad', $situacion_calle, $es_migrante, $es_refugiado, $poblacion_indigena
            )
        ";

        $res1 = @pg_query($conn, $query_persona);
        $res2 = @pg_query($conn, $query_nna);

        if ($res1 && $res2) {
            pg_query($conn, "COMMIT");
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit();
        } else {
            pg_query($conn, "ROLLBACK");
            $error = pg_last_error($conn);
            $mensaje = (strpos($error, 'persona_pkey') !== false) ? "La CURP ya existe ⚠️" : "Error: " . $error;
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
            <?= $mensaje ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>CURP:</label>
        <input type="text" name="curp" maxlength="18" required>

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
                <input type="date" name="nacimiento" required>
            </div>
            <div>
                <label>Sexo:</label>
                <select name="sexo" required>
                    <option value="Masculino">MASCULINO</option>
                    <option value="Femenino">FEMENINO</option>
                    <option value="Otro">OTRO</option>
                </select>
            </div>
        </div>

        <h3 style="margin-top:25px; border-bottom:1px solid #eee; color:#2980b9;">Dirección y Ubicación</h3>
        
        <label>Calle:</label>
        <input type="text" name="calle" required>

        <div class="grid-form">
            <div>
                <label>Núm. Exterior:</label>
                <input type="text" name="num_ext" required>
            </div>
            <div>
                <label>Núm. Interior:</label>
                <input type="text" name="num_int">
            </div>
        </div>

        <div class="grid-form">
            <div>
                <label>Municipio/Alcaldía:</label>
                <input type="text" name="municipio" required>
            </div>
            <div>
                <label>Estado:</label>
                <select name="estado_dir" required>
                    <option value="Ciudad de México">CIUDAD DE MÉXICO</option>
                    <option value="Estado de México">ESTADO DE MÉXICO</option>
                    <option value="Hidalgo">HIDALGO</option>
                    </select>
            </div>
        </div>

        <h3 style="margin-top:25px; border-bottom:1px solid #eee; color:#c0392b;">Datos de Vulnerabilidad</h3>
        
        <div class="grid-form">
            <div>
                <label>Nacionalidad:</label>
                <input type="text" name="nacionalidad" value="MÉXICO">
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

        <button type="submit" class="btn-submit">Registrar al NNA</button>
    </form>
</div>

</body>
</html>