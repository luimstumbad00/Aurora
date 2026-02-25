<?php
// 1. Unirse a la sesión actual para poder destruirla
session_start();

// 2. Vaciar completamente el arreglo de la sesión
$_SESSION = array();

// 3. Destruir la cookie de la sesión en el navegador del usuario
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finalmente, destruir la sesión en el servidor
session_destroy();

// 5. Redirigir a la pantalla de inicio o login
header("Location: /Aurora");
exit();
?>