Markdown
# 🌅 Proyecto Aurora - Sistema de Administración para Fundación en pro de los Derechos de NNA's

Aurora es un sistema web de gestión de personal y de trabajo que permite administrar usuarios bajo distintos roles (Director, Coordinador, Analista, etc.), así como a futuro darle seguimiento a todos los casos que atiende esta fundación con niveles de acceso, manejo seguro de sesiones y una base de datos robusta en PostgreSQL que utiliza tipos de datos compuestos para estandarizar la información en México.

---

## 🚀 Requisitos Previos

Dependiendo de tu sistema operativo, necesitarás instalar las siguientes herramientas:

### 🐧 Para Linux (Ubuntu)
Solo necesitas instalar el servidor web (Apache), PHP y PostgreSQL con su respectiva extensión.
Abre tu terminal y ejecuta:
```bash
sudo apt update
sudo apt install apache2 php postgresql postgresql-contrib php-pgsql
🪟 Para Windows
Descarga e instala XAMPP (para obtener Apache y PHP).

Descarga e instala PostgreSQL desde su página oficial.

¡Importante! Abre el panel de XAMPP, haz clic en Config en el módulo de Apache, abre el archivo php.ini y quita el punto y coma (;) al inicio de estas dos líneas para activar la conexión con PostgreSQL:

extension=pdo_pgsql

extension=pgsql

Guarda el archivo y reinicia Apache en XAMPP.

🛠️ Instalación y Configuración
Clona o mueve la carpeta del proyecto Aurora a tu directorio de servidor web:

Linux: /var/www/html/Aurora

Windows: C:\xampp\htdocs\Aurora

Configura tu conexión a la base de datos editando el archivo config/database.php con las credenciales de tu instalación local de PostgreSQL.

🗄️ Configuración de la Base de Datos
Abre el servidor de Postgres (psql) en tu consola.

Crea una base de datos nueva llamada aurora.

Ejecuta el siguiente script SQL para crear la estructura, los tipos de datos personalizados y cargar el usuario administrador por defecto:

SQL
-- Configuración de tipos compuestos y ENUM
CREATE TYPE public.direccion_mex AS (
    calle character varying(100),
    numero_exterior character varying(10),
    numero_interior character varying(10),
    codigo_postal character varying(10),
    municipio character varying(100),
    estado character varying(100)
);

CREATE TYPE public.nombre_mex AS (
    apellido_paterno character varying(50),
    apellido_materno character varying(50),
    nombres character varying(100)
);

CREATE TYPE public.rol_enum AS ENUM (
    'Director',
    'Coordinador',
    'Psicologo',
    'Doctor',
    'Abogado',
    'Trabajador Social',
    'Analista'
);

-- Creación de la tabla principal
CREATE TABLE public.usuario (
    curp character varying(18) NOT NULL,
    rfc character varying(13) NOT NULL,
    nombre public.nombre_mex NOT NULL,
    direccion public.direccion_mex NOT NULL,
    sexo character varying(10),
    tipo_personal character varying(15),
    rol public.rol_enum NOT NULL,
    fecha_registro timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    estado character varying(8),
    correo character varying(100) NOT NULL,
    contrasena character varying(255) NOT NULL,
    nacimiento date,
    CONSTRAINT usuario_sexo_check CHECK (((sexo)::text = ANY ((ARRAY['Masculino'::character varying, 'Femenino'::character varying, 'Otro'::character varying])::text[]))),
    CONSTRAINT usuario_tipo_personal_check CHECK (((tipo_personal)::text = ANY ((ARRAY['Empleado'::character varying, 'Voluntario'::character varying])::text[])))
);

-- Inserción del usuario administrador por defecto
INSERT INTO public.usuario (curp, rfc, nombre, direccion, sexo, tipo_personal, rol, estado, correo, contrasena, nacimiento) 
VALUES (
    'AUTL061220HMCGRSA5', 
    'AUTL061220LKA', 
    ROW('AGUILAR', 'TORRES', 'LUIS')::public.nombre_mex, 
    ROW('kmmm', '90', '', '67890', 'GUSTAVO A. MADERO', 'Nayarit')::public.direccion_mex, 
    'Masculino', 
    'Empleado', 
    'Director'::public.rol_enum, 
    'Activo', 
    'luis@aurora.com', 
    '123456', 
    '2026-02-03'
);

-- Llaves y restricciones
ALTER TABLE ONLY public.usuario ADD CONSTRAINT usuario_pkey PRIMARY KEY (curp);
ALTER TABLE ONLY public.usuario ADD CONSTRAINT usuario_correo_key UNIQUE (correo);
ALTER TABLE ONLY public.usuario ADD CONSTRAINT usuario_rfc_key UNIQUE (rfc);
🔐 Credenciales de Acceso por Defecto
Una vez que hayas levantado el servidor y montado la base de datos, entra a http://localhost/Aurora (o la ruta que hayas asignado) e inicia sesión con:

Correo: luis@aurora.com

Contraseña: 123456

Rol: Director

PHP
/**
 * Proyecto Aurora - Sistema de Administracion
 * * @package     Aurora
 * @author      DataSinc
 * @copyright   2026 DataSinc. Todos los derechos reservados.
 * @version     1.0.0
 * @description Este código es propiedad de DataSinc. Queda prohibida 
 * su reproducción, distribución o modificación sin 
 * autorización expresa.
 */