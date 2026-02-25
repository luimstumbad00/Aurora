<?php ini_set('display_errors', 1);
    error_reporting(E_ALL);
    require '../config/database.php';


$mensaje = "";
$tipoMensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete')
    { $curpAEliminar = $_POST['curp'] ?? '';

if ($curpAEliminar)
    { $deleteQuery = "DELETE FROM usuario WHERE curp = $1";

$resultDel = @pg_query_params($conn, $deleteQuery, array($curpAEliminar));
if ($resultDel)
    { $mensaje = "Usuario eliminado correctamente ✅"; $tipoMensaje = "success";
    }
    else
    { $mensaje = "Error al eliminar usuario ❌"; $tipoMensaje = "error"; }
    }
    else
    { $mensaje = "CURP no especificada para eliminar.";
$tipoMensaje = "error"; }
}
$usuarios = [];
$queryAll = "SELECT curp, rfc, nombre, direccion, sexo, nacimiento, tipo_personal, rol, estado, correo FROM usuario ORDER BY apellido_p, apellido_m, nombre";
$resultAll = @pg_query($conn, $queryAll);

if ($resultAll)
    { while ($row = pg_fetch_assoc($resultAll))
    {
$usuario = [ 'curp' => $row['curp'], 'rfc' => $row['rfc'], 'nombre' => $row['nombre'], 'direccion' => $row['direccion'],'sexo' => $row['sexo'], 'nacimiento' => $row['nacimiento'], 'tipo_personal' => $row['tipo_personal'], 'rol' => $row['rol'], 'estado' => $row['estado'], 'correo' => $row['correo'], ];
$usuarios[] = $usuario;
    }
    }
    else
    { $mensaje .= " No se pudieron cargar los usuarios.";
    $tipoMensaje = "error";
    }
?>
<!DOCTYPE html>
<html><head>
    <meta charset="UTF-8">
    <title>Ver Usuarios</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
    .usuario-tarjeta
        {
        border: 1px solid #ddd;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 12px;
        background: #f9f9f9;
        }
    
    .usuario-header
        {
        display: flex;
        justify-content: space-between;
        align-items: center;
        }
        
    .usuario-datos
        {
        margin-top: 8px;
        }
        
    .btn-borrar
        {
        background: #e74c3c;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        }
        
    .btn-borrar:hover 
        {
        background: #c0392b;
        }
        
    .mensaje
        {
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 12px;
        }
        
    .success
        {
        background: #d4edda;
        color: #155724;
        }
        
    .error
        {
        background: #f8d7da;
        color: #721c24;
        }
        
    </style></head>
    <body><div class="login-container">
        <h1>Lista de Usuarios</h1>
        <?php if ($mensaje): ?> <div class="mensaje <?= $tipoMensaje == 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($mensaje) ?> </div>

        <?php endif; ?>
        <?php if (!empty($usuarios)): ?>
        <?php foreach ($usuarios as $u): ?>
            
        <div class="usuario-tarjeta"><div class="usuario-header"><strong>
            <?= htmlspecialchars($u['nombre']) ?>
            (CURP: <?= htmlspecialchars($u['curp']) ?>)</strong>
        <form method="POST" style="margin:0;"> <input type="hidden" name="action" value="delete">
        <input type="hidden" name="curp" value="<?= htmlspecialchars($u['curp']) ?>">
        <button type="submit" class="btn-borrar" onclick="return confirm('¿Eliminar este usuario y todos sus datos?');">
            Borrar </button> </form> </div> <div class="usuario-datos"> <div><strong>
            RFC:</strong> <?= htmlspecialchars($u['rfc']) ?></div> <div><strong>
            Sexo:</strong> <?= htmlspecialchars($u['sexo']) ?></div> <div><strong>
            Nacimiento:</strong> <?= htmlspecialchars($u['nacimiento']) ?></div> <div><strong>
            Tipo de Personal:</strong> <?= htmlspecialchars($u['tipo_personal']) ?></div> <div><strong>
            Rol:</strong> <?= htmlspecialchars($u['rol'] ?? '') ?></div> <div><strong>
            Estado:</strong><?= htmlspecialchars($u['estado']) ?>
            </div><div><strong>
            Correo:</strong>
            <?= htmlspecialchars($u['correo']) ?>
            </div><div><strong>
            Dirección:</strong>
            <?= htmlspecialchars($u['direccion']) ?>
            </div></div></div>
        <?php endforeach; ?>
        <?php else: ?>
            <p>No hay usuarios registrados.
        </p> <?php endif; ?>
        </div></body></html>