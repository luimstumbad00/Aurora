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

-- 1. CREAR Y CONECTAR A LA BASE DE DATOS
DROP DATABASE IF EXISTS aurora; -- Por si hubo un intento fallido previo
CREATE DATABASE aurora;
\c aurora;

-- 2. EXTENSIONES
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- 3. ENUMS
CREATE TYPE rol_usuario AS ENUM ('Psicologo', 'Medico', 'Trabajador_Social', 'Abogado', 'Administrador');
CREATE TYPE tipo_persona_enum AS ENUM ('NNA', 'TUTOR');

-- 4. CATÁLOGOS
CREATE TABLE cat_entidad_federativa (
    id_entidad SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE cat_municipio (
    id_municipio SERIAL PRIMARY KEY,
    id_entidad INT REFERENCES cat_entidad_federativa(id_entidad),
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE cat_codigo_postal (
    id_cp SERIAL PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL
);

CREATE TABLE cat_idioma (
    id_idioma SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    variante VARCHAR(100)
);

CREATE TABLE cat_discapacidad (
    id_discapacidad SERIAL PRIMARY KEY,
    tipo VARCHAR(100) NOT NULL
);

CREATE TABLE cat_enfermedad (
    id_enfermedad SERIAL PRIMARY KEY,
    nombre_padecimiento VARCHAR(200) NOT NULL,
    tipo_enfermedad VARCHAR(100)
);

CREATE TABLE cat_colonia (
    id_colonia SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

-- 5. USUARIOS
CREATE TABLE usuario (
    curp VARCHAR(18) PRIMARY KEY,
    rfc VARCHAR(13) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(50) NOT NULL,
    apellido_materno VARCHAR(50),
    calle VARCHAR(100),
    numero_exterior VARCHAR(10),
    numero_interior VARCHAR(10),
    codigo_postal VARCHAR(10),
    municipio VARCHAR(100),
    estado_dir VARCHAR(100),
    sexo VARCHAR(10),
    tipo_personal VARCHAR(15), 
    rol rol_usuario,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(8), 
    correo VARCHAR(100) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    nacimiento DATE
);

-- 6. NNA Y TUTORES
CREATE TABLE persona (
    curp VARCHAR(18) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(50) NOT NULL,
    apellido_materno VARCHAR(50),
    sexo VARCHAR(10),
    fecha_nacimiento DATE NOT NULL,
    tipo_persona tipo_persona_enum NOT NULL
);

CREATE TABLE nna (
    curp VARCHAR(18) PRIMARY KEY REFERENCES persona(curp) ON DELETE CASCADE,
    nacionalidad VARCHAR(50),
    situacion_calle BOOLEAN DEFAULT FALSE,
    es_migrante BOOLEAN DEFAULT FALSE,
    es_refugiado BOOLEAN DEFAULT FALSE,
    poblacion_indigena BOOLEAN DEFAULT FALSE
);

CREATE TABLE tutor (
    curp VARCHAR(18) PRIMARY KEY REFERENCES persona(curp) ON DELETE CASCADE,
    es_adulto_mayor BOOLEAN DEFAULT FALSE,
    telefono VARCHAR(20),
    correo VARCHAR(100)
);

CREATE TABLE nna_tutor (
    curp_nna VARCHAR(18) REFERENCES nna(curp) ON DELETE CASCADE,
    curp_tutor VARCHAR(18) REFERENCES tutor(curp) ON DELETE CASCADE,
    relacion VARCHAR(50),
    PRIMARY KEY (curp_nna, curp_tutor)
);

-- 7. SALUD E IDIOMAS
CREATE TABLE persona_enfermedad (
    id_registro SERIAL PRIMARY KEY,
    curp VARCHAR(18) REFERENCES persona(curp) ON DELETE CASCADE,
    id_enfermedad INT REFERENCES cat_enfermedad(id_enfermedad),
    es_cronica BOOLEAN DEFAULT FALSE,
    esta_controlada BOOLEAN DEFAULT FALSE,
    tratamiento_actual TEXT
);

CREATE TABLE persona_discapacidad (
    id_registro SERIAL PRIMARY KEY,
    curp VARCHAR(18) REFERENCES persona(curp) ON DELETE CASCADE,
    id_discapacidad INT REFERENCES cat_discapacidad(id_discapacidad),
    grado_dependencia VARCHAR(50)
);

CREATE TABLE persona_idioma (
    id_registro SERIAL PRIMARY KEY,
    curp VARCHAR(18) REFERENCES persona(curp) ON DELETE CASCADE,
    id_idioma INT REFERENCES cat_idioma(id_idioma),
    nivel_dominio VARCHAR(50),
    requiere_traductor BOOLEAN DEFAULT FALSE
);

-- 8. HECHO VICTIMAL
CREATE TABLE hecho_victimal (
    id_hecho UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    curp_nna VARCHAR(18) REFERENCES nna(curp) ON DELETE CASCADE,
    fecha_hecho DATE,
    relato_hechos TEXT,
    id_colonia_hechos INT REFERENCES cat_colonia(id_colonia),
    calle_hechos VARCHAR(100)
);

CREATE TABLE dano_sufrido (
    id_dano SERIAL PRIMARY KEY,
    id_hecho UUID REFERENCES hecho_victimal(id_hecho) ON DELETE CASCADE,
    tipo_dano VARCHAR(50)
);

CREATE TABLE expediente_juridico (
    id_expediente UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    id_hecho UUID REFERENCES hecho_victimal(id_hecho) ON DELETE CASCADE,
    autoridad_conoce VARCHAR(100),
    numero_carpeta VARCHAR(100),
    estado_proceso VARCHAR(50)
);

CREATE TABLE seguimiento_multidisciplinario (
    id_seguimiento UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    curp_nna VARCHAR(18) REFERENCES nna(curp) ON DELETE CASCADE,
    curp_usuario VARCHAR(18) REFERENCES usuario(curp),
    fecha_atencion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notas_evolucion TEXT
);

-- 9. INSERTAR USUARIO DIRECTOR POR DEFECTO
INSERT INTO usuario (
    curp, rfc, nombre, apellido_paterno, apellido_materno, 
    rol, correo, contrasena, estado, nacimiento
) VALUES (
    'CURPDIRECTOR000000', 'RFCDIR0000000', 'Luis', 'Director', '', 
    'Administrador', 'luis@aurora.com', '123456', 'Activo', '1990-01-01'
);
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