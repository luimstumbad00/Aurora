-- ============================================================
--  PROYECTO AURORA — SCRIPT UNIFICADO Y DEFINITIVO v3
--  PostgreSQL | Arquitecto: Senior DB Architect
--  Modelo FUD/LGDNNA | Dirección normalizada
--  Orden jerárquico para evitar errores de Foreign Keys
-- ============================================================


-- ============================================================
--  FASE 1: EXTENSIONES
-- ============================================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";


-- ============================================================
--  FASE 2: TABLAS DE CATÁLOGO + DATOS POR DEFECTO
-- ============================================================

-- ----------------------------------------------------------
--  2A. cat_rol_sistema
-- ----------------------------------------------------------
CREATE TABLE cat_rol_sistema (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);

INSERT INTO cat_rol_sistema (nombre) VALUES
    ('Administrador'),
    ('Psicologo'),
    ('Medico'),
    ('Trabajador_Social'),
    ('Abogado');

COMMENT ON TABLE cat_rol_sistema IS 'Catálogo de roles operativos del personal en la plataforma Aurora.';

-- ----------------------------------------------------------
--  2B. cat_sexo
-- ----------------------------------------------------------
CREATE TABLE cat_sexo (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);

INSERT INTO cat_sexo (nombre) VALUES
    ('Hombre'),
    ('Mujer'),
    ('Indeterminado');

COMMENT ON TABLE cat_sexo IS 'Catálogo de sexo registral conforme al FUD/LGDNNA.';

-- ----------------------------------------------------------
--  2C. cat_tipo_discapacidad
-- ----------------------------------------------------------
CREATE TABLE cat_tipo_discapacidad (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);

INSERT INTO cat_tipo_discapacidad (nombre) VALUES
    ('Física'),
    ('Intelectual'),
    ('Sensorial'),
    ('Mental'),
    ('Múltiple');

COMMENT ON TABLE cat_tipo_discapacidad IS 'Catálogo de tipos de discapacidad según clasificación gubernamental.';

-- ----------------------------------------------------------
--  2D. cat_grado_dependencia
-- ----------------------------------------------------------
CREATE TABLE cat_grado_dependencia (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);

INSERT INTO cat_grado_dependencia (nombre) VALUES
    ('Independiente'),
    ('Requiere Apoyo Moderado'),
    ('Requiere Apoyo Total');

COMMENT ON TABLE cat_grado_dependencia IS 'Catálogo de grados de dependencia funcional del NNA.';

-- ----------------------------------------------------------
--  2E. cat_nivel_competencia
-- ----------------------------------------------------------
CREATE TABLE cat_nivel_competencia (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);

INSERT INTO cat_nivel_competencia (nombre) VALUES
    ('Básico'),
    ('Intermedio'),
    ('Avanzado'),
    ('Nativo');

COMMENT ON TABLE cat_nivel_competencia IS 'Catálogo de niveles de competencia lingüística oral/señada.';


-- ============================================================
--  FASE 3: ESTRUCTURA DE DIRECCIÓN NORMALIZADA
--  Jerarquía: entidad_federativa → asentamiento → direccion
-- ============================================================

-- ----------------------------------------------------------
--  3A. ENTIDAD_FEDERATIVA
-- ----------------------------------------------------------
CREATE TABLE entidad_federativa (
    id_ent      SERIAL          PRIMARY KEY,
    nom_ent     VARCHAR(200)    NOT NULL UNIQUE
);

INSERT INTO entidad_federativa (nom_ent) VALUES
    ('Aguascalientes'),
    ('Baja California'),
    ('Baja California Sur'),
    ('Campeche'),
    ('Chiapas'),
    ('Chihuahua'),
    ('Ciudad de México'),
    ('Coahuila'),
    ('Colima'),
    ('Durango'),
    ('Guanajuato'),
    ('Guerrero'),
    ('Hidalgo'),
    ('Jalisco'),
    ('México'),
    ('Michoacán'),
    ('Morelos'),
    ('Nayarit'),
    ('Nuevo León'),
    ('Oaxaca'),
    ('Puebla'),
    ('Querétaro'),
    ('Quintana Roo'),
    ('San Luis Potosí'),
    ('Sinaloa'),
    ('Sonora'),
    ('Tabasco'),
    ('Tamaulipas'),
    ('Tlaxcala'),
    ('Veracruz'),
    ('Yucatán'),
    ('Zacatecas');

