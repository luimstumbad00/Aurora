# 🌅 Proyecto Aurora - Sistema de Administración para Fundación en pro de los Derechos de NNA's

Aurora es un sistema web de gestión de personal y de trabajo multidisciplinario que permite administrar usuarios bajo distintos roles operativos (Psicólogo, Médico, Trabajador Social, Abogado y Administrador), así como dar un seguimiento riguroso a los casos de Niñas, Niños y Adolescentes (NNA) bajo los estándares del FUD y la LGDNNA. Cuenta con control seguro de sesiones, manejo de roles y una base de datos robusta en PostgreSQL normalizada en su totalidad hasta la **Forma Normal de Boyce-Codd (FNBC)**.

---

# 🚀 Requisitos Previos

Dependiendo de tu sistema operativo, necesitarás instalar las siguientes herramientas.

## 🐧 Linux (Ubuntu)

Instala Apache, PHP, PostgreSQL y el controlador de PostgreSQL para PHP:

```bash
sudo apt update
sudo apt install apache2 php postgresql postgresql-contrib php-pgsql
```

## 🪟 Windows

1. Descarga e instala XAMPP.
2. Descarga e instala PostgreSQL.
3. Abre el Panel de Control de XAMPP.
4. En Apache selecciona **Config → php.ini**.
5. Busca y habilita las siguientes extensiones eliminando el punto y coma (`;`) al inicio:

```ini
extension=pdo_pgsql
extension=pgsql
```

6. Guarda los cambios.
7. Reinicia Apache desde XAMPP.

---

# 📁 Instalación del Proyecto

Clona el repositorio o copia la carpeta Aurora dentro del directorio raíz de tu servidor web.

## Linux

```bash
cd /var/www/html
git clone <repositorio>
```

o simplemente copia la carpeta:

```bash
sudo cp -r Aurora /var/www/html/
```

La estructura final debe quedar como:

```text
/var/www/html/Aurora
```

## Windows

Copia la carpeta Aurora a:

```text
C:\xampp\htdocs\Aurora
```

---

# 🗄️ Configuración de la Base de Datos

Una vez que el proyecto se encuentre dentro de:

```text
/var/www/html/Aurora
```

abre una terminal y accede a PostgreSQL:

```bash
sudo -u postgres psql
```

Elimina la base de datos Aurora en caso de existir y créala nuevamente:

```sql
DROP DATABASE IF EXISTS aurora;
CREATE DATABASE aurora;
```

Sal de PostgreSQL:

```sql
\q
```

Ahora ejecuta el script de estructura incluido en el proyecto:

```bash
sudo -u postgres psql -d aurora -f /var/www/html/Aurora/aurora_estructura.sql
```

Si el comando finaliza sin errores, la base de datos Aurora quedará completamente instalada, incluyendo:

* Extensiones necesarias.
* Tipos de datos personalizados.
* Catálogos.
* Tablas del sistema.
* Relaciones y restricciones.
* Usuario administrador inicial.

No es necesario copiar ni pegar manualmente el contenido del script SQL.

---

# ⚙️ Configuración de la Conexión

Abre el archivo:

```text
Aurora/config/database.php
```

y configura los datos de conexión correspondientes a tu instalación local de PostgreSQL:

```php
$host = "localhost";
$port = "5432";
$dbname = "aurora";
$user = "postgres";
$password = "tu_password";
```

Guarda los cambios.

---

# ▶️ Inicio de Servicios

## Linux

Inicia Apache y PostgreSQL:

```bash
sudo systemctl start apache2
sudo systemctl start postgresql
```

Opcionalmente verifica su estado:

```bash
sudo systemctl status apache2
sudo systemctl status postgresql
```

## Windows

Desde XAMPP:

* Inicia Apache.

Desde Servicios de Windows:

* Inicia PostgreSQL.

---

# 🌐 Ejecución del Sistema

Abre tu navegador y accede a:

```text
http://localhost/Aurora
```

o a la ruta correspondiente según tu configuración del servidor.

---

# 🔐 Credenciales de Acceso Inicial

Una vez desplegado el sistema, inicia sesión con el usuario administrador generado automáticamente por el script de instalación:

```text
Correo: luis@aurora.com'
Contraseña: Admin2024
Rol: Administrador
```

> Se recomienda cambiar la contraseña inmediatamente después del primer acceso.

---

# 📂 Estructura General del Proyecto

```text
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
├── aurora_estructura.sql
│
└── index.php
```

---

# 📋 Resumen de Instalación

```text
1. Instalar Apache, PHP y PostgreSQL.
2. Copiar Aurora a /var/www/html/Aurora.
3. Crear la base de datos aurora.
4. Ejecutar aurora_estructura.sql.
5. Configurar database.php.
6. Iniciar Apache y PostgreSQL.
7. Abrir http://localhost/Aurora.
8. Iniciar sesión con:

   Correo: luis@aurora.com
   Contraseña: 123456
```

---

# 📄 Licencia

Proyecto Aurora desarrollado por DataSinc.

```php
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
```
