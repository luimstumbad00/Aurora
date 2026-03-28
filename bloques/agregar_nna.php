<?php  
session_start();  

// 1. Validar sesión  
if (!isset($_SESSION['usuario'])) {  
    header("Location: ../index.php");  
    exit();  
}  

// 2. Validar rol (solo Director o Coordinador)  
$rolActual = $_SESSION['usuario']['rol'];  
if ($rolActual !== 'Director' && $rolActual !== 'Coordinador') {  
    header("Location: dashboard.php?error=acceso_denegado");  
    exit();  
}  

// Errores (solo desarrollo)  
ini_set('display_errors', 1);  
error_reporting(E_ALL);  

require '../config/database.php';  

$mensaje = "";  
$tipoMensaje = "";  

// Mensaje de éxito tras redirección (PRG)  
if (isset($_GET['status']) && $_GET['status'] === 'success') {  
    $mensaje = "NNA registrado correctamente ✅";  
    $tipoMensaje = "success";  
}  

if ($_SERVER["REQUEST_METHOD"] === "POST") {  
    // --- Campos obligatorios (validación básica) ---  
    $curp = trim($_POST['curp'] ?? '');  
    $nombres = trim($_POST['nombres'] ?? '');  
    $apellido_p = trim($_POST['apellido_p'] ?? '');  
    $apellido_m = trim($_POST['apellido_m'] ?? '');  
    $nacimiento = $_POST['nacimiento'] ?? '';  
    $sexo = $_POST['sexo'] ?? '';  
    $nacionalidad = $_POST['nacionalidad'] ?? '';  

    // Campos de dirección  
    $calle = trim($_POST['calle'] ?? '');  
    $num_ext = trim($_POST['num_ext'] ?? '');  
    $num_int = !empty($_POST['num_int']) ? trim($_POST['num_int']) : null;  

    // Campos booleanos (convertir 'Si'/'No' → true/false)  
    $situacion_calle = ($_POST['situacion_calle'] ?? '') === 'Si';  
    $es_migrante = ($_POST['migrante'] ?? '') === 'Si';  
    $es_refugiado = ($_POST['refugiado'] ?? '') === 'Si';  
    $poblacion_indigena = ($_POST['pob_indigena'] ?? '') === 'Si';  

    // --- Validación mínima (requeridos por tu tabla `nna`) ---  
    if (empty($curp) || empty($nombres) || empty($apellido_p) || empty($nacimiento) || empty($sexo) || empty($nacionalidad)) {  
        $mensaje = "Los campos CURP, Nombres, Apellido Paterno, Fecha de nacimiento, Sexo y Nacionalidad son obligatorios ⚠️";  
        $tipoMensaje = "error";  
    } else {  
        // Preparar valores para inserción (escapar/limpiar)  
        $curp = pg_escape_string($conn, $curp);  
        $nombres = pg_escape_string($conn, strtoupper($nombres));  
        $apellido_p = pg_escape_string($conn, strtoupper($apellido_p));  
        $apellido_m = pg_escape_string($conn, strtoupper($apellido_m));
        $calle = pg_escape_string($conn, strtoupper($calle));
        $num_ext = pg_escape_string($conn, strtoupper($num_ext));
        $num_int = !empty($num_int) ? pg_escape_string($conn, strtoupper($num_int)) : 'NULL';
        $nacionalidad = pg_escape_string($conn, $nacionalidad); // ✅ se guarda tal cual (ej. "México")
        $sexo = pg_escape_string($conn, $sexo);

        // Convertir booleanos a 'true'/'false' para PostgreSQL
        $situacion_calle_str = $situacion_calle ? 'true' : 'false';
        $es_migrante_str = $es_migrante ? 'true' : 'false';
        $es_refugiado_str = $es_refugiado ? 'true' : 'false';
        $poblacion_indigena_str = $poblacion_indigena ? 'true' : 'false';

        // --- Consulta INSERT para tabla `nna` ---
        $query = "
            INSERT INTO nna (
                curp, nombre, apellido_paterno, apellido_materno,
                fecha_nacimiento, sexo, nacionalidad,
                calle, num_ext, num_int,
                situacion_calle, es_migrante, es_refugiado, poblacion_indigena
            ) VALUES (
                '$curp', '$nombres', '$apellido_p', " . ($apellido_m ? "'$apellido_m'" : 'NULL') . ",
                '$nacimiento', '$sexo', '$nacionalidad',
                '$calle', '$num_ext', " . ($num_int !== 'NULL' ? "'$num_int'" : 'NULL') . ",
                $situacion_calle_str, $es_migrante_str, $es_refugiado_str, $poblacion_indigena_str
            )
        ";

        $result = pg_query($conn, $query);

        if ($result) {
            // Éxito → redirigir para evitar reenvío (PRG)
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit();
        } else {
            // Error detallado
            $error = pg_last_error($conn);
            if (strpos($error, 'nna_pkey') !== false || strpos($error, 'unique') !== false) {
                $mensaje = "La CURP ya está registrada ⚠️";
            } elseif (strpos($error, 'check') !== false) {
                $mensaje = "Datos inválidos (ej. fecha fuera de rango o valor no permitido) ⚠️";
            } else {
                $mensaje = "Error al registrar al NNA ❌<br><small>" . htmlspecialchars($error) . "</small>";
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
    .btn-regresar {
        display: inline-block;
        margin-bottom: 20px;
        padding: 10px 15px;
        background-color: #34495e;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
        font-family: Arial, sans-serif;
        transition: background-color 0.3s;
    }
    .btn-regresar:hover {
        background-color: #2c3e50;
    }
    label {
        display: block;
        text-align: left;
        margin-top: 15px;
        margin-bottom: 5px;
        font-weight: bold;
        color: #2c3e50;
        font-size: 14px;
    }
    /* Forzamos a que todo lo que se escriba en los inputs de texto se vea en MAYÚSCULAS automáticamente */
    input[type="text"] {
        text-transform: uppercase;
    }
</style>
</head>
<body>

<div class="login-container">

    <div style="text-align: left;">
        <a href="dashboard.php" class="btn-regresar">⬅ Regresar al Dashboard</a>
    </div>

    <h1>Registrar NNA</h1>

    <?php if ($mensaje): ?>
    <p style="color: <?php echo $tipoMensaje == 'success' ? 'green' : 'red'; ?>; font-weight: bold; background-color: <?php echo $tipoMensaje == 'success' ? '#d4edda' : '#f8d7da'; ?>; padding: 10px; border-radius: 5px;">
        <?php echo $mensaje; ?>
    </p>
    <?php endif; ?>

    <form method="POST">
        <label for="curp">CURP:</label>
        <input type="text" id="curp" name="curp" placeholder="Ej. ABCD123456EFGHIJ78" value="<?= htmlspecialchars($_POST['curp'] ?? '') ?>" required maxlength="18">
        
        <label for="nombres">Nombres:</label>
        <input type="text" id="nombres" name="nombres" value="<?= htmlspecialchars($_POST['nombres'] ?? '') ?>" required maxlength="100">


        <label for="apellido_p">Apellido Paterno:</label>
        <input type="text" id="apellido_p" name="apellido_p" value="<?= htmlspecialchars($_POST['apellido_p'] ?? '') ?>" required maxlength="50">
        
        <label for="apellido_m">Apellido Materno:</label>
        <input type="text" id="apellido_m" name="apellido_m" value="<?= htmlspecialchars($_POST['apellido_m'] ?? '') ?>" required maxlength="50">
        
        <label for="nacimiento">Fecha de Nacimiento:</label>
        <input type="date" id="nacimiento" name="nacimiento" max="<?php echo date('Y-m-d'); ?>" value="<?= htmlspecialchars($_POST['nacimiento'] ?? '') ?>" required>

        <label for="sexo">Sexo:</label>
        <select id="sexo" name="sexo" required>
            <option value="" disabled selected hidden>SELECCIONE SEXO</option>
            <option value="Masculino" <?= (($_POST['sexo'] ?? '') == 'Masculino') ? 'selected' : '' ?>>MASCULINO</option>
            <option value="Femenino" <?= (($_POST['sexo'] ?? '') == 'Femenino') ? 'selected' : '' ?>>FEMENINO</option>
            <option value="Otro" <?= (($_POST['sexo'] ?? '') == 'Otro') ? 'selected' : '' ?>>OTRO</option>
        </select>

        <label for="nacionalidad">Nacionalidad:</label>
        <select id="nacionalidad" name="nacionalidad" required>
            <option value="" disabled selected hidden>SELECCIONE EL PAÍS</option>
            <?php 
            $paises = ["Afganistán", "Albania", "Alemania", "Andorra", "Angola", "Antigua y Barbuda", "Arabia Saudita", "Argelia", "Argentina", "Armenia", "Australia", "Austria", "Azerbaiyán", "Bahamas", "Bahréin", "Bangladés", "Barbados", "Bélgica", "Belice", "Benín", "Bielorrusia", "Bolivia", "Bosnia y Herzegovina", "Brasil", "Bulgaria", "Bután", "Camboya", "Camerún", "Canadá", "Catar", "Chad", "Chile", "China", "Chipre", "Colombia", "Comoras", "Congo", "Corea del Norte", "Corea del Sur", "Costa de Marfil", "Costa Rica", "Croacia", "Cuba", "Dinamarca", "Dominica", "República Dominicana", "Ecuador", "Egipto", "El Salvador", "Emiratos Árabes Unidos", "Eritrea", "Eslovaquia", "Eslovenia", "España", "Estados Unidos", "Estonia", "Esuatini", "Etiopía", "Filipinas", "Finlandia", "Fiyi", "Francia", "Gabón", "Gambia", "Georgia", "Ghana", "Granada", "Grecia", "Guatemala", "Guinea", "Guinea-Bisáu", "Guinea Ecuatorial", "Guyana", "Haití", "Honduras", "Hungría", "India", "Indonesia", "Irak", "Irán", "Irlanda", "Islandia", "Islas Marshall", "Islas Salomón", "Israel", "Italia", "Jamaica", "Japón", "Jordania", "Kazajistán", "Kenia", "Kirguistán", "Kiribati", "Kuwait", "Laos", "Lesoto", "Letonia", "Líbano", "Liberia", "Libia", "Liechtenstein", "Lituania", "Luxemburgo", "Macedonia del Norte", "Madagascar", "Malasia", "Malaui", "Maldivas", "Mali", "Malta", "Marruecos", "Mauricio", "Mauritania", "México", "Micronesia", "Moldavia", "Mónaco", "Mongolia", "Montenegro", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Nicaragua", "Níger", "Nigeria", "Noruega", "Nueva Zelanda", "Omán", "Países Bajos", "Pakistán", "Palaos", "Panamá", "Papúa Nueva Guinea", "Paraguay", "Perú", "Polonia", "Portugal", "Reino Unido", "República Centroafricana", "República Checa", "República Democrática del Congo", "Ruanda", "Rumania", "Rusia", "Samoa", "San Cristóbal y Nieves", "San Marino", "San Vicente y las Granadinas", "Santa Lucía", "Santo Tomé y Príncipe", "Senegal", "Serbia", "Seychelles", "Sierra Leona", "Singapur", "Siria", "Somalia", "Sri Lanka", "Sudáfrica", "Sudán", "Sudán del Sur", "Suecia", "Suiza", "Surinam", "Tailandia", "Taiwán", "Tanzania", "Tayikistán", "Timor Oriental", "Togo", "Tonga", "Trinidad y Tobago", "Túnez", "Turkmenistán", "Turquía", "Tuvalu", "Ucrania", "Uganda", "Uruguay", "Uzbekistán", "Vanuatu", "Vaticano", "Venezuela", "Vietnam", "Yemen", "Yibuti", "Zambia", "Zimbabue"];
            foreach ($paises as $pais) {
                $selected = (($_POST['nacionalidad'] ?? '') == $pais) ? 'selected' : '';
                // Se muestra en mayúsculas en pantalla, pero se envía el valor original ($pais)
                echo "<option value=\"$pais\" $selected>" . mb_strtoupper($pais, 'UTF-8') . "</option>";
            }
            ?>
        </select>

        <label for="calle">Calle:</label>
        <input type="text" id="calle" name="calle" value="<?= htmlspecialchars($_POST['calle'] ?? '') ?>" required maxlength="100">
        
        <label for="num_ext">Número Exterior:</label>
        <input type="text" id="num_ext" name="num_ext" value="<?= htmlspecialchars($_POST['num_ext'] ?? '') ?>" required>
        
        <label for="num_int">Número Interior (Opcional):</label>
        <input type="text" id="num_int" name="num_int" value="<?= htmlspecialchars($_POST['num_int'] ?? '') ?>">
        
        <label for="situacion_calle">Situación de Calle:</label>
        <select id="situacion_calle" name="situacion_calle" required>
            <option value="" disabled selected hidden>SELECCIONE SI LO ESTÁ O NO</option>
            <option value="Si" <?= (($_POST['situacion_calle'] ?? '') == 'Si') ? 'selected' : '' ?>>Si</option>
            <option value="No" <?= (($_POST['situacion_calle'] ?? '') == 'No') ? 'selected' : '' ?>>No</option>
        </select>

        <label for="migrante">Migrante:</label>
        <select id="migrante" name="migrante" required>
            <option value="" disabled selected hidden>SELECCIONE SI LO ES O NO</option>
            <option value="Si" <?= (($_POST['migrante'] ?? '') == 'Si') ? 'selected' : '' ?>>Si</option>
            <option value="No" <?= (($_POST['migrante'] ?? '') == 'No') ? 'selected' : '' ?>>No</option>
        </select>

        <label for="refugiado">Refugiado:</label>
        <select id="refugiado" name="refugiado" required>
            <option value="" disabled selected hidden>SELECCIONE SI LO ES O NO</option>
            <option value="Si" <?= (($_POST['refugiado'] ?? '') == 'Si') ? 'selected' : '' ?>>Si</option>
            <option value="No" <?= (($_POST['refugiado'] ?? '') == 'No') ? 'selected' : '' ?>>No</option>
        </select>

        <label for="pob_indigena">Población Indígnea:</label>
        <select id="pob_indigena" name="pob_indigena" required>
            <option value="" disabled selected hidden>SELECCIONE SI LO ES O NO</option>
            <option value="Si" <?= (($_POST['pob_indigena'] ?? '') == 'Si') ? 'selected' : '' ?>>Si</option>
            <option value="No" <?= (($_POST['pob_indigena'] ?? '') == 'No') ? 'selected' : '' ?>>No</option>
        </select>

        <button type="submit" style="margin-top: 20px;">Registrar al NNA</button>

    </form>

</div>

</body>
</html>