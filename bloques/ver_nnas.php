<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$rolActual = $_SESSION['usuario']['rol'];
if ($rolActual !== 'Director' && $rolActual !== 'Coordinador') {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

require '../config/database.php';

$busqueda = isset($_GET['buscar']) ? pg_escape_string($conn, strtoupper($_GET['buscar'])) : '';

// 1. CONSULTA CON JOIN
$query = "SELECT n.*, 
          t.nombre as tutor_nombre, t.apellido_paterno as tutor_ap, t.telefono as tutor_tel
          FROM nna n
          LEFT JOIN nna_tutor nt ON n.curp = nt.curp_nna
          LEFT JOIN tutor t ON nt.curp_tutor = t.curp";

if (!empty($busqueda)) {
    $query .= " WHERE n.curp LIKE '%$busqueda%' OR n.nombre LIKE '%$busqueda%' OR n.apellido_paterno LIKE '%$busqueda%'";
}

$query .= " ORDER BY n.curp DESC"; 
$result = pg_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de NNA's Registrados</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container-tabla { max-width: 1300px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-family: Arial, sans-serif; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; font-size: 13px; }
        th { background-color: #34495e; color: white; text-transform: uppercase; }
        tr:hover { background-color: #f5f5f5; }
        .badge { display: block; margin-bottom: 5px; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; color: white; text-align: center; }
        .bg-si { background-color: #27ae60; }
        .bg-no { background-color: #95a5a6; }
        .tutor-info { font-size: 11px; color: #2c3e50; background: #f9f9f9; padding: 5px; border-radius: 4px; border: 1px solid #eee; }
        .btn-regresar { display: inline-block; margin-bottom: 20px; padding: 10px 15px; background-color: #34495e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        
        /* Estilos para los botones de acción */
        .btn-accion { 
            display: block; 
            text-align: center; 
            text-decoration: none; 
            color: white; 
            padding: 6px 10px; 
            border-radius: 4px; 
            font-size: 11px; 
            margin-bottom: 5px; 
            font-weight: bold;
        }
        .btn-asignar { background-color: #2980b9; }
        .btn-editar { background-color: #f39c12; }
        
        .search-container { margin-bottom: 20px; display: flex; gap: 10px; }
        .search-container input { flex-grow: 1; padding: 8px; text-transform: uppercase; }
        
        .alerta-exito {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>

<div class="container-tabla">
    <a href="dashboard.php" class="btn-regresar">⬅ Regresar al Dashboard</a>
    <h1>NNA's Registrados</h1>

    <!-- Aviso de eliminación exitosa -->
    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'eliminado_exito'): ?>
        <div class="alerta-exito">✅ El registro del NNA ha sido eliminado correctamente.</div>
    <?php endif; ?>

    <form method="GET" class="search-container">
        <input type="text" name="buscar" placeholder="Buscar por CURP o Nombre..." value="<?= htmlspecialchars($busqueda) ?>">
        <button type="submit" style="width: auto; padding: 0 20px;">Buscar</button>
        <?php if(!empty($busqueda)): ?><a href="ver_nnas.php" style="color: red; text-decoration: none; align-self: center;">Limpiar</a><?php endif; ?>
    </form>

    <table>
        <thead>
            <tr>
                <th>CURP</th>
                <th>Nombre NNA</th>
                <th>F. Nacimiento</th>
                <th>Dirección</th>
                <th>Condiciones</th>
                <th>Tutor Asignado</th>
                <th style="width: 120px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (pg_num_rows($result) > 0): ?>
                <?php while ($row = pg_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['curp']) ?></strong></td>
                        <td><?= htmlspecialchars($row['nombre'] . " " . $row['apellido_paterno']) ?></td>
                        <td><?= htmlspecialchars($row['fecha_nacimiento']) ?></td>
                        <td><?= htmlspecialchars($row['calle'] . " #" . $row['num_ext']) ?></td>
                        <td>
                            <span class="badge <?= $row['situacion_calle'] == 't' ? 'bg-si' : 'bg-no' ?>">Calle: <?= $row['situacion_calle'] == 't' ? 'SÍ' : 'NO' ?></span>
                            <span class="badge <?= $row['es_migrante'] == 't' ? 'bg-si' : 'bg-no' ?>">Migrante: <?= $row['es_migrante'] == 't' ? 'SÍ' : 'NO' ?></span>
                        </td>
                        <td>
                            <?php if ($row['tutor_nombre']): ?>
                                <div class="tutor-info">
                                    <strong><?= htmlspecialchars($row['tutor_nombre'] . " " . $row['tutor_ap']) ?></strong><br>
                                    📞 <?= htmlspecialchars($row['tutor_tel'] ?? 'S/T') ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #e74c3c; font-style: italic;">Sin tutor</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Botón VER PERFIL COMPLETO (Los "datos normales") --><a href="ver_perfil_nna.php?curp=<?= urlencode($row['curp']) ?>" class="btn-accion btn-asignar" style="background-color: #3498db;">👁️ Ver Perfil</a>
                            <!-- Botón EDITAR --><a href="editar_nna.php?curp=<?= urlencode($row['curp']) ?>" class="btn-accion btn-editar">✏️ Editar NNA</a>
                            <!-- Botón SALUD (Abre el historial médico) --><a href="ver_salud_nna.php?curp=<?= urlencode($row['curp']) ?>" class="btn-accion" style="background-color: #9b59b6; color: white;">🏥 Salud/Enf.</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center;">No se encontraron registros.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>