<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../config/database.php';

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $curp = $_POST['curp'];
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

    $contrasena = $curp;

    $query = "
        INSERT INTO usuario (
            curp, rfc, nombre, direccion,
            sexo, nacimiento, tipo_personal,
            rol, estado, correo, contrasena
        ) VALUES (
            $1, $2,
            ROW($3,$4,$5),
            ROW($6,$7,$8,$9,$10,$11),
            $12, $13, $14,
            $15, 'Activo', $16, $17
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
        $mensaje = "Usuario creado correctamente ✅";
        $tipoMensaje = "success";
    } else {
        $error = pg_last_error($conn);

        if (strpos($error, 'usuario_pkey') !== false) {
            $mensaje = "La CURP ya está registrada ⚠️";
        } elseif (strpos($error, 'usuario_correo_key') !== false) {
            $mensaje = "El correo ya está registrado ⚠️";
        } else {
            $mensaje = "Error al crear usuario ❌";
        }

        $tipoMensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Registrar Usuario</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="login-container">

<h1>Registrar Usuario</h1>

<?php if ($mensaje): ?>
<p style="color: <?php echo $tipoMensaje == 'success' ? 'green' : 'red'; ?>;">
    <?php echo $mensaje; ?>
</p>
<?php endif; ?>

<form method="POST">

<input type="text" name="curp" placeholder="CURP" required maxlength="18">
<input type="text" name="rfc" placeholder="RFC" required maxlength="13">

<input type="text" name="apellido_p" placeholder="Apellido Paterno" required>
<input type="text" name="apellido_m" placeholder="Apellido Materno" required>
<input type="text" name="nombres" placeholder="Nombres" required>

<select name="sexo" required>
<option value=""disabled selected hidden>Sexo</option>
<option value="Masculino">Masculino</option>
<option value="Femenino">Femenino</option>
<option value="Otro">Otro</option>
</select>

<input type="date" name="nacimiento" max="<?php echo date('Y-m-d'); ?>" required>

<input type="text" name="calle" placeholder="Calle" required>
<input type="text" name="num_ext" placeholder="Número Exterior" required>
<input type="text" name="num_int" placeholder="Número Interior (Opcional)">
<input type="text" name="cp" placeholder="Código Postal" required>
<input type="text" name="municipio" placeholder="Municipio" required>

<select name="estado_dir" required>
<option value=""disabled selected hidden>Estado</option>
<option>Aguascalientes</option>
<option>Baja California</option>
<option>Baja California Sur</option>
<option>Campeche</option>
<option>Chiapas</option>
<option>Chihuahua</option>
<option>Ciudad de México</option>
<option>Coahuila</option>
<option>Colima</option>
<option>Durango</option>
<option>Estado de México</option>
<option>Guanajuato</option>
<option>Guerrero</option>
<option>Hidalgo</option>
<option>Jalisco</option>
<option>Michoacán</option>
<option>Morelos</option>
<option>Nayarit</option>
<option>Nuevo León</option>
<option>Oaxaca</option>
<option>Puebla</option>
<option>Querétaro</option>
<option>Quintana Roo</option>
<option>San Luis Potosí</option>
<option>Sinaloa</option>
<option>Sonora</option>
<option>Tabasco</option>
<option>Tamaulipas</option>
<option>Tlaxcala</option>
<option>Veracruz</option>
<option>Yucatán</option>
<option>Zacatecas</option>
</select>

<select name="tipo_personal" required>
<option value=""disabled selected hidden>Tipo de Personal</option>
<option value="Empleado">Empleado</option>
<option value="Voluntario">Voluntario</option>
</select>

<select name="rol">
<option value=""disabled selected hidden>Rol (Opcional)</option>
<option value="Director">Director</option>
<option value="Coordinador">Coordinador</option>
<option value="Psicologo">Psicologo</option>
<option value="Doctor">Doctor</option>
<option value="Abogado">Abogado</option>
<option value="Trabajador Social">Trabajador Social</option>
<option value="Analista">Analista</option>
</select>

<input type="email" name="correo" placeholder="Correo" required>

<button type="submit">Crear Usuario</button>

</form>

</div>

</body>
</html>