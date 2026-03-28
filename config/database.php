<?php

/*
|--------------------------------------------------------------------------
| Conexión directa simple a PostgreSQL
|--------------------------------------------------------------------------
| Aquí se colocan los datos directamente.
| Proyecto simplificado sin Composer ni .env
*/

$conn = pg_connect("host=localhost dbname=Aurora_db user=postgres password=060319");

if (!$conn) {
    die("Error de conexión ❌");
}