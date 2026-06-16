<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require("../config/database.php");

// Los datos ya vienen de la sesión, no necesitamos otra consulta
$nombreCompleto = trim(
    $_SESSION['usuario']['nombre'] . " " .
    $_SESSION['usuario']['apellido_paterno'] . " " .
    ($_SESSION['usuario']['apellido_materno'] ?? '')
);
$rol = $_SESSION['usuario']['rol'];

date_default_timezone_set('America/Mexico_City');
$hora = date("H");

if ($hora >= 6 && $hora < 12) {
    $saludo = "Buenos días";
} elseif ($hora >= 12 && $hora < 19) {
    $saludo = "Buenas tardes";
} else {
    $saludo = "Buenas noches";
}

// "Mis casos": NNA donde el usuario logueado ha registrado algún seguimiento
$mis_casos = [];
try {
    $id_usuario = $_SESSION['usuario']['id_usuario'] ?? null;
    if ($id_usuario) {
        $stmt = $pdo->prepare("
            SELECT 
                n.curp,
                n.nombre,
                n.prim_ap,
                COUNT(es.id_seguimiento) AS total_notas,
                MAX(es.fecha_atencion)   AS ultima_atencion
            FROM expediente_seguimiento es
            JOIN nna n ON n.id_nna = es.id_nna
            WHERE es.id_usuario = :id_usuario
            GROUP BY n.id_nna, n.curp, n.nombre, n.prim_ap
            ORDER BY ultima_atencion DESC
        ");
        $stmt->execute([':id_usuario' => $id_usuario]);
        $mis_casos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // En producción: error_log($e->getMessage());
    $mis_casos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Principal - Aurora</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body {
            background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
            min-height: 100vh;
            color: #333;
            padding: 40px 20px;
        }
        .dashboard-container { max-width: 1000px; margin: 0 auto; animation: fadeIn 0.6s ease-in-out; }
        .header-card {
            background-color: rgba(255,255,255,0.95);
            padding: 30px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .header-info h2 { font-size: 28px; font-weight: 600; color: #2c3e50; margin-bottom: 5px; }
        .header-info p { font-size: 15px; color: #666; margin-top: 5px; }
        .badge-rol {
            background-color: #a18cd1;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
            margin-top: 5px;
        }
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .nav-item {
            background-color: #ffffff;
            padding: 25px 20px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.3s, box-shadow 0.3s, color 0.3s;
        }
        .nav-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(161,140,209,0.3);
            color: #a18cd1;
        }
        .nav-item.logout { color: #e74c3c; }
        .nav-item.logout:hover { color: white; background-color: #e74c3c; box-shadow: 0 15px 35px rgba(231,76,60,0.3); }
        .content-card {
            background-color: rgba(255,255,255,0.95);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .content-card h3 { color: #2c3e50; margin-bottom: 10px; }
        /* Estilos para Mis Casos */
        .casos-card {
            background-color: rgba(255,255,255,0.95);
            padding: 30px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .casos-card h3 { color: #2c3e50; margin-bottom: 15px; }
        .caso-row {
            border-bottom: 1px solid #eee;
            padding: 12px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .caso-row:last-child { border-bottom: none; }
        .caso-info strong { color: #2c3e50; }
        .caso-info small { color: #999; }
        .btn-ver-caso {
            background-color: #a18cd1;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-ver-caso:hover { background-color: #8c76be; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="dashboard-container">

    <div class="header-card">
        <div class="header-info">
            <h2><?php echo $saludo . ', ' . explode(' ', $nombreCompleto)[0]; ?></h2>
            <p><strong>Usuario:</strong> <?php echo $nombreCompleto; ?></p>
            <div class="badge-rol"><?php echo $rol; ?></div>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'acceso_denegado'): ?>
            <div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border-radius:8px;font-weight:500;font-size:14px;">
                ⚠️ Acceso denegado a esa sección.
            </div>
        <?php endif; ?>
    </div>

    <div class="nav-grid">
        <a href="mi_cuenta.php"    class="nav-item" style="color:#f39c12;">⚙️ Mi Cuenta</a>
        <a href="ver_usuarios.php" class="nav-item">👥 Ver Usuarios</a>
        <a href="ver_nnas.php"     class="nav-item">👶 Ver NNA's</a>

        <?php if ($rol === 'Administrador'): ?>
            <a href="agusuario.php"        class="nav-item">➕ Agregar Usuario</a>
            <a href="modificar_usuario.php" class="nav-item">✏️ Editar Usuario</a>
            <a href="agregar_nna.php"      class="nav-item">➕ Agregar NNA</a>
            <a href="ver_tutores.php"      class="nav-item">👨‍👩‍👧 Ver Tutores</a>
            <a href="asignar_tutor.php"    class="nav-item">🔗 Agregar Tutor</a>
            <a href="ver_equipos.php" class="nav-item">🏥 Equipos</a>
        <?php endif; ?>

        <a href="logout.php" class="nav-item logout">🚪 Cerrar Sesión</a>
    </div>

    <!-- ===== MIS CASOS (Seguimiento multidisciplinario) ===== -->
    <div class="casos-card">
        <h3>📂 Mis Casos</h3>
        <?php if (count($mis_casos) > 0): ?>
            <?php foreach ($mis_casos as $c): ?>
                <div class="caso-row">
                    <div class="caso-info">
                        <strong><?= htmlspecialchars($c['nombre'] . " " . $c['prim_ap']) ?></strong><br>
                        <small><?= (int) $c['total_notas'] ?> seguimiento(s) · Última atención: <?= htmlspecialchars($c['ultima_atencion']) ?></small>
                    </div>
                    <a href="ver_seguimientos.php?curp=<?= urlencode($c['curp']) ?>" class="btn-ver-caso">Ver caso</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:#666;">Aún no tienes casos con seguimientos registrados. Entra al perfil de un NNA y registra un seguimiento para que aparezca aquí.</p>
        <?php endif; ?>
    </div>

    <div class="content-card">
        <h3>Panel de Control</h3>
        <p style="color:#666;">Bienvenido al sistema Aurora. Desde aquí puedes gestionar las operaciones utilizando el menú superior.</p>
    </div>

</div>
</body>
</html>