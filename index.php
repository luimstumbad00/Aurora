<?php
session_start();
require 'config/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $correo = $_POST['correo'];
    $password = $_POST['password'];

    $query = "SELECT * FROM usuario WHERE correo = $1 AND contrasena = $2";
    $result = pg_query_params($conn, $query, array($correo, $password));

    if (pg_num_rows($result) == 1) {

        $usuario = pg_fetch_assoc($result);

        $_SESSION['usuario'] = $usuario;

        header("Location: bloques/dashboard.php");
        exit();

    } else {
        $error = "Correo o contraseña incorrectos";
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