COMMENT ON TABLE entidad_federativa IS 'Catálogo de entidades federativas de México. Extensible para países extranjeros.';

-- ----------------------------------------------------------
--  3B. ASENTAMIENTO
--  (Colonia/Localidad con CP vinculada a entidad federativa)
-- ----------------------------------------------------------
CREATE TABLE asentamiento (
    id_asen     SERIAL          PRIMARY KEY,
    nom_mun     VARCHAR(200)    NOT NULL,
    nom_col     VARCHAR(200)    NOT NULL,
    cp_asen     VARCHAR(5)      NOT NULL,
    id_ent      INT             NOT NULL REFERENCES entidad_federativa(id_ent) ON DELETE RESTRICT,

    CONSTRAINT chk_cp_asen CHECK (cp_asen ~ '^\d{5}$')
);

COMMENT ON TABLE  asentamiento         IS 'Asentamientos humanos (colonias) con CP, vinculados a entidad federativa.';
COMMENT ON COLUMN asentamiento.nom_mun IS 'Municipio o alcaldía al que pertenece el asentamiento.';
COMMENT ON COLUMN asentamiento.nom_col IS 'Nombre de la colonia o asentamiento humano.';
COMMENT ON COLUMN asentamiento.cp_asen IS 'Código postal de 5 dígitos.';

CREATE INDEX idx_asen_ent ON asentamiento(id_ent);
CREATE INDEX idx_asen_cp  ON asentamiento(cp_asen);

-- ----------------------------------------------------------
--  3C. DIRECCION
--  (Domicilio específico: calle + números + referencia + asentamiento)
-- ----------------------------------------------------------
CREATE TABLE direccion (
    id_dir      SERIAL          PRIMARY KEY,
    calle_dir   VARCHAR(200),
    no_ext_dir  VARCHAR(20),
    no_int_dir  VARCHAR(20),
    ref_dir     VARCHAR(200),
    id_asen     INT             NOT NULL REFERENCES asentamiento(id_asen) ON DELETE RESTRICT
);

COMMENT ON TABLE  direccion            IS 'Domicilios específicos compuestos por calle, número y referencia al asentamiento.';
COMMENT ON COLUMN direccion.ref_dir    IS 'Referencia adicional de ubicación (entre calles, cerca de, etc.).';
COMMENT ON COLUMN direccion.no_ext_dir IS 'Número exterior.';
COMMENT ON COLUMN direccion.no_int_dir IS 'Número interior (depto, local, etc.).';

CREATE INDEX idx_dir_asen ON direccion(id_asen);


-- ============================================================
--  FASE 4: OPERACIÓN DE LA PLATAFORMA
--  Tabla: usuario_sistema
-- ============================================================

CREATE TABLE usuario_sistema (
    id_usuario          UUID            PRIMARY KEY DEFAULT uuid_generate_v4(),
    curp                VARCHAR(18)     NOT NULL UNIQUE,
    rfc                 VARCHAR(13)     UNIQUE,
    nombre              VARCHAR(100)    NOT NULL,
    apellido_paterno    VARCHAR(100)    NOT NULL,
    apellido_materno    VARCHAR(100),
    correo              VARCHAR(255)    NOT NULL UNIQUE,
    contrasena          VARCHAR(255)    NOT NULL,
    id_rol              INT             NOT NULL REFERENCES cat_rol_sistema(id) ON DELETE RESTRICT,
    estado              VARCHAR(20)     NOT NULL DEFAULT 'ACTIVO',
    municipio_labora    VARCHAR(150),
    fecha_registro      TIMESTAMP       NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_curp_usuario   CHECK (LENGTH(TRIM(curp)) = 18),
    CONSTRAINT chk_correo_usuario CHECK (correo LIKE '%@%.%'),
    CONSTRAINT chk_estado_usuario CHECK (estado IN ('ACTIVO', 'INACTIVO', 'SUSPENDIDO'))
);

