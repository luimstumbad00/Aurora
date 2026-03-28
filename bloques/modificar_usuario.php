<?php
session_start(); // Arrancamos la sesión que viene del login

// 1. Validar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    // Si no hay sesión, lo mandamos al login (ajusta la ruta de tu index.php o login.php si es necesario)
    header("Location: ../index.php"); 
    exit();
}

// 2. CORRECCIÓN: Validar que solo el Administrador (nuevo rol) pueda estar aquí
$rolActual = $_SESSION['usuario']['rol'];

if ($rolActual !== 'Administrador') {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../config/database.php';

$mensaje = "";
$tipoMensaje = "";
$usuario = null;

// Atrapamos el mensaje de éxito si venimos de una redirección (Patrón PRG)
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $mensaje = "Datos del usuario actualizados correctamente ✅";
    $tipoMensaje = "success";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // FASE 1: BUSCAR USUARIO
    if (isset($_POST['buscar']) || isset($_POST['curp_buscar'])) {
        $curp_buscar = strtoupper(trim($_POST['curp_buscar']));
        
        // CORRECCIÓN: Consulta SELECT ajustada a las columnas simples de la nueva BD
        $query_buscar = "
            SELECT 
                curp, rfc, 
                apellido_paterno AS apellido_p, 
                apellido_materno AS apellido_m, 
                nombre AS nombres, 
                calle, 
                numero_exterior AS num_ext, 
                numero_interior AS num_int, 
                codigo_postal AS cp, 
                municipio, 
                estado_dir, 
                sexo, nacimiento, tipo_personal, rol, correo 
            FROM usuario 
            WHERE curp = $1
        ";
        
        $result_buscar = pg_query_params($conn, $query_buscar, array($curp_buscar));
        
        if ($result_buscar && pg_num_rows($result_buscar) > 0) {
            $usuario = pg_fetch_assoc($result_buscar);
            $mensaje = "Usuario encontrado. Puede modificar sus datos. ✏️";
            $tipoMensaje = "success";
        } else {
            $mensaje = "Usuario no encontrado ⚠️";
            $tipoMensaje = "error";
        }
    } 
    
    // FASE 2: ACTUALIZAR USUARIO
    elseif (isset($_POST['actualizar'])) {
        $curp = strtoupper(trim($_POST['curp'])); 
        $rfc = strtoupper(trim($_POST['rfc']));
        $apellido_p = strtoupper(trim($_POST['apellido_p']));
        $apellido_m = strtoupper(trim($_POST['apellido_m']));
        $nombres = strtoupper(trim($_POST['nombres']));

        $calle = $_POST['calle'];
        $num_ext = $_POST['num_ext'];
        $num_int = $_POST['num_int'] ?: null;
        $cp = $_POST['cp'];
        $municipio = $_POST['municipio'];
        $estado_dir = $_POST['estado_dir'];

        $sexo = $_POST['sexo'];
        $nacimiento = $_POST['nacimiento'];
        $tipo_personal = $_POST['tipo_personal'];
        $rol = $_POST['rol'] ?: null;
        $correo = $_POST['correo'];

        // CORRECCIÓN: Consulta UPDATE ajustada a las columnas simples (sin usar ROW ni tipos compuestos)
        $query_actualizar = "
            UPDATE usuario SET 
                rfc = $2, 
                nombre = $3, 
                apellido_paterno = $4,
                apellido_materno = $5,
                calle = $6,
                numero_exterior = $7,
                numero_interior = $8,
                codigo_postal = $9,
                municipio = $10,
                estado_dir = $11,
                sexo = $12, 
                nacimiento = $13, 
                tipo_personal = $14, 
                rol = $15::rol_usuario, 
                correo = $16
            WHERE curp = $1
        ";

        $result_actualizar = @pg_query_params($conn, $query_actualizar, array(
            $curp, $rfc,
            $nombres, $apellido_p, $apellido_m, // El orden cambió para coincidir con los parámetros $3, $4, $5
            $calle, $num_ext, $num_int, $cp, $municipio, $estado_dir,
            $sexo, $nacimiento, $tipo_personal,
            $rol, $correo
        ));

        if ($result_actualizar) {
            // ¡Aquí ocurre la magia! Redirigimos a la misma página para limpiar el historial POST del navegador
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit();
        } else {
            $error = pg_last_error($conn);
            if (strpos($error, 'usuario_correo_key') !== false) {
                $mensaje = "El correo ya está registrado por otro usuario ⚠️";
            } else {
                $mensaje = "Error al actualizar usuario ❌";
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
    <title>Buscar y Modificar Usuario</title>
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
        /* Estilos para las etiquetas de los campos */
        label {
            display: block;
            text-align: left;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="login-container">

    <a href="dashboard.php" class="btn-regresar">⬅ Regresar al Dashboard</a>

    <h1>Buscar Usuario</h1>

    <?php if ($mensaje): ?>
    <p style="color: <?php echo $tipoMensaje == 'success' ? 'green' : 'red'; ?>;">
        <?php echo $mensaje; ?>
    </p>
    <?php endif; ?>

    <form method="POST">
        <!-- Mantener el valor buscado en el input -->
        <label for="curp_buscar">Ingrese la CURP del usuario:</label>
        <input type="text" id="curp_buscar" name="curp_buscar" placeholder="Ej. ABCD123456EFGHIJ78" required maxlength="18" value="<?php echo htmlspecialchars($_POST['curp_buscar'] ?? ''); ?>">
        <button type="submit" name="buscar">Buscar</button>
    </form>

    <br>

    <?php if ($usuario): ?>
        <hr>
        <h2>Modificar Datos</h2>
        <form method="POST">
            
            <label for="curp">CURP (No modificable):</label>
            <input type="text" id="curp" name="curp" value="<?php echo htmlspecialchars($usuario['curp'] ?? ''); ?>" readonly style="background-color: #e9ecef;">
            
            <label for="rfc">RFC:</label>
            <input type="text" id="rfc" name="rfc" value="<?php echo htmlspecialchars($usuario['rfc'] ?? ''); ?>" required maxlength="13">

            <label for="apellido_p">Apellido Paterno:</label>
            <input type="text" id="apellido_p" name="apellido_p" value="<?php echo htmlspecialchars($usuario['apellido_p'] ?? ''); ?>" required>
            
            <label for="apellido_m">Apellido Materno:</label>
            <input type="text" id="apellido_m" name="apellido_m" value="<?php echo htmlspecialchars($usuario['apellido_m'] ?? ''); ?>" required>
            
            <label for="nombres">Nombres:</label>
            <input type="text" id="nombres" name="nombres" value="<?php echo htmlspecialchars($usuario['nombres'] ?? ''); ?>" required>

            <label for="sexo">Sexo:</label>
            <select id="sexo" name="sexo" required>
                <option value="Masculino" <?php echo (($usuario['sexo'] ?? '') == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                <option value="Femenino" <?php echo (($usuario['sexo'] ?? '') == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                <option value="Otro" <?php echo (($usuario['sexo'] ?? '') == 'Otro') ? 'selected' : ''; ?>>Otro</option>
            </select>

            <label for="nacimiento">Fecha de Nacimiento:</label>
            <input type="date" id="nacimiento" name="nacimiento" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($usuario['nacimiento'] ?? ''); ?>" required>

            <label for="calle">Calle:</label>
            <input type="text" id="calle" name="calle" value="<?php echo htmlspecialchars($usuario['calle'] ?? ''); ?>" required>
            
            <label for="num_ext">Número Exterior:</label>
            <input type="text" id="num_ext" name="num_ext" value="<?php echo htmlspecialchars($usuario['num_ext'] ?? ''); ?>" required>
            
            <label for="num_int">Número Interior (Opcional):</label>
            <input type="text" id="num_int" name="num_int" value="<?php echo htmlspecialchars($usuario['num_int'] ?? ''); ?>">
            
            <label for="cp">Código Postal:</label>
            <input type="text" id="cp" name="cp" value="<?php echo htmlspecialchars($usuario['cp'] ?? ''); ?>" required>
            
            <label for="municipio">Municipio:</label>
            <input type="text" id="municipio" name="municipio" value="<?php echo htmlspecialchars($usuario['municipio'] ?? ''); ?>" required>

            <label for="estado_dir">Estado:</label>
            <select id="estado_dir" name="estado_dir" required>
                <?php 
                $estados = ["Aguascalientes", "Baja California", "Baja California Sur", "Campeche", "Chiapas", "Chihuahua", "Ciudad de México", "Coahuila", "Colima", "Durango", "Estado de México", "Guanajuato", "Guerrero", "Hidalgo", "Jalisco", "Michoacán", "Morelos", "Nayarit", "Nuevo León", "Oaxaca", "Puebla", "Querétaro", "Quintana Roo", "San Luis Potosí", "Sinaloa", "Sonora", "Tabasco", "Tamaulipas", "Tlaxcala", "Veracruz", "Yucatán", "Zacatecas"];
                foreach ($estados as $est) {
                    $selected = (($usuario['estado_dir'] ?? '') == $est) ? 'selected' : '';
                    echo "<option value=\"$est\" $selected>$est</option>";
                }
                ?>
            </select>

            <label for="tipo_personal">Tipo de Personal:</label>
            <select id="tipo_personal" name="tipo_personal" required>
                <option value="Empleado" <?php echo (($usuario['tipo_personal'] ?? '') == 'Empleado') ? 'selected' : ''; ?>>Empleado</option>
                <option value="Voluntario" <?php echo (($usuario['tipo_personal'] ?? '') == 'Voluntario') ? 'selected' : ''; ?>>Voluntario</option>
            </select>

            <label for="rol">Rol (Opcional):</label>
            <select id="rol" name="rol">
                <option value="" hidden>Seleccione un rol</option>
                <!-- CORRECCIÓN: Los roles ahora reflejan el ENUM de la base de datos -->
                <?php 
                $roles = ["Administrador", "Psicologo", "Medico", "Abogado", "Trabajador_Social"];
                foreach ($roles as $r) {
                    $selected = (($usuario['rol'] ?? '') == $r) ? 'selected' : '';
                    $label_rol = str_replace('_', ' ', $r); // Para que se vea bonito en pantalla
                    echo "<option value=\"$r\" $selected>$label_rol</option>";
                }
                ?>
            </select>

            <label for="correo">Correo Electrónico:</label>
            <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>" required>

            <button type="submit" name="actualizar" style="margin-top: 20px;">Guardar Cambios</button>

        </form>
    <?php endif; ?>

</div>

</body>
</html>