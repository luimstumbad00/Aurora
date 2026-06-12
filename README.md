Markdown
# 🌅 Proyecto Aurora - Sistema de Administración para Fundación en pro de los Derechos de NNA's

Aurora es un sistema web de gestión de personal y de trabajo multidisciplinario que permite administrar usuarios bajo distintos roles operativos (Psicólogo, Médico, Trabajador Social, Abogado y Administrador), así como dar un seguimiento riguroso a los casos de Niñas, Niños y Adolescentes (NNA) bajo los estándares del FUD y la LGDNNA. Cuenta con control seguro de sesiones, manejo de roles y una base de datos robusta en PostgreSQL normalizada en su totalidad hasta la **Forma Normal de Boyce-Codd (FNBC)**.

---

## 🚀 Requisitos Previos

Dependiendo de tu sistema operativo, necesitarás instalar las siguientes herramientas:

### 🐧 Para Linux (Ubuntu)
Instala el servidor web (Apache), PHP y el motor PostgreSQL junto con su extensión conductora. Abre tu terminal y ejecuta:
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

CREATE TABLE cat_tipo_contacto (
    id_tipo_contacto SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL
);

INSERT INTO cat_tipo_contacto (id_tipo_contacto, nombre, descripcion) VALUES
(1, 'Teléfono fijo', 'Número de teléfono fijo de casa, trabajo, o algun familiar. 10 digitos.'),
(2, 'Celular', 'Número de celular del tutor o de algun otro familiar. 10 digitos.'),
(3, 'Correo', 'Correo al que se pueda enviar documentación, generalmente de un adulto.'),
(4, 'Instagram', 'Cuenta de red social Instagram en donde se pueda establecer comunicación con la persona.'),
(5, 'Facebook', 'Cuenta de red social Facebook en donde se pueda establecer comunicación con la persona.'),
(6, 'LinkedIn', 'Cuenta de red social LinkedIn en donde se pueda establecer comunicación con la persona.'),
(7, 'Telegram', 'Cuenta de red social Telegram en donde se pueda establecer comunicación con la persona.'),
(8, 'Caseta Comunitaria', 'Número telefónico de la caseta o espacio central en localidades rurales sin cobertura particular.'),
(9, 'Red Vecinal / Autoridad Local', 'Contacto con un miembro del comité vecinal, jefe de manzana o líder comunitario para emergencias.'),
(10, 'Teléfono de Albergue / Refugio', 'Número institucional y extensión del centro de asistencia donde pernocta el NNA.'),
(11, 'Enlace Institucional TS', 'Contacto del área de Trabajo Social de dependencias homólogas (DIF, SIPINNA) para seguimiento.');