COMMENT ON TABLE  usuario_sistema            IS 'Usuarios operativos de la plataforma Aurora (personal multidisciplinario).';
COMMENT ON COLUMN usuario_sistema.contrasena IS 'Almacenar SIEMPRE como hash (bcrypt/argon2). Nunca texto plano.';
COMMENT ON COLUMN usuario_sistema.estado     IS 'ACTIVO | INACTIVO | SUSPENDIDO';
COMMENT ON COLUMN usuario_sistema.id_rol     IS 'FK a cat_rol_sistema. Define los permisos funcionales del usuario.';


-- ============================================================
--  FASE 5: ENTIDADES CENTRALES
--  Tablas: tutor · nna
-- ============================================================

-- ----------------------------------------------------------
--  5A. TUTOR — Responsable legal / familiar del NNA
-- ----------------------------------------------------------
CREATE TABLE tutor (
    id_tutor            SERIAL          PRIMARY KEY,
    curp_tutor          VARCHAR(18)     UNIQUE,
    nombre              VARCHAR(100)    NOT NULL,
    primer_apellido     VARCHAR(100)    NOT NULL,
    segundo_apellido    VARCHAR(100),
    telefono            VARCHAR(20),
    correo              VARCHAR(255),
    es_adulto_mayor     BOOLEAN         NOT NULL DEFAULT FALSE,

    CONSTRAINT chk_curp_tutor   CHECK (curp_tutor IS NULL OR LENGTH(TRIM(curp_tutor)) = 18),
    CONSTRAINT chk_correo_tutor CHECK (correo     IS NULL OR correo LIKE '%@%.%')
);

COMMENT ON TABLE  tutor                 IS 'Tutores, padres o responsables legales de los NNA registrados.';
COMMENT ON COLUMN tutor.es_adulto_mayor IS 'TRUE si el tutor tiene 60 años o más (condición de vulnerabilidad adicional).';

-- ----------------------------------------------------------
--  5B. NNA — Niñas, Niños y Adolescentes (FUD/LGDNNA)
-- ----------------------------------------------------------
CREATE TABLE nna (
    -- Identidad primaria
    id_nna              SERIAL          PRIMARY KEY,
    folio_nna           VARCHAR(50)     NOT NULL UNIQUE,
    nombre              VARCHAR(100)    NOT NULL,
    prim_ap             VARCHAR(100)    NOT NULL,
    seg_ap              VARCHAR(100),
    fecha_nacimiento    DATE            NOT NULL,
    curp                VARCHAR(18)     UNIQUE,
    id_sexo             INT             NOT NULL REFERENCES cat_sexo(id) ON DELETE RESTRICT,

    -- Dirección normalizada (modelo profesor FUD/LGDNNA)
    dir_actual          INT             REFERENCES direccion(id_dir)          ON DELETE SET NULL,
    luga_nac_nna        INT             REFERENCES entidad_federativa(id_ent) ON DELETE SET NULL,

    -- Vulnerabilidad / Contexto (BOOLEAN — sin catálogo externo por diseño)
    situacion_calle     BOOLEAN         NOT NULL DEFAULT FALSE,
    es_migrante         BOOLEAN         NOT NULL DEFAULT FALSE,
    es_refugiado        BOOLEAN         NOT NULL DEFAULT FALSE,
    poblacion_indigena  BOOLEAN         NOT NULL DEFAULT FALSE,

    -- Auditoría
    fecha_registro      TIMESTAMP       NOT NULL DEFAULT NOW(),
    registrado_por      UUID            REFERENCES usuario_sistema(id_usuario) ON DELETE SET NULL,

    CONSTRAINT chk_curp_nna      CHECK (curp IS NULL OR LENGTH(TRIM(curp)) = 18),
    CONSTRAINT chk_fecha_nac_nna CHECK (fecha_nacimiento <= CURRENT_DATE)
);

COMMENT ON TABLE  nna                    IS 'Registro central de NNA conforme al FUD y la LGDNNA.';
COMMENT ON COLUMN nna.folio_nna          IS 'Folio único de ingreso asignado por el sistema o autoridad competente.';
COMMENT ON COLUMN nna.id_sexo            IS 'FK a cat_sexo.';
COMMENT ON COLUMN nna.dir_actual         IS 'FK a direccion. Domicilio actual del NNA.';
COMMENT ON COLUMN nna.luga_nac_nna       IS 'FK a entidad_federativa. Entidad o país de nacimiento del NNA.';
COMMENT ON COLUMN nna.situacion_calle    IS 'TRUE si el NNA se encuentra o encontraba en situación de calle.';
COMMENT ON COLUMN nna.es_migrante        IS 'TRUE si el NNA tiene condición migratoria activa o reconocida.';
COMMENT ON COLUMN nna.es_refugiado       IS 'TRUE si cuenta con reconocimiento de condición de refugiado.';
COMMENT ON COLUMN nna.poblacion_indigena IS 'TRUE si pertenece a una comunidad o pueblo indígena.';
COMMENT ON COLUMN nna.registrado_por     IS 'FK al usuario que realizó el registro inicial del NNA.';


