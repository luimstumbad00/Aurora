<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../config/database.php';

$mensaje = "";
$tipoMensaje = "";
$usuario = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // FASE 1: BUSCAR USUARIO
    if (isset($_POST['buscar'])) {
        $curp_buscar = $_POST['curp_buscar'];
        
        // Se usan alias para extraer correctamente los datos de los tipos compuestos (nombre y direccion)
        $query_buscar = "
            SELECT 
                curp, rfc, 
                (nombre).apellido_p AS apellido_p, 
                (nombre).apellido_m AS apellido_m, 
                (nombre).nombres AS nombres, 
                (direccion).calle AS calle, 
                (direccion).num_ext AS num_ext, 
                (direccion).num_int AS num_int, 
                (direccion).cp AS cp, 
                (direccion).municipio AS municipio, 
                (direccion).estado AS estado_dir, 
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
        $curp = $_POST['curp']; // Se mantiene como llave primaria para el WHERE
        $rfc = $_POST['rfc'];
        $apellido_p = $_POST['apellido_p'];
        $apellido_m = $_POST['apellido_m'];
        $nombres = $_POST['nombres'];

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

        // Se actualizan los datos estructurando de nuevo los tipos compuestos con ROW()
        $query_actualizar = "
            UPDATE usuario SET 
                rfc = $2, 
                nombre = ROW($3, $4, $5), 
                direccion = ROW($6, $7, $8, $9, $10, $11), 
                sexo = $12, 
                nacimiento = $13, 
                tipo_personal = $14, 
                rol = $15, 
                correo = $16
            WHERE curp = $1
        ";

        $result_actualizar = @pg_query_params($conn, $query_actualizar, array(
            $curp, $rfc,
            $apellido_p, $apellido_m, $nombres,
            $calle, $num_ext, $num_int, $cp, $municipio, $estado_dir,
            $sexo, $nacimiento, $tipo_personal,
            $rol, $correo
        ));

        if ($result_actualizar) {
            $mensaje = "Datos del usuario actualizados correctamente ✅";
            $tipoMensaje = "success";
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
<html>
<head>
    <meta charset="UTF-8">
    <title>Buscar y Modificar Usuario</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="login-container">

    <h1>Buscar Usuario</h1>

    <?php if ($mensaje): ?>
    <p style="color: <?php echo $tipoMensaje == 'success' ? 'green' : 'red'; ?>;">
        <?php echo $mensaje; ?>
    </p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="curp_buscar" placeholder="Ingrese CURP a buscar" required maxlength="18">
        <button type="submit" name="buscar">Buscar</button>
    </form>

    <br>

    <?php if ($usuario): ?>
        <hr>
        <h2>Modificar Datos</h2>
        <form method="POST">
            
            <input type="text" name="curp" value="<?php echo htmlspecialchars($usuario['curp']); ?>" readonly style="background-color: #e9ecef;">
            <input type="text" name="rfc" value="<?php echo htmlspecialchars($usuario['rfc']); ?>" required maxlength="13">

            <input type="text" name="apellido_p" value="<?php echo htmlspecialchars($usuario['apellido_p']); ?>" required>
            <input type="text" name="apellido_m" value="<?php echo htmlspecialchars($usuario['apellido_m']); ?>" required>
            <input type="text" name="nombres" value="<?php echo htmlspecialchars($usuario['nombres']); ?>" required>

            <select name="sexo" required>
                <option value="Masculino" <?php echo ($usuario['sexo'] == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                <option value="Femenino" <?php echo ($usuario['sexo'] == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                <option value="Otro" <?php echo ($usuario['sexo'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
            </select>

            <input type="date" name="nacimiento" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($usuario['nacimiento']); ?>" required>

            <input type="text" name="calle" value="<?php echo htmlspecialchars($usuario['calle']); ?>" required>
            <input type="text" name="num_ext" value="<?php echo htmlspecialchars($usuario['num_ext']); ?>" required>
            <input type="text" name="num_int" value="<?php echo htmlspecialchars($usuario['num_int']); ?>">
            <input type="text" name="cp" value="<?php echo htmlspecialchars($usuario['cp']); ?>" required>
            <input type="text" name="municipio" value="<?php echo htmlspecialchars($usuario['municipio']); ?>" required>

            <select name="estado_dir" required>
                <?php 
                $estados = ["Aguascalientes", "Baja California", "Baja California Sur", "Campeche", "Chiapas", "Chihuahua", "Ciudad de México", "Coahuila", "Colima", "Durango", "Estado de México", "Guanajuato", "Guerrero", "Hidalgo", "Jalisco", "Michoacán", "Morelos", "Nayarit", "Nuevo León", "Oaxaca", "Puebla", "Querétaro", "Quintana Roo", "San Luis Potosí", "Sinaloa", "Sonora", "Tabasco", "Tamaulipas", "Tlaxcala", "Veracruz", "Yucatán", "Zacatecas"];
                foreach ($estados as $est) {
                    $selected = ($usuario['estado_dir'] == $est) ? 'selected' : '';
                    echo "<option value=\"$est\" $selected>$est</option>";
                }
                ?>
            </select>

            <select name="tipo_personal" required>
                <option value="Empleado" <?php echo ($usuario['tipo_personal'] == 'Empleado') ? 'selected' : ''; ?>>Empleado</option>
                <option value="Voluntario" <?php echo ($usuario['tipo_personal'] == 'Voluntario') ? 'selected' : ''; ?>>Voluntario</option>
            </select>

            <select name="rol">
                <option value="" hidden>Rol (Opcional)</option>
                <?php 
                $roles = ["Director", "Coordinador", "Psicologo", "Doctor", "Abogado", "Trabajador Social", "Analista"];
                foreach ($roles as $r) {
                    $selected = ($usuario['rol'] == $r) ? 'selected' : '';
                    echo "<option value=\"$r\" $selected>$r</option>";
                }
                ?>
            </select>

            <input type="email" name="correo" value="<?php echo htmlspecialchars($usuario['correo']); ?>" required>

            <button type="submit" name="actualizar">Guardar Cambios</button>

        </form>
    <?php endif; ?>

</div>

</body>
</html>