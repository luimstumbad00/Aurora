<?php

/*
|--------------------------------------------------------------------------
| Conexión directa simple a PostgreSQL
|--------------------------------------------------------------------------
| Aquí se colocan los datos directamente.
| Proyecto simplificado sin Composer ni .env
*/

$conn = pg_connect("host=localhost dbname=aurora user=postgres password=1234");

if (!$conn) {
    die("Error de conexión ❌");
}