CREATE TABLE cat_parentesco (
    id_parentesco SERIAL PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE cat_motivo_ingreso (
    id_motivo_ingreso SERIAL PRIMARY KEY,
    nombre VARCHAR(150) UNIQUE NOT NULL
);

-- INSERCIÓN EN CAT_PARENTESCO
INSERT INTO cat_parentesco (id_parentesco, nombre) VALUES
(1, 'Madre'), (2, 'Padre'), (3, 'Abuela'), (4, 'Abuelo'), (5, 'Bisabuela'), 
(6, 'Bisabuelo'), (7, 'Hermana'), (8, 'Hermano'), (9, 'Media hermana'), (10, 'Medio hermano'), 
(11, 'Tía'), (12, 'Tío'), (13, 'Prima'), (14, 'Primo'), (15, 'Sobrina'), 
(16, 'Sobrino'), (17, 'Madrina'), (18, 'Padrino'), (19, 'Madrastra'), (20, 'Padrastro'), 
(21, 'Madre adoptiva'), (22, 'Padre adoptivo'), (23, 'Hermana adoptiva'), (24, 'Hermano adoptivo'), (25, 'Tutor legal'), 
(26, 'Tutora legal'), (27, 'Representante legal'), (28, 'Curador'), (29, 'Cuidador principal'), (30, 'Cuidadora principal'), 
(31, 'Familia de acogida'), (32, 'Madre de acogida'), (33, 'Padre de acogida'), (34, 'Guardador provisional'), (35, 'Persona responsable'), 
(36, 'Persona de confianza'), (37, 'Vecino responsable'), (38, 'Vecina responsable'), (39, 'Amigo responsable'), (40, 'Amiga responsable'), 
(41, 'Director de Centro de Asistencia Social'), (42, 'Personal de Centro de Asistencia Social'), (43, 'Trabajador Social'), (44, 'Procurador de Protección'), (45, 'Autoridad Judicial'), 
(46, 'Autoridad Administrativa'), (47, 'Agente Migratorio Responsable'), (48, 'Cónyuge'), (49, 'Concubinario'), (50, 'Concubina'), 
(51, 'Sin parentesco'), (52, 'Desconocido'), (53, 'Otro');

-- INSERCIÓN EN CAT_MOTIVO_INGRESO
INSERT INTO cat_motivo_ingreso (id_motivo_ingreso, nombre) VALUES
(1, 'Violencia física'), (2, 'Violencia psicológica'), (3, 'Violencia emocional'), (4, 'Violencia sexual'), (5, 'Violencia familiar'), 
(6, 'Violencia comunitaria'), (7, 'Violencia escolar'), (8, 'Bullying'), (9, 'Ciberacoso'), (10, 'Negligencia'), 
(11, 'Omisión de cuidados'), (12, 'Abandono'), (13, 'Maltrato infantil'), (14, 'Castigo corporal'), (15, 'Explotación laboral'), 
(16, 'Explotación sexual'), (17, 'Trata de personas'), (18, 'Trabajo infantil'), (19, 'Mendicidad forzada'), (20, 'Reclutamiento por grupos delictivos'), 
(21, 'Riesgo por delincuencia organizada'), (22, 'Situación de calle'), (23, 'Pobreza extrema'), (24, 'Carencia de vivienda'), (25, 'Carencia alimentaria'), 
(26, 'Desintegración familiar'), (27, 'Separación familiar'), (28, 'Conflictos familiares graves'), (29, 'Orfandad'), (30, 'Fallecimiento de tutor'), 
(31, 'Fallecimiento de ambos padres'), (32, 'Migración acompañada'), (33, 'Migración no acompañada'), (34, 'Niña, niño o adolescente refugiado'), (35, 'Solicitante de asilo'), 
(36, 'Desplazamiento forzado'), (37, 'Repatriación'), (38, 'Retorno asistido'), (39, 'Víctima de discriminación'), (40, 'Discriminación por discapacidad'), 
(41, 'Discriminación étnica'), (42, 'Discriminación lingüística'), (43, 'Discriminación por nacionalidad'), (44, 'Conflicto con la ley'), (45, 'Proceso judicial en curso'), 
(46, 'Medidas de protección judicial'), (47, 'Consumo de alcohol'), (48, 'Consumo de drogas'), (49, 'Riesgo de adicciones'), (50, 'Problemas de salud mental'), 
(51, 'Intento de autolesión'), (52, 'Conducta suicida'), (53, 'Discapacidad sin red de apoyo'), (54, 'Enfermedad crónica sin cuidados adecuados'), (55, 'Emergencia médica'), 
(56, 'Canalización por escuela'), (57, 'Canalización por hospital'), (58, 'Canalización por Ministerio Público'), (59, 'Canalización por DIF'), (60, 'Canalización por Procuraduría de Protección'), 
(61, 'Canalización por autoridad migratoria'), (62, 'Canalización por autoridad judicial'), (63, 'Solicitud voluntaria de protección'), (64, 'Reintegración familiar'), (65, 'Seguimiento de caso'), 
(66, 'Medida especial de protección'), (67, 'Riesgo social'), (68, 'Riesgo comunitario'), (69, 'Otro');

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
    id_parentesco INT REFERENCES cat_parentesco(id_parentesco), -- FK Normalizada en 3FN/FNBC
    PRIMARY KEY (curp_nna, curp_tutor)
);

CREATE TABLE persona_contacto_adicional (
    id_contacto SERIAL PRIMARY KEY,
    curp VARCHAR(18) REFERENCES persona(curp) ON DELETE CASCADE,
    id_tipo_contacto INT REFERENCES cat_tipo_contacto(id_tipo_contacto),
    valor_contacto VARCHAR(255) NOT NULL,
    descripcion TEXT
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
    calle_hechos VARCHAR(100),
    id_motivo_ingreso INT REFERENCES cat_motivo_ingreso(id_motivo_ingreso) -- FK que asocia el motivo real del ingreso
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