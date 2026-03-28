<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/database.php';

$curp_nna = $_GET['curp_nna'] ?? ''; 
$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($curp_nna)) {
    // 1. Recibir y limpiar datos
    $curp_tutor = pg_escape_string($conn, strtoupper(trim($_POST['curp'])));
    $nombre = pg_escape_string($conn, strtoupper(trim($_POST['nombre'])));
    $apellido_p = pg_escape_string($conn, strtoupper(trim($_POST['apellido_paterno'])));
    $apellido_m = pg_escape_string($conn, strtoupper(trim($_POST['apellido_materno'])));
    $sexo = pg_escape_string($conn, $_POST['sexo']);
    $telefono = pg_escape_string($conn, trim($_POST['telefono']));
    $correo = pg_escape_string($conn, trim($_POST['correo']));
    $es_adulto_mayor = ($_POST['es_adulto_mayor'] ?? '') === 'Si' ? 'true' : 'false';

    pg_query($conn, "BEGIN");

    $queryTutor = "INSERT INTO tutor (curp, nombre, apellido_paterno, apellido_materno, sexo, es_adulto_mayor, telefono, correo) 
                   VALUES ('$curp_tutor', '$nombre', '$apellido_p', " . ($apellido_m ? "'$apellido_m'" : "NULL") . ", '$sexo', $es_adulto_mayor, '$telefono', '$correo')
                   ON CONFLICT (curp) DO UPDATE SET 
                   nombre = EXCLUDED.nombre, 
                   apellido_paterno = EXCLUDED.apellido_paterno,
                   telefono = EXCLUDED.telefono;";

    $resTutor = pg_query($conn, $queryTutor);

    $queryRelacion = "INSERT INTO nna_tutor (curp_nna, curp_tutor, relacion) 
                      VALUES ('$curp_nna', '$curp_tutor', 'TUTOR ASIGNADO')
                      ON CONFLICT (curp_nna, curp_tutor) DO NOTHING;";

    $resRel = pg_query($conn, $queryRelacion);

    if ($resTutor && $resRel) {
        pg_query($conn, "COMMIT");
        $mensaje = "Tutor asignado y vinculado correctamente ✅";
        $tipoMensaje = "success";
    } else {
        pg_query($conn, "ROLLBACK");
        $error = pg_last_error($conn);
        $mensaje = "Error al vincular: " . htmlspecialchars($error);
        $tipoMensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Tutor</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .info-nna {
            background-color: #e8f4f8;
            padding: 10px;
            border-left: 5px solid #3498db;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        input[type="text"], input[type="email"] { text-transform: uppercase; }
        .btn-regresar {
            display: inline-block;
            margin-bottom: 15px;
            color: #34495e;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="login-container" style="max-width: 500px;">
    <a href="ver_nnas.php" class="btn-regresar">⬅ Volver a la lista</a>
    
    <h1>Registro de Tutor</h1>

    <?php if ($curp_nna): ?>
        <div class="info-nna">
            Asignando tutor al NNA con CURP: <strong><?= htmlspecialchars($curp_nna) ?></strong>
        </div>
    <?php endif; ?>

    <?php if ($mensaje): ?>
        <p style="color: white; background-color: <?= $tipoMensaje == 'success' ? '#27ae60' : '#e74c3c' ?>; padding: 10px; border-radius: 5px;">
            <?= $mensaje ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>CURP del Tutor:</label>
        <input type="text" name="curp" maxlength="18" required>

        <label>Nombre(s):</label>
        <input type="text" name="nombre" required>

        <label>Apellido Paterno:</label>
        <input type="text" name="apellido_paterno" required>

        <label>Apellido Materno:</label>
        <input type="text" name="apellido_materno">

        <label>Sexo:</label>
        <select name="sexo" required>
            <option value="" disabled selected>Seleccione...</option>
            <option value="Masculino">MASCULINO</option>
            <option value="Femenino">FEMENINO</option>
            <option value="Otro">OTRO</option>
        </select>

        <label>¿Es Adulto Mayor?</label>
        <select name="es_adulto_mayor" required>
            <option value="No">No</option>
            <option value="Si">Si</option>
        </select>

        <label>Teléfono:</label>
        <input type="text" name="telefono" placeholder="Ej. 5512345678">

        <label>Correo Electrónico:</label>
        <input type="email" name="correo" style="text-transform: lowercase;">

        <button type="submit" style="margin-top: 20px; background-color: #27ae60;">Registrar Tutor</button>
    </form>
</div>

</body>
</html>