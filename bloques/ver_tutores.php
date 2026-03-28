<?php
session_start();

// 1. Validar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/database.php';

// Manejo de búsqueda por nombre de tutor o CURP
$busqueda = isset($_GET['buscar']) ? pg_escape_string($conn, strtoupper($_GET['buscar'])) : '';

// 2. CORRECCIÓN: Consulta con JOIN a la superentidad 'persona' para obtener los nombres
$query = "SELECT 
            t.curp AS tutor_curp, 
            pt.nombre AS tutor_nom, 
            pt.apellido_paterno AS tutor_ap, 
            pt.apellido_materno AS tutor_am,
            t.telefono, 
            t.correo, 
            t.es_adulto_mayor,
            pn.nombre AS nna_nom, 
            pn.apellido_paterno AS nna_ap,
            nt.relacion
          FROM tutor t
          JOIN persona pt ON t.curp = pt.curp 
          LEFT JOIN nna_tutor nt ON t.curp = nt.curp_tutor
          LEFT JOIN nna n ON nt.curp_nna = n.curp
          LEFT JOIN persona pn ON n.curp = pn.curp";

if (!empty($busqueda)) {
    // Ajuste de búsqueda para apuntar a la tabla persona (pt)
    $query .= " WHERE t.curp LIKE '%$busqueda%' OR pt.nombre LIKE '%$busqueda%' OR pt.apellido_paterno LIKE '%$busqueda%'";
}

// Ajuste del ORDER BY para apuntar a la tabla persona (pt)
$query .= " ORDER BY pt.apellido_paterno ASC";
$result = pg_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Directorio de Tutores</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container-tabla {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 14px;
        }
        th {
            background-color: #2c3e50;
            color: white;
            text-transform: uppercase;
        }
        .nna-asignado {
            background-color: #f1f9ff;
            padding: 5px 10px;
            border-radius: 4px;
            border-left: 3px solid #3498db;
            font-size: 13px;
        }
        .adulto-mayor {
            color: #e67e22;
            font-weight: bold;
            font-size: 11px;
        }
        .btn-regresar {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: #34495e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<div class="container-tabla">
    <a href="dashboard.php" class="btn-regresar">⬅ Dashboard</a>
    
    <h1>Directorio de Tutores</h1>

    <form method="GET" style="display:flex; gap:10px; margin-bottom:20px;">
        <input type="text" name="buscar" placeholder="Buscar tutor por nombre o CURP..." 
               value="<?= htmlspecialchars($busqueda) ?>" style="flex-grow:1; padding:8px; text-transform:uppercase;">
        <button type="submit">Buscar</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>CURP Tutor</th>
                <th>Nombre del Tutor</th>
                <th>Contacto</th>
                <th>¿Es Tutor de quién?</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (pg_num_rows($result) > 0): ?>
                <?php while ($row = pg_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['tutor_curp']) ?></strong></td>
                        <td>
                            <?= htmlspecialchars($row['tutor_nom'] . " " . $row['tutor_ap'] . " " . $row['tutor_am']) ?>
                            <?= ($row['es_adulto_mayor'] == 't') ? '<br><span class="adulto-mayor">👴 ADULTO MAYOR</span>' : '' ?>
                        </td>
                        <td>
                            📞 <?= htmlspecialchars($row['telefono'] ?? 'N/A') ?><br>
                            ✉️ <small><?= htmlspecialchars($row['correo'] ?? 'N/A') ?></small>
                        </td>
                        <td>
                            <?php if ($row['nna_nom']): ?>
                                <div class="nna-asignado">
                                    <strong>NNA:</strong> <?= htmlspecialchars($row['nna_nom'] . " " . $row['nna_ap']) ?><br>
                                    <small>Parentesco: <?= htmlspecialchars($row['relacion'] ?? 'Asignado') ?></small>
                                </div>
                            <?php else: ?>
                                <span style="color: gray; font-style: italic;">Sin NNA vinculado</span>
                            <?php endif; ?>
                        </td>
                        <!-- CORRECCIÓN: Etiquetas td y a cerradas correctamente -->
                        <td>
                            <a href="editar_tutor.php?curp=<?= urlencode($row['tutor_curp']) ?>" style="background-color: #f39c12; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 11px;">✏️ Editar</a>
                            
                            <a href="registrar_enfermedad.php?curp_tutor=<?= urlencode($row['tutor_curp']) ?>" style="background-color: #9b59b6; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 11px; display: block; text-align: center; margin-top:5px;">🏥 Salud Tutor</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center;">No se encontraron tutores registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>