-- ============================================================
--  FASE 6: RELACIONES Y LISTAS MULTIVALORADAS
-- ============================================================

-- ----------------------------------------------------------
--  6A. NNA ↔ TUTOR
-- ----------------------------------------------------------
CREATE TABLE nna_tutor (
    id_nna              INT             NOT NULL REFERENCES nna(id_nna)     ON DELETE CASCADE,
    id_tutor            INT             NOT NULL REFERENCES tutor(id_tutor) ON DELETE CASCADE,
    relacion_parentesco VARCHAR(80)     NOT NULL,
    es_contacto_ppal    BOOLEAN         NOT NULL DEFAULT FALSE,
    fecha_vinculacion   DATE,

    PRIMARY KEY (id_nna, id_tutor)
);

COMMENT ON TABLE  nna_tutor                  IS 'Relación N:M entre NNA y sus tutores/responsables.';
COMMENT ON COLUMN nna_tutor.es_contacto_ppal IS 'TRUE si este tutor es el contacto de referencia principal para el NNA.';

-- ----------------------------------------------------------
--  6B. NACIONALIDADES del NNA
-- ----------------------------------------------------------
CREATE TABLE nna_nacionalidad (
    id_nna              INT             NOT NULL REFERENCES nna(id_nna) ON DELETE CASCADE,
    pais_nacionalidad   VARCHAR(100)    NOT NULL,

    PRIMARY KEY (id_nna, pais_nacionalidad)
);

COMMENT ON TABLE nna_nacionalidad IS 'Nacionalidades del NNA; admite doble o múltiple nacionalidad.';

-- ----------------------------------------------------------
--  6C. DISCAPACIDADES del NNA
-- ----------------------------------------------------------
CREATE TABLE nna_discapacidad (
    id_nna                     INT     NOT NULL REFERENCES nna(id_nna)              ON DELETE CASCADE,
    id_tipo_discapacidad       INT     NOT NULL REFERENCES cat_tipo_discapacidad(id) ON DELETE RESTRICT,
    id_grado_dependencia       INT     NOT NULL REFERENCES cat_grado_dependencia(id) ON DELETE RESTRICT,
    diagnostico_medico_oficial BOOLEAN NOT NULL DEFAULT FALSE,
    descripcion_adicional      TEXT,

    PRIMARY KEY (id_nna, id_tipo_discapacidad)
);

COMMENT ON TABLE  nna_discapacidad                            IS 'Discapacidades registradas del NNA según clasificación oficial.';
COMMENT ON COLUMN nna_discapacidad.diagnostico_medico_oficial IS 'TRUE si existe dictamen médico oficial que certifica la discapacidad.';

-- ----------------------------------------------------------
--  6D. LENGUAS del NNA
-- ----------------------------------------------------------
CREATE TABLE nna_lengua (
    id_nna               INT          NOT NULL REFERENCES nna(id_nna)              ON DELETE CASCADE,
    nombre_lengua        VARCHAR(100) NOT NULL,
    es_preferente        BOOLEAN      NOT NULL DEFAULT FALSE,
    id_nivel_competencia INT          NOT NULL REFERENCES cat_nivel_competencia(id) ON DELETE RESTRICT,
    requiere_interprete  BOOLEAN      NOT NULL DEFAULT FALSE,

    PRIMARY KEY (id_nna, nombre_lengua)
);

COMMENT ON TABLE  nna_lengua                     IS 'Lenguas habladas / señadas por el NNA, incluyendo lenguas indígenas y LSM.';
COMMENT ON COLUMN nna_lengua.es_preferente       IS 'TRUE si esta es la lengua de comunicación principal del NNA.';
COMMENT ON COLUMN nna_lengua.requiere_interprete IS 'TRUE si el NNA necesita intérprete para la atención institucional.';

