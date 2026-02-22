<?php

/*
|--------------------------------------------------------------------------
| 1️⃣ Cargar Composer (autoload)
|--------------------------------------------------------------------------
| Esto permite usar la librería phpdotenv instalada con Composer.
*/
require __DIR__ . '/../vendor/autoload.php';


/*
|--------------------------------------------------------------------------
| 2️⃣ Cargar variables del archivo .env
|--------------------------------------------------------------------------
| Aquí se leen las variables:
| DB_HOST, DB_NAME, DB_USER, DB_PASS
*/
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


/*
|--------------------------------------------------------------------------
| 3️⃣ Crear conexión a PostgreSQL
|--------------------------------------------------------------------------
| Se construye el string de conexión usando variables de entorno.
*/
$conn = pg_connect(
    "host=" . $_ENV['DB_HOST'] .
    " dbname=" . $_ENV['DB_NAME'] .
    " user=" . $_ENV['DB_USER'] .
    " password=" . $_ENV['DB_PASS']
);

if (!$conn) {
    die("Error de conexión ❌");
}

echo "<h2>Conexión exitosa 🚀</h2>";



/*
|--------------------------------------------------------------------------
| 4️⃣ Consulta SQL
|--------------------------------------------------------------------------
| Extraemos los campos compuestos usando (campo).subcampo
*/
$query = "
SELECT 
    curp,
    rfc,
    (nombre).apellido_paterno AS apellido_paterno,
    (nombre).apellido_materno AS apellido_materno,
    (nombre).nombres AS nombres,
    (direccion).calle AS calle,
    (direccion).numero_exterior AS numero_exterior,
    (direccion).numero_interior AS numero_interior,
    (direccion).codigo_postal AS codigo_postal,
    (direccion).municipio AS municipio,
    (direccion).estado AS estado_direccion,
    sexo,
    edad,
    tipo_personal,
    rol,
    estado,
    correo,
    fecha_registro
FROM usuario
";

$result = pg_query($conn, $query);

if (!$result) {
    die("Error en la consulta ❌");
}


/*
|--------------------------------------------------------------------------
| 5️⃣ Mostrar resultados
|--------------------------------------------------------------------------
*/
echo "<h3>Lista de Usuarios</h3>";

while ($row = pg_fetch_assoc($result)) {

    echo "<hr>";

    echo "<strong>CURP:</strong> " . $row['curp'] . "<br>";
    echo "<strong>RFC:</strong> " . $row['rfc'] . "<br>";

    echo "<strong>Nombre Completo:</strong> "
        . $row['apellido_paterno'] . " "
        . $row['apellido_materno'] . " "
        . $row['nombres'] . "<br>";

    echo "<strong>Dirección:</strong> "
        . $row['calle'] . " "
        . $row['numero_exterior'] . " Int. "
        . $row['numero_interior'] . ", CP "
        . $row['codigo_postal'] . ", "
        . $row['municipio'] . ", "
        . $row['estado_direccion'] . "<br>";

    echo "<strong>Sexo:</strong> " . $row['sexo'] . "<br>";
    echo "<strong>Edad:</strong> " . $row['edad'] . "<br>";

    echo "<strong>Tipo de Personal:</strong> " . $row['tipo_personal'] . "<br>";

    echo "<strong>Rol:</strong> " . $row['rol'] . "<br>";

    echo "<strong>Estado:</strong> " . $row['estado'] . "<br>";

    echo "<strong>Correo:</strong> " . $row['correo'] . "<br>";

    echo "<strong>Fecha de Registro:</strong> " . $row['fecha_registro'] . "<br>";
}

pg_close($conn);

?>