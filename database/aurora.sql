-- 1. EXTENSIONES Y TIPOS (ENUMS)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TYPE rol_usuario AS ENUM (
    'Psicologo', 'Medico', 'Trabajador_Social', 'Abogado', 'Administrador'
);

CREATE TYPE tipo_persona_enum AS ENUM (
    'NNA', 'TUTOR'
);

-- 2. SUPERENTIDAD: PERSONA
-- Aquí vive el domicilio y los datos básicos de todos
CREATE TABLE persona (
    curp character varying(18) PRIMARY KEY,
    nombre character varying(100) NOT NULL,
    apellido_paterno character varying(50) NOT NULL,
    apellido_materno character varying(50),
    sexo character varying(10),
    fecha_nacimiento date NOT NULL,
    tipo_persona tipo_persona_enum NOT NULL,
    -- Dirección Unificada
    calle character varying(100),
    numero_exterior character varying(10),
    numero_interior character varying(10),
    municipio character varying(100),
    estado_dir character varying(100)
);

-- 3. SUBENTIDAD: NNA (Niños, Niñas y Adolescentes)
CREATE TABLE nna (
    curp character varying(18) PRIMARY KEY REFERENCES persona(curp) ON DELETE CASCADE,
    nacionalidad character varying(50),
    situacion_calle boolean DEFAULT false,
    es_migrante boolean DEFAULT false,
    es_refugiado boolean DEFAULT false,
    poblacion_indigena boolean DEFAULT false
);

-- 4. SUBENTIDAD: TUTOR
CREATE TABLE tutor (
    curp character varying(18) PRIMARY KEY REFERENCES persona(curp) ON DELETE CASCADE,
    es_adulto_mayor boolean DEFAULT false,
    telefono character varying(20),
    correo character varying(100)
);

-- 5. RELACIÓN: NNA_TUTOR (Muchos a Muchos con Parentesco)
CREATE TABLE nna_tutor (
    curp_nna character varying(18) REFERENCES nna(curp) ON DELETE CASCADE,
    curp_tutor character varying(18) REFERENCES tutor(curp) ON DELETE CASCADE,
    relacion character varying(50), -- Aquí guardamos: MADRE, PADRE, TÍO, etc.
    PRIMARY KEY (curp_nna, curp_tutor)
);

-- 6. TABLA DE USUARIOS DEL SISTEMA
CREATE TABLE usuario (
    curp character varying(18) PRIMARY KEY,
    rfc character varying(13) UNIQUE NOT NULL,
    nombre character varying(100) NOT NULL,
    apellido_paterno character varying(50) NOT NULL,
    apellido_materno character varying(50),
    correo character varying(100) UNIQUE NOT NULL,
    contrasena character varying(255) NOT NULL,
    rol rol_usuario NOT NULL,
    municipio character varying(100),
    estado_dir character varying(100),
    estado character varying(10) DEFAULT 'ACTIVO',
    fecha_registro timestamp DEFAULT CURRENT_TIMESTAMP
);

-- 7. TABLAS DE SEGUIMIENTO (EJEMPLOS)
CREATE TABLE seguimiento_multidisciplinario (
    id_seguimiento uuid DEFAULT uuid_generate_v4() PRIMARY KEY,
    curp_nna character varying(18) REFERENCES nna(curp) ON DELETE CASCADE,
    curp_usuario character varying(18) REFERENCES usuario(curp),
    fecha_atencion timestamp DEFAULT CURRENT_TIMESTAMP,
    notas_evolucion text
);

-- 8. CATALOGOS AUXILIARES (OPCIONALES PARA FORMULARIOS)
CREATE TABLE cat_colonia (id_colonia SERIAL PRIMARY KEY, nombre VARCHAR(100));
CREATE TABLE cat_discapacidad (id_discapacidad SERIAL PRIMARY KEY, tipo VARCHAR(100));
CREATE TABLE cat_enfermedad (id_enfermedad SERIAL PRIMARY KEY, nombre_padecimiento VARCHAR(200));

---

-- 9. INSERCIÓN DEL ADMINISTRADOR (LUIS)
-- Usamos tus datos para que el sistema ya tenga dueño
INSERT INTO usuario (
    curp, rfc, nombre, apellido_paterno, apellido_materno, 
    correo, contrasena, rol, municipio, estado_dir
) VALUES (
    'AULL800101HDFRRN01', 
    'AULL800101ABC', 
    'LUIS', 
    'AGUILAR', 
    'TORRES', 
    'luis@aurora.com', 
    '123456', -- Contraseña inicial
    'Administrador', 
    'GUSTAVO A. MADERO', 
    'CIUDAD DE MÉXICO'
);