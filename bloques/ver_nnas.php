<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$rolActual = $_SESSION['usuario']['rol'];
if ($rolActual !== 'Administrador') {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

require '../config/database.php';

$busqueda = isset($_GET['buscar']) ? pg_escape_string($conn, strtoupper($_GET['buscar'])) : '';

// CORRECCIÓN: Jalamos la dirección REAL desde la tabla persona (pn)
$query = "SELECT 
            n.curp, 
            pn.nombre, 
            pn.apellido_paterno, 
            pn.fecha_nacimiento,
            n.situacion_calle, 
            n.es_migrante, 
            n.es_refugiado, 
            n.poblacion_indigena,
            pn.calle, 
            pn.numero_exterior AS num_ext,
            pn.municipio,
            pn.estado_dir,
            pt.nombre as tutor_nombre, 
            pt.apellido_paterno as tutor_ap, 
            t.telefono as tutor_tel
          FROM nna n
          JOIN persona pn ON n.curp = pn.curp
          LEFT JOIN nna_tutor nt ON n.curp = nt.curp_nna
          LEFT JOIN tutor t ON nt.curp_tutor = t.curp
          LEFT JOIN persona pt ON t.curp = pt.curp";

if (!empty($busqueda)) {
    $query .= " WHERE n.curp LIKE '%$busqueda%' OR pn.nombre LIKE '%$busqueda%' OR pn.apellido_paterno LIKE '%$busqueda%'";
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
        .container-tabla { max-width: 1400px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-family: Arial, sans-serif; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; font-size: 13px; }
        th { background-color: #34495e; color: white; text-transform: uppercase; }
        tr:hover { background-color: #f5f5f5; }
        .badge { display: block; margin-bottom: 5px; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; color: white; text-align: center; }
        .bg-si { background-color: #27ae60; }
        .bg-no { background-color: #95a5a6; }
        .tutor-info { font-size: 11px; color: #2c3e50; background: #f1f8ff; padding: 5px; border-radius: 4px; border: 1px solid #d1e9ff; }
        .btn-regresar { display: inline-block; margin-bottom: 20px; padding: 10px 15px; background-color: #34495e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        
        .btn-accion { 
            display: block; 
            text-align: center; 
            text-decoration: none; 
            color: white; 
            padding: 7px 10px; 
            border-radius: 4px; 
            font-size: 11px; 
            margin-bottom: 5px; 
            font-weight: bold;
            transition: 0.2s;
        }
        .btn-asignar { background-color: #27ae60; } /* Verde para nueva asignación */
        .btn-cambiar { background-color: #8e44ad; } /* Morado si ya tiene tutor */
        .btn-editar { background-color: #f39c12; }
        .btn-perfil { background-color: #3498db; }
        
        .search-container { margin-bottom: 20px; display: flex; gap: 10px; }
        .search-container input { flex-grow: 1; padding: 8px; text-transform: uppercase; }
        
        .alerta-exito { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

<div class="container-tabla">
    <a href="dashboard.php" class="btn-regresar">⬅ Regresar al Dashboard</a>
    <h1>Directorio de NNA's</h1>

    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'eliminado_exito'): ?>
        <div class="alerta-exito">✅ El registro del NNA ha sido eliminado correctamente.</div>
    <?php endif; ?>

    <form method="GET" class="search-container">
        <input type="text" name="buscar" placeholder="Buscar por CURP o Nombre..." value="<?= htmlspecialchars($busqueda) ?>">
        <button type="submit" style="width: auto; padding: 0 20px;">Buscar</button>
        <?php if(!empty($busqueda)): ?><a href="ver_nnas.php" style="color: red; text-decoration: none; align-self: center; margin-left:10px;">Limpiar</a><?php endif; ?>
    </form>

    <table>
        <thead>
            <tr>
                <th>CURP</th>
                <th>Nombre NNA</th>
                <th>Dirección Registrada</th>
                <th>Vulnerabilidad</th>
                <th>Tutor Responsable</th>
                <th style="width: 150px;">Acciones de Gestión</th>
            </tr>
        </thead>
        <tbody>
            <?php if (pg_num_rows($result) > 0): ?>
                <?php while ($row = pg_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['curp']) ?></strong></td>
                        <td>
                            <?= htmlspecialchars($row['nombre'] . " " . $row['apellido_paterno']) ?><br>
                            <small style="color: #7f8c8d;">Nacimiento: <?= htmlspecialchars($row['fecha_nacimiento']) ?></small>
                        </td>
                        <td>
                            <?php if ($row['calle']): ?>
                                <?= htmlspecialchars($row['calle'] . " #" . $row['num_ext']) ?><br>
                                <small><?= htmlspecialchars($row['municipio'] . ", " . $row['estado_dir']) ?></small>
                            <?php else: ?>
                                <span style="color: #95a5a6; font-style: italic;">Sin dirección</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $row['situacion_calle'] == 't' ? 'bg-si' : 'bg-no' ?>">Calle: <?= $row['situacion_calle'] == 't' ? 'SÍ' : 'NO' ?></span>
                            <span class="badge <?= $row['es_migrante'] == 't' ? 'bg-si' : 'bg-no' ?>">Migrante: <?= $row['es_migrante'] == 't' ? 'SÍ' : 'NO' ?></span>
                        </td>
                        <td>
                            <?php if ($row['tutor_nombre']): ?>
                                <div class="tutor-info">
                                    <strong>👤 <?= htmlspecialchars($row['tutor_nombre'] . " " . $row['tutor_ap']) ?></strong><br>
                                    📞 <?= htmlspecialchars($row['tutor_tel'] ?? 'Sin tel.') ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #e74c3c; font-weight: bold;">⚠️ Requiere Tutor</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['tutor_nombre']): ?>
                                <a href="asignar_tutor.php?curp_nna=<?= urlencode($row['curp']) ?>" class="btn-accion btn-cambiar">🔄 Cambiar Tutor</a>
                            <?php else: ?>
                                <a href="asignar_tutor.php?curp_nna=<?= urlencode($row['curp']) ?>" class="btn-accion btn-asignar">➕ Asignar Tutor</a>
                            <?php endif; ?>

                            <a href="ver_perfil_nna.php?curp=<?= urlencode($row['curp']) ?>" class="btn-accion btn-perfil">👁️ Perfil Completo</a>
                            <a href="editar_nna.php?curp=<?= urlencode($row['curp']) ?>" class="btn-accion btn-editar">✏️ Editar Datos</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center;">No hay NNA's registrados en el sistema.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>