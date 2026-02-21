<?php

require 'config/database.php';

echo "Conexión exitosa 🚀";

$result = pg_query($conn, "SELECT * FROM usuario");

if (!$result) {
    die("Error en la consulta ❌");
}

echo "<h2>Usuarios:</h2>";

while ($row = pg_fetch_assoc($result)) {
    echo "RFC: " . $row['rfc'] . "<br>";
    echo "Nombre: " . $row['nombre'] . "<br>";
    echo "Apellido: " . $row['apellido'] . "<br>";
    echo "<hr>";
}

pg_close($conn);
?>