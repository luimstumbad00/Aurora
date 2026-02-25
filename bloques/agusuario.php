<?php
session_start();

// 1. Validar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php"); 
    exit();
}

// 2. Validar que solo el Director o Coordinador puedan registrar
$rolActual = $_SESSION['usuario']['rol'];
if ($rolActual !== 'Director' && $rolActual !== 'Coordinador') {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../config/database.php';

$mensaje = "";
$tipoMensaje = "";

// PRG: Atrapamos el éxito si venimos de recargar la página
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $mensaje = "Usuario creado correctamente ✅";
    $tipoMensaje = "success";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Convertimos a MAYÚSCULAS todos los campos de texto
    $curp = strtoupper(trim($_POST['curp']));
    $rfc = strtoupper(trim($_POST['rfc']));
    $apellido_p = strtoupper(trim($_POST['apellido_p']));
    $apellido_m = strtoupper(trim($_POST['apellido_m']));
    $nombres = strtoupper(trim($_POST['nombres']));

    $calle = strtoupper(trim($_POST['calle']));
    $num_ext = strtoupper(trim($_POST['num_ext']));
    $num_int = !empty($_POST['num_int']) ? strtoupper(trim($_POST['num_int'])) : null;
    $cp = trim($_POST['cp']); 
    $municipio = strtoupper(trim($_POST['municipio']));
    
    // Estos los dejamos con su formato original porque la BD tiene validaciones estrictas (CHECK/ENUM)
    $estado_dir = $_POST['estado_dir'];
    $sexo = $_POST['sexo'];
    $tipo_personal = $_POST['tipo_personal'];
    $rol = !empty($_POST['rol']) ? $_POST['rol'] : null;
    $nacimiento = $_POST['nacimiento'];
    
    // El correo es una buena práctica guardarlo siempre en minúsculas
    $correo = strtolower(trim($_POST['correo']));

    $contrasena = $curp; // La contraseña temporal será su CURP (ya en mayúsculas)

    // Agregamos ::nombre_mex, ::direccion_mex y ::rol_enum como en la pantalla de modificación
    $query = "
        INSERT INTO usuario (
            curp, rfc, nombre, direccion,
            sexo, nacimiento, tipo_personal,
            rol, estado, correo, contrasena
        ) VALUES (
            $1, $2,
            ROW($3,$4,$5)::nombre_mex,
            ROW($6,$7,$8,$9,$10,$11)::direccion_mex,
            $12, $13, $14,
            $15::rol_enum, 'Activo', $16, $17
        )
    ";

    $result = @pg_query_params($conn, $query, array(
        $curp, $rfc,
        $apellido_p, $apellido_m, $nombres,
        $calle, $num_ext, $num_int, $cp, $municipio, $estado_dir,
        $sexo, $nacimiento, $tipo_personal,
        $rol, $correo, $contrasena
    ));

    if ($result) {
        // Redirigimos para limpiar el POST y que no se duplique al recargar la página (F5)
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
        exit();
    } else {
        // Manejo detallado de errores
        $error = pg_last_error($conn);

        if (strpos($error, 'usuario_pkey') !== false) {
            $mensaje = "La CURP ya está registrada ⚠️";
        } elseif (strpos($error, 'usuario_rfc_key') !== false) {
            $mensaje = "El RFC ya está registrado ⚠️";
        } elseif (strpos($error, 'usuario_correo_key') !== false) {
            $mensaje = "El correo electrónico ya está registrado ⚠️";
        } else {
            $mensaje = "Error al crear usuario ❌";
        }
        $tipoMensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar Usuario</title>
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

    <h1>Registrar Usuario</h1>

    <?php if ($mensaje): ?>
    <p style="color: <?php echo $tipoMensaje == 'success' ? 'green' : 'red'; ?>; font-weight: bold; background-color: <?php echo $tipoMensaje == 'success' ? '#d4edda' : '#f8d7da'; ?>; padding: 10px; border-radius: 5px;">
        <?php echo $mensaje; ?>
    </p>
    <?php endif; ?>

    <form method="POST">
        <label for="curp">CURP:</label>
        <input type="text" id="curp" name="curp" placeholder="Ej. ABCD123456EFGHIJ78" value="<?= htmlspecialchars($_POST['curp'] ?? '') ?>" required maxlength="18">
        
        <label for="rfc">RFC:</label>
        <input type="text" id="rfc" name="rfc" placeholder="Ej. ABCD123456789" value="<?= htmlspecialchars($_POST['rfc'] ?? '') ?>" required maxlength="13">

        <label for="apellido_p">Apellido Paterno:</label>
        <input type="text" id="apellido_p" name="apellido_p" value="<?= htmlspecialchars($_POST['apellido_p'] ?? '') ?>" required>
        
        <label for="apellido_m">Apellido Materno:</label>
        <input type="text" id="apellido_m" name="apellido_m" value="<?= htmlspecialchars($_POST['apellido_m'] ?? '') ?>" required>
        
        <label for="nombres">Nombres:</label>
        <input type="text" id="nombres" name="nombres" value="<?= htmlspecialchars($_POST['nombres'] ?? '') ?>" required>

        <label for="sexo">Sexo:</label>
        <select id="sexo" name="sexo" required>
            <option value="" disabled selected hidden>SELECCIONE SEXO</option>
            <option value="Masculino" <?= (($_POST['sexo'] ?? '') == 'Masculino') ? 'selected' : '' ?>>MASCULINO</option>
            <option value="Femenino" <?= (($_POST['sexo'] ?? '') == 'Femenino') ? 'selected' : '' ?>>FEMENINO</option>
            <option value="Otro" <?= (($_POST['sexo'] ?? '') == 'Otro') ? 'selected' : '' ?>>OTRO</option>
        </select>

        <label for="nacimiento">Fecha de Nacimiento:</label>
        <input type="date" id="nacimiento" name="nacimiento" max="<?php echo date('Y-m-d'); ?>" value="<?= htmlspecialchars($_POST['nacimiento'] ?? '') ?>" required>

        <label for="calle">Calle:</label>
        <input type="text" id="calle" name="calle" value="<?= htmlspecialchars($_POST['calle'] ?? '') ?>" required>
        
        <label for="num_ext">Número Exterior:</label>
        <input type="text" id="num_ext" name="num_ext" value="<?= htmlspecialchars($_POST['num_ext'] ?? '') ?>" required>
        
        <label for="num_int">Número Interior (Opcional):</label>
        <input type="text" id="num_int" name="num_int" value="<?= htmlspecialchars($_POST['num_int'] ?? '') ?>">
        
        <label for="cp">Código Postal:</label>
        <input type="text" id="cp" name="cp" value="<?= htmlspecialchars($_POST['cp'] ?? '') ?>" required>
        
        <label for="municipio">Municipio:</label>
        <input type="text" id="municipio" name="municipio" value="<?= htmlspecialchars($_POST['municipio'] ?? '') ?>" required>

        <label for="estado_dir">Estado:</label>
        <select id="estado_dir" name="estado_dir" required>
            <option value="" disabled selected hidden>SELECCIONE ESTADO</option>
            <?php 
            $estados = ["Aguascalientes", "Baja California", "Baja California Sur", "Campeche", "Chiapas", "Chihuahua", "Ciudad de México", "Coahuila", "Colima", "Durango", "Estado de México", "Guanajuato", "Guerrero", "Hidalgo", "Jalisco", "Michoacán", "Morelos", "Nayarit", "Nuevo León", "Oaxaca", "Puebla", "Querétaro", "Quintana Roo", "San Luis Potosí", "Sinaloa", "Sonora", "Tabasco", "Tamaulipas", "Tlaxcala", "Veracruz", "Yucatán", "Zacatecas"];
            foreach ($estados as $est) {
                $selected = (($_POST['estado_dir'] ?? '') == $est) ? 'selected' : '';
                // Se muestra en mayúsculas en pantalla, pero se envía el valor original ($est)
                echo "<option value=\"$est\" $selected>" . mb_strtoupper($est, 'UTF-8') . "</option>";
            }
            ?>
        </select>

        <label for="tipo_personal">Tipo de Personal:</label>
        <select id="tipo_personal" name="tipo_personal" required>
            <option value="" disabled selected hidden>SELECCIONE TIPO DE PERSONAL</option>
            <option value="Empleado" <?= (($_POST['tipo_personal'] ?? '') == 'Empleado') ? 'selected' : '' ?>>EMPLEADO</option>
            <option value="Voluntario" <?= (($_POST['tipo_personal'] ?? '') == 'Voluntario') ? 'selected' : '' ?>>VOLUNTARIO</option>
        </select>

        <label for="rol">Rol (Opcional):</label>
        <select id="rol" name="rol">
            <option value="" hidden>SELECCIONE UN ROL</option>
            <?php 
            $roles = ["Director", "Coordinador", "Psicologo", "Doctor", "Abogado", "Trabajador Social", "Analista"];
            foreach ($roles as $r) {
                $selected = (($_POST['rol'] ?? '') == $r) ? 'selected' : '';
                echo "<option value=\"$r\" $selected>" . mb_strtoupper($r, 'UTF-8') . "</option>";
            }
            ?>
        </select>

        <label for="correo">Correo Electrónico:</label>
        <input type="email" id="correo" name="correo" value="<?=($_POST['correo'] ?? '') ?>" required>

        <button type="submit" style="margin-top: 20px;">Crear Usuario</button>

    </form>

</div>

</body>
</html>