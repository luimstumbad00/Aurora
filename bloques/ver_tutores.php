<?php
session_start();

// 1. Validar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/database.php';

// Búsqueda por nombre de tutor o CURP
$busqueda = isset($_GET['buscar']) ? strtoupper(trim($_GET['buscar'])) : '';

// Modelo normalizado:
//   tutor ya guarda su nombre (sin tabla persona)
//   nna_tutor usa id_tutor/id_nna; el parentesco es id_parentesco -> cat_parentesco
$query = "
    SELECT 
        t.curp_tutor        AS tutor_curp, 
        t.nombre            AS tutor_nom, 
        t.primer_apellido   AS tutor_ap, 
        t.segundo_apellido  AS tutor_am,
        t.telefono, 
        t.correo, 
        t.es_adulto_mayor,
        n.nombre            AS nna_nom, 
        n.prim_ap           AS nna_ap,
        p.nombre            AS relacion
    FROM tutor t
    LEFT JOIN nna_tutor nt       ON nt.id_tutor = t.id_tutor
    LEFT JOIN nna n              ON n.id_nna = nt.id_nna
    LEFT JOIN cat_parentesco p   ON p.id = nt.id_parentesco
";

$params = [];
if (!empty($busqueda)) {
    $query .= " WHERE t.curp_tutor ILIKE :b
                   OR t.nombre ILIKE :b
                   OR t.primer_apellido ILIKE :b ";
    $params[':b'] = '%' . $busqueda . '%';
}

$query .= " ORDER BY t.primer_apellido ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En producción: error_log($e->getMessage());
    $filas = [];
}
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
            <?php if (count($filas) > 0): ?>
                <?php foreach ($filas as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['tutor_curp'] ?? 'Sin CURP') ?></strong></td>
                        <td>
                            <?= htmlspecialchars(trim($row['tutor_nom'] . " " . $row['tutor_ap'] . " " . ($row['tutor_am'] ?? ''))) ?>
                            <?= ($row['es_adulto_mayor'] == 't') ? '<br><span class="adulto-mayor">ADULTO MAYOR</span>' : '' ?>
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
                        <td>
                            <a href="editar_tutor.php?curp=<?= urlencode($row['tutor_curp'] ?? '') ?>" style="background-color: #f39c12; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 11px;">✏️ Editar</a>
                            
                            <a href="registrar_enfermedad.php?curp_tutor=<?= urlencode($row['tutor_curp'] ?? '') ?>" style="background-color: #9b59b6; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 11px; display: block; text-align: center; margin-top:5px;">🏥 Salud Tutor</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
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