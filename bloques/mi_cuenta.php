<?php
session_start();

// 1. Validar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php"); 
    exit();
}

require("../config/database.php");
$curp = $_SESSION['usuario']['curp'];

$mensaje = "";
$tipoMensaje = "";

// Patrón PRG para atrapar mensajes
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $mensaje = "Contraseña actualizada correctamente ✅";
        $tipoMensaje = "success";
    } elseif ($_GET['status'] == 'error_actual') {
        $mensaje = "La contraseña actual es incorrecta ❌";
        $tipoMensaje = "error";
    } elseif ($_GET['status'] == 'error_coincidencia') {
        $mensaje = "Las contraseñas nuevas no coinciden ⚠️";
        $tipoMensaje = "error";
    } elseif ($_GET['status'] == 'error_db') {
        $mensaje = "Error al actualizar la base de datos ❌";
        $tipoMensaje = "error";
    }
}

// Procesar el cambio de contraseña
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cambiar_pass'])) {
    $pass_actual = $_POST['pass_actual'];
    $pass_nueva = $_POST['pass_nueva'];
    $pass_confirma = $_POST['pass_confirma'];

    // Validar que las nuevas coincidan
    if ($pass_nueva !== $pass_confirma) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=error_coincidencia");
        exit();
    }

    // Buscar la contraseña actual en la BD
    $query_verificar = "SELECT contrasena FROM usuario WHERE curp = $1";
    $result_verificar = pg_query_params($conn, $query_verificar, array($curp));
    $row = pg_fetch_assoc($result_verificar);

    // Comparar la contraseña actual escrita con la de la BD
    if ($row['contrasena'] === $pass_actual) {
        // Si es correcta, actualizamos
        $query_update = "UPDATE usuario SET contrasena = $1 WHERE curp = $2";
        $result_update = pg_query_params($conn, $query_update, array($pass_nueva, $curp));

        if ($result_update) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit();
        } else {
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=error_db");
            exit();
        }
    } else {
        // La contraseña actual no cuadra
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=error_actual");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Cuenta - Aurora</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
            min-height: 100vh;
            color: #333;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .dashboard-container {
            width: 100%;
            max-width: 500px;
            animation: fadeIn 0.6s ease-in-out;
        }

        .content-card {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .content-card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: -10px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input:focus {
            border-color: #a18cd1;
            box-shadow: 0 0 0 3px rgba(161, 140, 209, 0.2);
        }

        button.btn-guardar {
            background-color: #a18cd1;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            margin-top: 10px;
        }

        button.btn-guardar:hover { background-color: #8c76be; }
        button.btn-guardar:active { transform: scale(0.98); }

        .btn-regresar {
            display: inline-block;
            margin-bottom: 20px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            background: rgba(0,0,0,0.2);
            padding: 8px 15px;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .btn-regresar:hover { background: rgba(0,0,0,0.4); }

        /* Botón de Logout */
        .btn-logout {
            display: block;
            text-align: center;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 30px;
            transition: background-color 0.3s, transform 0.1s;
        }
        .btn-logout:hover { background-color: #c0392b; }

        .mensaje {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
            font-size: 14px;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <a href="dashboard.php" class="btn-regresar">⬅ Regresar al Dashboard</a>

    <div class="content-card">
        <h2>⚙️ Mi Cuenta</h2>
        
        <?php if ($mensaje): ?> 
            <div class="mensaje <?= $tipoMensaje == 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($mensaje) ?> 
            </div>
        <?php endif; ?>

        <form method="POST">
            <label for="pass_actual">Contraseña Actual:</label>
            <input type="password" id="pass_actual" name="pass_actual" placeholder="Ingresa tu contraseña actual" required>

            <label for="pass_nueva">Nueva Contraseña:</label>
            <input type="password" id="pass_nueva" name="pass_nueva" placeholder="Mínimo 6 caracteres" required minlength="6">

            <label for="pass_confirma">Confirmar Nueva Contraseña:</label>
            <input type="password" id="pass_confirma" name="pass_confirma" placeholder="Repite la nueva contraseña" required minlength="6">

            <button type="submit" name="cambiar_pass" class="btn-guardar">Actualizar Contraseña</button>
        </form>

        <hr style="border: none; border-top: 1px solid #eee; margin-top: 30px;">

        <a href="logout.php" class="btn-logout">🚪 Cerrar Sesión</a>
    </div>

</div>

</body>
</html>