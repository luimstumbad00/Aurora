Sí, es muy importante que lo agregues, especialmente porque en tu script SQL usaste una ruta absoluta (/var/www/html/Aurora/catalogo_de_enfermedades.txt). Si alguien intenta instalar esto en Windows (XAMPP) o en un directorio distinto en Linux, la ejecución del script SQL fallará al intentar encontrar ese archivo .txt.

Aquí tienes tu README.md actualizado. Agregué una nota crucial en la sección de Configuración de la Base de Datos para que los desarrolladores (o tú mismo en el futuro) sepan que deben ajustar esa ruta antes de correr el script.
🌅 Proyecto Aurora - Sistema de Administración para Fundación en pro de los Derechos de NNA's

Aurora es un sistema web de gestión de personal y de trabajo multidisciplinario que permite administrar usuarios bajo distintos roles operativos (Psicólogo, Médico, Trabajador Social, Abogado y Administrador), así como dar un seguimiento riguroso a los casos de Niñas, Niños y Adolescentes (NNA) bajo los estándares del FUD y la LGDNNA. Cuenta con control seguro de sesiones, manejo de roles y una base de datos robusta en PostgreSQL normalizada en su totalidad hasta la Forma Normal de Boyce-Codd (FNBC).
🚀 Requisitos Previos

Dependiendo de tu sistema operativo, necesitarás instalar las siguientes herramientas.
🐧 Linux (Ubuntu)

Instala Apache, PHP, PostgreSQL y el controlador de PostgreSQL para PHP:
Bash

sudo apt update
sudo apt install apache2 php postgresql postgresql-contrib php-pgsql

🪟 Windows

    Descarga e instala XAMPP.

    Descarga e instala PostgreSQL.

    Abre el Panel de Control de XAMPP.

    En Apache selecciona Config → php.ini.

    Busca y habilita las siguientes extensiones eliminando el punto y coma (;) al inicio:

Ini, TOML

extension=pdo_pgsql
extension=pgsql

    Guarda los cambios.

    Reinicia Apache desde XAMPP.

📁 Instalación del Proyecto

Clona el repositorio o copia la carpeta Aurora dentro del directorio raíz de tu servidor web. Asegúrate de que el archivo catalogo_de_enfermedades.txt esté incluido en la raíz del proyecto.
Linux
Bash

cd /var/www/html
git clone <repositorio>

o simplemente copia la carpeta:
Bash

sudo cp -r Aurora /var/www/html/

La estructura final debe quedar como:
Plaintext

/var/www/html/Aurora

Windows

Copia la carpeta Aurora a:
Plaintext

C:\xampp\htdocs\Aurora

🗄️ Configuración de la Base de Datos

    ⚠️ IMPORTANTE - IMPORTACIÓN DEL CATÁLOGO CIE-10
    El script de base de datos incluye la importación automática de miles de padecimientos desde el archivo catalogo_de_enfermedades.txt.
    Si estás en Windows o tu ruta de instalación es diferente, debes abrir el archivo aurora_estructura.sql, buscar la línea que contiene \copy temp_cat y cambiar la ruta absoluta por la ruta real de tu sistema antes de ejecutar el script. Por ejemplo:
    \copy temp_cat(codigo_cie, nombre, tipo) FROM 'C:/xampp/htdocs/Aurora/catalogo_de_enfermedades.txt' DELIMITER ',' CSV;

Una vez configurada la ruta, abre una terminal y accede a PostgreSQL:
Bash

sudo -u postgres psql

Elimina la base de datos Aurora en caso de existir y créala nuevamente:
SQL

DROP DATABASE IF EXISTS aurora;
CREATE DATABASE aurora;

Sal de PostgreSQL:
SQL

\q

Ahora ejecuta el script de estructura incluido en el proyecto:
Bash

sudo -u postgres psql -d aurora -f /var/www/html/Aurora/aurora_estructura.sql

Si el comando finaliza sin errores, la base de datos Aurora quedará completamente instalada, incluyendo:

    Extensiones necesarias.

    Tipos de datos personalizados.

    Catálogos (incluyendo la carga masiva del CIE-10).

    Tablas del sistema.

    Relaciones y restricciones.

    Usuario administrador inicial.

No es necesario copiar ni pegar manualmente el contenido del script SQL.
⚙️ Configuración de la Conexión

Abre el archivo:
Plaintext

Aurora/config/database.php

y configura los datos de conexión correspondientes a tu instalación local de PostgreSQL:
PHP

$host = "localhost";
$port = "5432";
$dbname = "aurora";
$user = "postgres";
$password = "tu_password";

Guarda los cambios.
▶️ Inicio de Servicios
Linux

Inicia Apache y PostgreSQL:
Bash

sudo systemctl start apache2
sudo systemctl start postgresql

Opcionalmente verifica su estado:
Bash

sudo systemctl status apache2
sudo systemctl status postgresql

Windows

Desde XAMPP:

    Inicia Apache.

Desde Servicios de Windows:

    Inicia PostgreSQL.

🌐 Ejecución del Sistema

Abre tu navegador y accede a:
Plaintext

http://localhost/Aurora

o a la ruta correspondiente según tu configuración del servidor.
🔐 Credenciales de Acceso Inicial

Una vez desplegado el sistema, inicia sesión con el usuario administrador generado automáticamente por el script de instalación:
Plaintext

Correo: luis@aurora.com
Contraseña: Admin2024
Rol: Administrador

    Se recomienda cambiar la contraseña inmediatamente después del primer acceso.

📂 Estructura General del Proyecto
Plaintext

Aurora/
│
├── config/
│   └── database.php
│
├── assets/
│
├── views/
│
├── controllers/
│
├── models/
│
├── catalogo_de_enfermedades.txt
├── aurora_estructura.sql
│
└── index.php

📋 Resumen de Instalación
Plaintext

1. Instalar Apache, PHP y PostgreSQL.
2. Copiar Aurora a /var/www/html/Aurora (y verificar ruta en aurora_estructura.sql).
3. Crear la base de datos aurora.
4. Ejecutar aurora_estructura.sql.
5. Configurar database.php.
6. Iniciar Apache y PostgreSQL.
7. Abrir http://localhost/Aurora.
8. Iniciar sesión con:

   Correo: luis@aurora.com
   Contraseña: Admin2024

📄 Licencia

Proyecto Aurora desarrollado por DataSinc.
PHP

/**
 * Proyecto Aurora - Sistema de Administración
 *
 * @package     Aurora
 * @author      DataSinc
 * @copyright   2026 DataSinc. Todos los derechos reservados.
 * @version     1.0.0
 *
 * Queda prohibida la reproducción, distribución o modificación
 * total o parcial de este software sin autorización expresa
 * de sus autores.
 */