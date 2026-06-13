<?php
session_start();
require 'config/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        // Traemos al usuario por correo y, de paso, el nombre del rol desde el catálogo
        $sql = "SELECT u.id_usuario,
                       u.curp,
                       u.nombre,
                       u.apellido_paterno,
                       u.apellido_materno,
                       u.correo,
                       u.contrasena,
                       u.id_rol,
                       u.estado,
                       r.nombre AS rol
                FROM   usuario_sistema u
                INNER JOIN cat_rol_sistema r ON r.id = u.id_rol
                WHERE  u.correo = :correo
                LIMIT  1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // La contraseña se valida contra el la clave almacenada en la BD
        if ($usuario && $password === $usuario['contrasena']) {

            // Solo cuentas ACTIVO pueden iniciar sesión (estado: ACTIVO | INACTIVO | SUSPENDIDO)
            if ($usuario['estado'] !== 'ACTIVO') {
                $error = "Tu cuenta está " . strtolower($usuario['estado']) . ". Contacta al administrador.";
            } else {
                // Nunca dejamos el hash de la contraseña en la sesión
                unset($usuario['contrasena']);
                $_SESSION['usuario'] = $usuario;

                header("Location: bloques/dashboard.php");
                exit();
            }
        } else {
            $error = "Correo o contraseña incorrectos";
        }
    } catch (PDOException $e) {
        // En producción: registrar el detalle con error_log($e->getMessage());
        $error = "Ocurrió un error al iniciar sesión. Intenta de nuevo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login - Aurora</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-container">
<h1>Iniciar Sesión</h1>
<?php if ($error != ""): ?>
<p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>
<form method="POST">
<input type="email" name="correo" placeholder="Correo electrónico" required>
<input type="password" name="password" placeholder="Contraseña" required>
<button type="submit">Entrar</button>
</form>
</div>
</body>
</html>