-- ----------------------------------------------------------
--  6E. CONTACTOS ADICIONALES del NNA
-- ----------------------------------------------------------
CREATE TABLE nna_contacto_adicional (
    id_contacto    SERIAL       PRIMARY KEY,
    id_nna         INT          NOT NULL REFERENCES nna(id_nna) ON DELETE CASCADE,
    tipo_contacto  VARCHAR(80)  NOT NULL,
    valor_contacto VARCHAR(255) NOT NULL,
    descripcion    VARCHAR(255),

    CONSTRAINT uq_nna_contacto UNIQUE (id_nna, tipo_contacto, valor_contacto)
);

COMMENT ON TABLE nna_contacto_adicional IS 'Medios de contacto alternativos vinculados al NNA (redes sociales, referencias vecinales, etc.).';


-- ============================================================
--  FASE 7: SEGUIMIENTO MULTIDISCIPLINARIO
-- ============================================================

CREATE TABLE expediente_seguimiento (
    id_seguimiento       UUID      PRIMARY KEY DEFAULT uuid_generate_v4(),
    id_nna               INT       NOT NULL REFERENCES nna(id_nna)                 ON DELETE CASCADE,
    id_usuario           UUID      NOT NULL REFERENCES usuario_sistema(id_usuario)  ON DELETE RESTRICT,
    id_area_atencion     INT       NOT NULL REFERENCES cat_rol_sistema(id)          ON DELETE RESTRICT,
    fecha_atencion       TIMESTAMP NOT NULL DEFAULT NOW(),
    notas_evolucion      TEXT,
    archivo_adjunto_path VARCHAR(500),

    CONSTRAINT chk_archivo_path CHECK (
        archivo_adjunto_path IS NULL
        OR archivo_adjunto_path ~ '^[a-zA-Z0-9_./\-]+$'
    )
);

COMMENT ON TABLE  expediente_seguimiento                      IS 'Registro cronológico de todas las intervenciones multidisciplinarias sobre un NNA.';
COMMENT ON COLUMN expediente_seguimiento.id_area_atencion     IS 'FK a cat_rol_sistema. Disciplina desde la que se realiza la intervención.';
COMMENT ON COLUMN expediente_seguimiento.archivo_adjunto_path IS 'Ruta relativa al archivo adjunto. Validar permisos de acceso en capa PHP.';


-- ============================================================
--  FASE 8: CAMBIO DE ESTADO — TRABAJADOR SOCIAL A INACTIVO
-- ============================================================

UPDATE usuario_sistema
SET    estado = 'INACTIVO'
WHERE  id_rol = (
    SELECT id FROM cat_rol_sistema WHERE nombre = 'Trabajador_Social'
);


-- ============================================================
--  FASE 9: ÍNDICES DE RENDIMIENTO
-- ============================================================

-- usuario_sistema
CREATE INDEX idx_usuario_rol    ON usuario_sistema(id_rol);
CREATE INDEX idx_usuario_estado ON usuario_sistema(estado);

-- nna
CREATE INDEX idx_nna_sexo           ON nna(id_sexo);
CREATE INDEX idx_nna_dir_actual     ON nna(dir_actual);
CREATE INDEX idx_nna_luga_nac       ON nna(luga_nac_nna);
CREATE INDEX idx_nna_fecha_nac      ON nna(fecha_nacimiento);
CREATE INDEX idx_nna_vulnerabilidad ON nna(situacion_calle, es_migrante, es_refugiado, poblacion_indigena);

-- expediente_seguimiento
CREATE INDEX idx_exp_nna     ON expediente_seguimiento(id_nna);
CREATE INDEX idx_exp_usuario ON expediente_seguimiento(id_usuario);
CREATE INDEX idx_exp_fecha   ON expediente_seguimiento(fecha_atencion DESC);
CREATE INDEX idx_exp_area    ON expediente_seguimiento(id_area_atencion);

-- nna_tutor
CREATE INDEX idx_nna_tutor_tutor ON nna_tutor(id_tutor);


-- ============================================================
--  FIN DEL SCRIPT — PROYECTO AURORA (v3 · DIRECCIÓN NORMALIZADA)
-- ============================================================