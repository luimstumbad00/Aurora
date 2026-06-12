-- ============================================================
--  PROYECTO AURORA — SCRIPT UNIFICADO Y DEFINITIVO v6.1 (FNBC)
--  PostgreSQL | Arquitecto: Senior DB Architect
--  Normalización: 1FN ✓  2FN ✓  3FN ✓  FNBC ✓
--  24 relaciones · Modelo Geográfico Híbrido · Catálogos FUD/LGDNNA
-- ============================================================
--
--  CAMBIOS RESPECTO A v6:
--  [FUD] cat_tipo_contacto      → NUEVO catálogo de tipos de contacto
--  [REF] nna_contacto_adicional → tipo_contacto VARCHAR → id_tipo_contacto INT FK
--
--  INTACTO DESDE v6:
--  entidad_federativa, cat_municipio, direccion, usuario_sistema,
--  todos los demás catálogos, nna, tutor, tablas puente, expediente_seguimiento
-- ============================================================
--
--  HISTORIAL DE VERSIONES:
--  v3 → Catálogos reales (ENUMs eliminados)
--  v4 → Normalización FNBC (cat_municipio nueva, usuario_sistema refactorizado)
--  v5 → Modelo Geográfico Híbrido (asentamiento eliminado, direccion absorbe colonia+CP)
--  v6 → Catálogos FUD/LGDNNA (parentesco, lengua, país, escolaridad, motivo, enfermedad)
--  v6.1 → cat_tipo_contacto normalizado (tipo_contacto VARCHAR → FK)
-- ============================================================


-- ============================================================
--  FASE 1: EXTENSIONES
-- ============================================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";


-- ============================================================
--  FASE 2: TABLAS DE CATÁLOGO + DATOS POR DEFECTO
--  (12 relaciones — PK atómica → FNBC automática)
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

-- ----------------------------------------------------------
--  2F. cat_parentesco
-- ----------------------------------------------------------
CREATE TABLE cat_parentesco (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);

INSERT INTO cat_parentesco (nombre) VALUES
    ('Madre'), ('Padre'), ('Abuela'), ('Abuelo'), ('Bisabuela'), 
    ('Bisabuelo'), ('Hermana'), ('Hermano'), ('Media hermana'), ('Medio hermano'), 
    ('Tía'), ('Tío'), ('Prima'), ('Primo'), ('Sobrina'), 
    ('Sobrino'), ('Madrina'), ('Padrino'), ('Madrastra'), ('Padrastro'), 
    ('Madre adoptiva'), ('Padre adoptivo'), ('Hermana adoptiva'), ('Hermano adoptivo'), ('Tutor legal'), 
    ('Tutora legal'), ('Representante legal'), ('Curador'), ('Cuidador principal'), ('Cuidadora principal'), 
    ('Familia de acogida'), ('Madre de acogida'), ('Padre de acogida'), ('Guardador provisional'), ('Persona responsable'), 
    ('Persona de confianza'), ('Vecino responsable'), ('Vecina responsable'), ('Amigo responsable'), ('Amiga responsable'), 
    ('Director de Centro de Asistencia Social'), ('Personal de Centro de Asistencia Social'), ('Trabajador Social'), ('Procurador de Protección'), ('Autoridad Judicial'), 
    ('Autoridad Administrativa'), ('Agente Migratorio Responsable'), ('Cónyuge'), ('Concubinario'), ('Concubina'), 
    ('Sin parentesco'), ('Desconocido'), ('Otro');

COMMENT ON TABLE cat_parentesco IS 'Catálogo de tipos de parentesco o relación entre tutor y NNA conforme al FUD/LGDNNA.';

-- ----------------------------------------------------------
--  2G. cat_lengua
-- ----------------------------------------------------------
CREATE TABLE cat_lengua (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);

INSERT INTO cat_lengua (nombre) VALUES
('Akateko'),('Amuzgo'),('Awakateko'),('Ayapaneco'),('Chatino'),('Chichimeca Jonaz'),('Chinanteco'),('Chocholteco'),('Chol'),('Chontal de Oaxaca'),('Chontal de Tabasco'),
('Chuj'),('Cochimi'),('Cora'),('Cuicateco'),('Guarijio'),('Huave'),('Huichol'),('Ixcateco'),('Ixil'),('Jacalteko'),('Kaqchikel'),('Kickapoo'),('Kiche'),
('Kiliwa'),('Kumiai'),('Lacandon'),('Mam'),('Mateo'),('Matlatzinca'),('Maya Yucateco'),('Mazahua'),('Mazateco'),('Mixe'),('Mixteco'),('Nahuatl'),('Oluteco'),
('Opata'),('Otomi'),('Paipai'),('Pame'),('Papago'),('Pima'),('Popoloca'),('Popoluca'),('Popoluca de la Sierra'),('Qanjobal'),('Qeqchi'),('Qatok'),
('Sakapulteko'),('Sayulteco'),('Seri'),('Sipakapense'),('Tarahumara'),('Tarasco Purepecha'),('Teko'),('Tektiteko'),('Tepehua'),('Tepehuano del Norte'),('Tepehuano del Sur'),
('Texistepequeño'),('Tlapaneco Mephaa'),('Tlahuica'),('Totonaco'),('Triqui'),('Tseltal'),('Tsotsil'),('Uspanteko'),('Yaqui'),('Zapoteco'),('Zoque'),
('Zoque');


COMMENT ON TABLE cat_lengua IS 'Catálogo de lenguas habladas/señadas por NNA conforme al FUD/LGDNNA. Incluye lenguas indígenas nacionales y LSM.';

-- ----------------------------------------------------------
--  2H. cat_pais
-- ----------------------------------------------------------
CREATE TABLE cat_pais (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);

INSERT INTO cat_pais (nombre) VALUES
('Afghanistan'),('Albania'),('Alemania'),('Andorra'),('Angola'),('Argentina'),('Australia'),('Austria'),('Bahamas'),('Baréin'),('Barbados'),('Bélgica'),
('Belice'),('Benín'),('Bután'),('Bolivia'),('Botsuana'),('Brasil'),('Bulgaria'),('Burkina Faso'),('Burundi'),('Cabo Verde'),('Camboya'),('Camerún'),('Canadá'),
('Catar'),('Chad'),('Chile'),('China'),('Chipre'),('Ciudad del Vaticano'),('Colombia'),('Comoras'),('Corea del Norte'),('Corea del Sur'),('Costa de Marfil'),
('Costa Rica'),('Cuba'),('Dinamarca'),('Dominica'),('Ecuador'),('Egipto'),('El Salvador'),('Emiratos Árabes Unidos'),('Eslovaquia'),('Eslovenia'),('España'),
('Estonia'),('Etiopía'),('Fiyi'),('Filipinas'),('Finlandia'),('Francia'),('Gabón'),('Gambia'),('Ghana'),('Granada'),('Grecia'),('Guinea'),('Guinea Ecuatorial'),('Guinea-Bisau'),
('Guyana'),('Guyana Británica'),('Haití'),('Honduras'),('Hungría'),('India'),('Indonesia'),('Irak'),('Irán'),('Irlanda'),('Islandia'),('Israel'),('Islas Salomón'),
('Italia'),('Jamaica'),('Japón'),('Jordania'),('Kenia'),('Kirguistán'),('Kuwait'),('Laos'),('Lesoto'),('Líbano'),('Liberia'),('Liechtenstein'),('Libia'),
('Lituania'),('Luxemburgo'),('Macedonia'),('Malaui'),('Malasia'),('Maldivas'),('Malí'),('Malta'),('Marruecos'),('Mauricio'),('Mauritania'),('México'),('Micronesia'),('Moldavia'),('Mónaco'),
('Mongolia'),('Mozambique'),('Namibia'),('Nauru'),('Nepal'),('Nicaragua'),('Níger'),('Nigeria'),('Noruega'),('Nueva Guinea'),('Omán'),('Países Bajos'),
('Pakistán'),('Panamá'),('Paraguay'),('Perú'),('Portugal'),('Puerto Rico'),('Reino Unido'),('República Centroafricana'),('República del Congo'),('República Dominicana'),('Bielorrusia'),('Ucrania'),('Ruanda'),
('Rumanía'),('Sahara Occidental'),('Samoa'),('San Marino'),('Santa Lucía'),('Santo Tomé y Príncipe'),('Senegal'),('Seychelles'),('Sierra Leona'),('Singapur'),
('Siria'),('Somalia'),('Sudáfrica'),('Sudán'),('Suazilandia'),('Suecia'),('Suiza'),('Surinam'),('Tailandia'),('Taiwán'),('Tanzania'),('Togo'),('Trinidad y Tobago'),
('Túnez'),('Turquía'),('Uganda'),('Uruguay'),('Venezuela'),('Vietnam del Norte'),('Yemen'),('Yibuti'),('Yugoslavia'),('Zaire'),('Zambia'),('Zimbabue');


COMMENT ON TABLE cat_pais IS 'Catálogo de países de nacionalidad/origen del NNA. Base inicial para NNA migrantes y refugiados conforme al FUD/LGDNNA.';

-- ----------------------------------------------------------
--  2I. cat_escolaridad
-- ----------------------------------------------------------
CREATE TABLE cat_escolaridad (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);

INSERT INTO cat_escolaridad (nombre) VALUES
    ('Ninguna'),
    ('Preescolar'),
    ('Primaria Incompleta'),
    ('Primaria Completa'),
    ('Secundaria Incompleta'),
    ('Secundaria Completa'),
    ('Bachillerato o Superior');

COMMENT ON TABLE cat_escolaridad IS 'Catálogo de niveles de escolaridad del NNA conforme al FUD/LGDNNA.';

-- ----------------------------------------------------------
--  2J. cat_motivo_ingreso
-- ----------------------------------------------------------
CREATE TABLE cat_motivo_ingreso (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);

INSERT INTO cat_motivo_ingreso (nombre) VALUES
    ('Violencia física'), ('Violencia psicológica'), ('Violencia emocional'), ('Violencia sexual'), ('Violencia familiar'), 
    ('Violencia comunitaria'), ('Violencia escolar'), ('Bullying'), ('Ciberacoso'), ('Negligencia'), 
    ('Omisión de cuidados'), ('Abandono'), ('Maltrato infantil'), ('Castigo corporal'), ('Explotación laboral'), 
    ('Explotación sexual'), ('Trata de personas'), ('Trabajo infantil'), ('Mendicidad forzada'), ('Reclutamiento por grupos delictivos'), 
    ('Riesgo por delincuencia organizada'), ('Situación de calle'), ('Pobreza extrema'), ('Carencia de vivienda'), ('Carencia alimentaria'), 
    ('Desintegración familiar'), ('Separación familiar'), ('Conflictos familiares graves'), ('Orfandad'), ('Fallecimiento de tutor'), 
    ('Fallecimiento de ambos padres'), ('Migración accompagnée'), ('Migración no acompañada'), ('Niña, niño o adolescente refugiado'), ('Solicitante de asilo'), 
    ('Desplazamiento forzado'), ('Repatriación'), ('Retorno asistido'), ('Víctima de discriminación'), ('Discriminación por discapacidad'), 
    ('Discriminación étnica'), ('Discriminación lingüística'), ('Discriminación por nacionalidad'), ('Conflicto con la ley'), ('Proceso judicial en curso'), 
    ('Medidas de protección judicial'), ('Consumo de alcohol'), ('Consumo de drogas'), ('Riesgo de adicciones'), ('Problemas de salud mental'), 
    ('Intento de autolesión'), ('Conducta suicida'), ('Discapacidad sin red de apoyo'), ('Enfermedad crónica sin cuidados adecuados'), ('Emergencia médica'), 
    ('Canalización por escuela'), ('Canalización por hospital'), ('Canalización por Ministerio Público'), ('Canalización por DIF'), ('Canalización por Procuraduría de Protección'), 
    ('Canalización por autoridad migratoria'), ('Canalización por autoridad judicial'), ('Solicitud voluntaria de protección'), ('Reintegración familiar'), ('Seguimiento de caso'), 
    ('Medida especial de protección'), ('Riesgo social'), ('Riesgo comunitario'), ('Otro');

COMMENT ON TABLE cat_motivo_ingreso IS 'Catálogo de motivos de ingreso del NNA al sistema de protección conforme al FUD/LGDNNA.';

-- ----------------------------------------------------------
--  2K. cat_enfermedad
--  Estructura especial: incluye código CIE-10
-- ----------------------------------------------------------
CREATE TABLE cat_enfermedad (
    id_enfermedad   SERIAL          PRIMARY KEY,
    codigo_cie      VARCHAR(10)     UNIQUE,
    nombre          VARCHAR(255)    NOT NULL UNIQUE
);

INSERT INTO cat_enfermedad (codigo_cie, nombre) VALUES
    ('J45',   'Asma'),
    ('F84.0', 'Trastorno del espectro autista');

COMMENT ON TABLE  cat_enfermedad            IS 'Catálogo de enfermedades con código CIE-10. Preinsertados 2 registros de ejemplo; diseñado para importación masiva del catálogo CIE-10 completo.';
COMMENT ON COLUMN cat_enfermedad.codigo_cie IS 'Código CIE-10. UNIQUE y opcional para enfermedades sin código asignado.';
COMMENT ON COLUMN cat_enfermedad.nombre     IS 'Nombre clínico oficial de la enfermedad o diagnóstico.';

-- ----------------------------------------------------------
--  2L. cat_tipo_contacto  ← NUEVO (v6.1)
--  DF: {id} → {nombre}  |  FNBC ✓
--  Normaliza el campo tipo_contacto VARCHAR de nna_contacto_adicional
-- ----------------------------------------------------------
CREATE TABLE cat_tipo_contacto (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);

INSERT INTO cat_tipo_contacto (nombre) VALUES
    ('Teléfono fijo'),
    ('Celular'),
    ('Correo'),
    ('Instagram'),
    ('Facebook'),
    ('LinkedIn'),
    ('Telegram'),
    ('Caseta Comunitaria'),
    ('Red Vecinal / Autoridad Local'),
    ('Teléfono de Albergue / Refugio'),
    ('Enlace Institucional TS');

COMMENT ON TABLE cat_tipo_contacto IS 'v6.1: Catálogo de tipos de contacto alternativo del NNA. Normaliza tipo_contacto VARCHAR en nna_contacto_adicional.';


-- ============================================================
--  FASE 3: GEOGRAFÍA NORMALIZADA — MODELO HÍBRIDO (FNBC)
--  Jerarquía: entidad_federativa → cat_municipio → direccion
--  INTACTO DESDE v5 — NO MODIFICAR
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

COMMENT ON TABLE entidad_federativa IS 'Catálogo de entidades federativas de México. Extensible para países extranjeros (NNA migrantes/refugiados).';

-- ----------------------------------------------------------
--  3B. CAT_MUNICIPIO
-- ----------------------------------------------------------
CREATE TABLE cat_municipio (
    id_municipio    SERIAL          PRIMARY KEY,
    nom_mun         VARCHAR(200)    NOT NULL,
    id_ent          INT             NOT NULL REFERENCES entidad_federativa(id_ent) ON DELETE RESTRICT,

    CONSTRAINT uq_municipio_ent UNIQUE (nom_mun, id_ent)
);

COMMENT ON TABLE  cat_municipio         IS '3FN/FNBC: Catálogo de municipios/alcaldías vinculados a su entidad federativa.';
COMMENT ON COLUMN cat_municipio.nom_mun IS 'Nombre del municipio o alcaldía.';
COMMENT ON COLUMN cat_municipio.id_ent  IS 'FK a entidad_federativa. El municipio pertenece a una sola entidad.';

CREATE INDEX idx_municipio_ent ON cat_municipio(id_ent);

-- ----------------------------------------------------------
--  3C. DIRECCION — MODELO HÍBRIDO
-- ----------------------------------------------------------
CREATE TABLE direccion (
    id_dir          SERIAL          PRIMARY KEY,
    calle_dir       VARCHAR(200),
    no_ext_dir      VARCHAR(20),
    no_int_dir      VARCHAR(20),
    ref_dir         VARCHAR(200),
    colonia_abierta VARCHAR(200)    NOT NULL,
    codigo_postal   VARCHAR(5)      NOT NULL,
    id_municipio    INT             NOT NULL REFERENCES cat_municipio(id_municipio) ON DELETE RESTRICT,

    CONSTRAINT chk_codigo_postal CHECK (codigo_postal ~ '^\d{5}$')
);

COMMENT ON TABLE  direccion                 IS 'Modelo Geográfico Híbrido (v5): domicilio completo con colonia y CP como texto abierto, anclado al municipio normalizado vía FK.';
COMMENT ON COLUMN direccion.calle_dir       IS 'Nombre de la calle o vialidad.';
COMMENT ON COLUMN direccion.no_ext_dir      IS 'Número exterior.';
COMMENT ON COLUMN direccion.no_int_dir      IS 'Número interior (depto, local, etc.).';
COMMENT ON COLUMN direccion.ref_dir         IS 'Referencia adicional (entre calles, cerca de, etc.).';
COMMENT ON COLUMN direccion.colonia_abierta IS 'Nombre de la colonia o asentamiento en texto libre.';
COMMENT ON COLUMN direccion.codigo_postal   IS 'Código postal de 5 dígitos. Validado por CONSTRAINT chk_codigo_postal.';
COMMENT ON COLUMN direccion.id_municipio    IS 'FK a cat_municipio. Ancla geográfico normalizado.';

CREATE INDEX idx_dir_municipio ON direccion(id_municipio);
CREATE INDEX idx_dir_cp        ON direccion(codigo_postal);


-- ============================================================
--  FASE 4: OPERACIÓN DE LA PLATAFORMA
--  INTACTO DESDE v5 — NO MODIFICAR
-- ============================================================

CREATE TABLE usuario_sistema (
    id_usuario              UUID            PRIMARY KEY DEFAULT uuid_generate_v4(),
    curp                    VARCHAR(18)     NOT NULL UNIQUE,
    rfc                     VARCHAR(13)     UNIQUE,
    nombre                  VARCHAR(100)    NOT NULL,
    apellido_paterno        VARCHAR(100)    NOT NULL,
    apellido_materno        VARCHAR(100),
    correo                  VARCHAR(255)    NOT NULL UNIQUE,
    contrasena              VARCHAR(255)    NOT NULL,
    id_rol                  INT             NOT NULL REFERENCES cat_rol_sistema(id)     ON DELETE RESTRICT,
    id_municipio_labora     INT             REFERENCES cat_municipio(id_municipio)      ON DELETE SET NULL,
    estado                  VARCHAR(20)     NOT NULL DEFAULT 'ACTIVO',
    fecha_registro          TIMESTAMP       NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_curp_usuario   CHECK (LENGTH(TRIM(curp)) = 18),
    CONSTRAINT chk_correo_usuario CHECK (correo LIKE '%@%.%'),
    CONSTRAINT chk_estado_usuario CHECK (estado IN ('ACTIVO', 'INACTIVO', 'SUSPENDIDO'))
);

COMMENT ON TABLE  usuario_sistema                     IS 'Usuarios operativos de la plataforma Aurora. Reestructurado en 3FN: municipio_labora VARCHAR → id_municipio_labora FK.';
COMMENT ON COLUMN usuario_sistema.contrasena          IS 'Almacenar SIEMPRE como hash (bcrypt/argon2). Nunca texto plano.';
COMMENT ON COLUMN usuario_sistema.id_rol              IS 'FK a cat_rol_sistema. Define permisos funcionales del usuario.';
COMMENT ON COLUMN usuario_sistema.id_municipio_labora IS '3FN: FK a cat_municipio. Reemplaza municipio_labora VARCHAR.';
COMMENT ON COLUMN usuario_sistema.estado              IS 'ACTIVO | INACTIVO | SUSPENDIDO — atributo propio del usuario, NO transitivo, se conserva en FNBC.';


-- ============================================================
--  FASE 5: ENTIDADES CENTRALES
-- ============================================================

-- ----------------------------------------------------------
--  5A. TUTOR  (sin cambios en v6.1)
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
--  DF: {id_nna} → {todos los atributos}  |  FNBC ✓
-- ----------------------------------------------------------
CREATE TABLE nna (
    id_nna              SERIAL          PRIMARY KEY,
    folio_nna           VARCHAR(50)     NOT NULL UNIQUE,
    nombre              VARCHAR(100)    NOT NULL,
    prim_ap             VARCHAR(100)    NOT NULL,
    seg_ap              VARCHAR(100),
    fecha_nacimiento    DATE            NOT NULL,
    curp                VARCHAR(18)     UNIQUE,
    id_sexo             INT             NOT NULL REFERENCES cat_sexo(id)              ON DELETE RESTRICT,
    id_escolaridad      INT             REFERENCES cat_escolaridad(id)                ON DELETE RESTRICT,
    id_motivo_ingreso   INT             REFERENCES cat_motivo_ingreso(id)             ON DELETE RESTRICT,
    dir_actual          INT             REFERENCES direccion(id_dir)                  ON DELETE SET NULL,
    luga_nac_nna        INT             REFERENCES entidad_federativa(id_ent)         ON DELETE SET NULL,
    situacion_calle     BOOLEAN         NOT NULL DEFAULT FALSE,
    es_migrante         BOOLEAN         NOT NULL DEFAULT FALSE,
    es_refugiado        BOOLEAN         NOT NULL DEFAULT FALSE,
    poblacion_indigena  BOOLEAN         NOT NULL DEFAULT FALSE,
    fecha_registro      TIMESTAMP       NOT NULL DEFAULT NOW(),
    registrado_por      UUID            REFERENCES usuario_sistema(id_usuario)        ON DELETE SET NULL,

    CONSTRAINT chk_curp_nna      CHECK (curp IS NULL OR LENGTH(TRIM(curp)) = 18),
    CONSTRAINT chk_fecha_nac_nna CHECK (fecha_nacimiento <= CURRENT_DATE)
);

COMMENT ON TABLE  nna                    IS 'Registro central de NNA conforme al FUD y la LGDNNA.';
COMMENT ON COLUMN nna.folio_nna          IS 'Folio único de ingreso asignado por el sistema o autoridad competente.';
COMMENT ON COLUMN nna.id_sexo            IS 'FK a cat_sexo.';
COMMENT ON COLUMN nna.id_escolaridad     IS 'FK a cat_escolaridad. Nivel educativo actual del NNA conforme al FUD/LGDNNA.';
COMMENT ON COLUMN nna.id_motivo_ingreso  IS 'FK a cat_motivo_ingreso. Causa principal de ingreso al sistema de protección conforme al FUD/LGDNNA.';
COMMENT ON COLUMN nna.dir_actual         IS 'FK a direccion. Domicilio actual del NNA.';
COMMENT ON COLUMN nna.luga_nac_nna       IS 'FK a entidad_federativa. Entidad o país de nacimiento del NNA.';
COMMENT ON COLUMN nna.situacion_calle    IS 'TRUE si el NNA se encuentra o encontraba en situación de calle.';
COMMENT ON COLUMN nna.es_migrante        IS 'TRUE si el NNA tiene condición migratoria activa o reconocida.';
COMMENT ON COLUMN nna.es_refugiado       IS 'TRUE si cuenta con reconocimiento de condición de refugiado.';
COMMENT ON COLUMN nna.poblacion_indigena IS 'TRUE si pertenece a una comunidad o pueblo indígena.';
COMMENT ON COLUMN nna.registrado_por     IS 'FK al usuario que realizó el registro inicial del NNA.';


-- ============================================================
--  FASE 6: RELACIONES Y LISTAS MULTIVALORADAS
--  (6 relaciones)
-- ============================================================

-- ----------------------------------------------------------
--  6A. NNA ↔ TUTOR
-- ----------------------------------------------------------
CREATE TABLE nna_tutor (
    id_nna              INT     NOT NULL REFERENCES nna(id_nna)        ON DELETE CASCADE,
    id_tutor            INT     NOT NULL REFERENCES tutor(id_tutor)    ON DELETE CASCADE,
    id_parentesco       INT     NOT NULL REFERENCES cat_parentesco(id) ON DELETE RESTRICT,
    es_contacto_ppal    BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_vinculacion   DATE,

    PRIMARY KEY (id_nna, id_tutor)
);

COMMENT ON TABLE  nna_tutor                  IS 'Relación N:M entre NNA y sus tutores/responsables.';
COMMENT ON COLUMN nna_tutor.id_parentesco    IS 'FK a cat_parentesco. Tipo de vínculo entre el tutor y el NNA.';
COMMENT ON COLUMN nna_tutor.es_contacto_ppal IS 'TRUE si este tutor es el contacto de referencia principal para el NNA.';

-- ----------------------------------------------------------
--  6B. NACIONALIDADES del NNA
-- ----------------------------------------------------------
CREATE TABLE nna_nacionalidad (
    id_nna      INT     NOT NULL REFERENCES nna(id_nna)  ON DELETE CASCADE,
    id_pais     INT     NOT NULL REFERENCES cat_pais(id) ON DELETE RESTRICT,

    PRIMARY KEY (id_nna, id_pais)
);

COMMENT ON TABLE  nna_nacionalidad         IS 'Nacionalidades del NNA; admite doble o múltiple nacionalidad.';
COMMENT ON COLUMN nna_nacionalidad.id_pais IS 'FK a cat_pais. Reemplaza pais_nacionalidad VARCHAR (v6).';

-- ----------------------------------------------------------
--  6C. DISCAPACIDADES del NNA
-- ----------------------------------------------------------
CREATE TABLE nna_discapacidad (
    id_nna                     INT     NOT NULL REFERENCES nna(id_nna)               ON DELETE CASCADE,
    id_tipo_discapacidad       INT     NOT NULL REFERENCES cat_tipo_discapacidad(id)  ON DELETE RESTRICT,
    id_grado_dependencia       INT     NOT NULL REFERENCES cat_grado_dependencia(id)  ON DELETE RESTRICT,
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
    id_nna               INT     NOT NULL REFERENCES nna(id_nna)               ON DELETE CASCADE,
    id_lengua            INT     NOT NULL REFERENCES cat_lengua(id)            ON DELETE RESTRICT,
    es_preferente        BOOLEAN NOT NULL DEFAULT FALSE,
    id_nivel_competencia INT     NOT NULL REFERENCES cat_nivel_competencia(id) ON DELETE RESTRICT,
    requiere_interprete  BOOLEAN NOT NULL DEFAULT FALSE,

    PRIMARY KEY (id_nna, id_lengua)
);

COMMENT ON TABLE  nna_lengua                     IS 'Lenguas habladas/señadas por el NNA. PK compuesta: (id_nna, id_lengua).';
COMMENT ON COLUMN nna_lengua.id_lengua           IS 'FK a cat_lengua. Reemplaza nombre_lengua VARCHAR (v6).';
COMMENT ON COLUMN nna_lengua.es_preferente       IS 'TRUE si esta es la lengua de comunicación principal del NNA.';
COMMENT ON COLUMN nna_lengua.requiere_interprete IS 'TRUE si el NNA necesita intérprete para la atención institucional.';

-- ----------------------------------------------------------
--  6E. CONTACTOS ADICIONALES del NNA  (v6.1: tipo_contacto → FK)
--  PK atómica (id_contacto)  |  FNBC ✓
-- ----------------------------------------------------------
CREATE TABLE nna_contacto_adicional (
    id_contacto         SERIAL       PRIMARY KEY,
    id_nna              INT          NOT NULL REFERENCES nna(id_nna)              ON DELETE CASCADE,
    -- v6.1: texto libre reemplazado por FK a catálogo
    id_tipo_contacto    INT          NOT NULL REFERENCES cat_tipo_contacto(id)    ON DELETE RESTRICT,
    valor_contacto      VARCHAR(255) NOT NULL,
    descripcion         VARCHAR(255),

    CONSTRAINT uq_nna_contacto UNIQUE (id_nna, id_tipo_contacto, valor_contacto)
);

COMMENT ON TABLE  nna_contacto_adicional              IS 'Medios de contacto alternativos del NNA. v6.1: tipo_contacto VARCHAR → id_tipo_contacto FK a cat_tipo_contacto.';
COMMENT ON COLUMN nna_contacto_adicional.id_tipo_contacto IS 'v6.1: FK a cat_tipo_contacto. Reemplaza tipo_contacto VARCHAR para normalizar el medio de contacto.';
COMMENT ON COLUMN nna_contacto_adicional.valor_contacto   IS 'Valor del contacto: número, URL, usuario, etc.';
COMMENT ON COLUMN nna_contacto_adicional.descripcion      IS 'Nota libre adicional (ej: "Facebook de la abuela materna").';

-- ----------------------------------------------------------
--  6F. NNA ↔ ENFERMEDAD
-- ----------------------------------------------------------
CREATE TABLE nna_enfermedad (
    id_nna              INT     NOT NULL REFERENCES nna(id_nna)                   ON DELETE CASCADE,
    id_enfermedad       INT     NOT NULL REFERENCES cat_enfermedad(id_enfermedad)  ON DELETE RESTRICT,
    bajo_tratamiento    BOOLEAN NOT NULL DEFAULT FALSE,
    observaciones       TEXT,

    PRIMARY KEY (id_nna, id_enfermedad)
);

COMMENT ON TABLE  nna_enfermedad                  IS 'Relación N:M entre NNA y enfermedades diagnosticadas. Vincula con cat_enfermedad (CIE-10).';
COMMENT ON COLUMN nna_enfermedad.bajo_tratamiento IS 'TRUE si el NNA está recibiendo tratamiento activo para esta enfermedad.';
COMMENT ON COLUMN nna_enfermedad.observaciones    IS 'Notas clínicas adicionales específicas del vínculo NNA-enfermedad.';


-- ============================================================
--  FASE 7: SEGUIMIENTO MULTIDISCIPLINARIO
-- ============================================================

CREATE TABLE expediente_seguimiento (
    id_seguimiento       UUID      PRIMARY KEY DEFAULT uuid_generate_v4(),
    id_nna               INT       NOT NULL REFERENCES nna(id_nna)                  ON DELETE CASCADE,
    id_usuario           UUID      NOT NULL REFERENCES usuario_sistema(id_usuario)   ON DELETE RESTRICT,
    id_area_atencion     INT       NOT NULL REFERENCES cat_rol_sistema(id)           ON DELETE RESTRICT,
    fecha_atencion       TIMESTAMP NOT NULL DEFAULT NOW(),
    notas_evolucion      TEXT,
    archivo_adjunto_path VARCHAR(500),

    CONSTRAINT chk_archivo_path CHECK (
        archivo_adjunto_path IS NULL
        OR archivo_adjunto_path ~ '^[a-zA-Z0-9_./\-]+$'
    )
);

COMMENT ON TABLE  expediente_seguimiento                      IS 'Registro cronológico de intervenciones multidisciplinarias sobre un NNA.';
COMMENT ON COLUMN expediente_seguimiento.id_area_atencion     IS 'FK a cat_rol_sistema. Disciplina desde la que se realiza la intervención.';
COMMENT ON COLUMN expediente_seguimiento.archivo_adjunto_path IS 'Ruta relativa al archivo adjunto. Validar permisos en capa PHP.';


-- ============================================================
--  FASE 8: DATOS OPERATIVOS INICIALES
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
CREATE INDEX idx_usuario_rol        ON usuario_sistema(id_rol);
CREATE INDEX idx_usuario_estado     ON usuario_sistema(estado);
CREATE INDEX idx_usuario_mun_lab    ON usuario_sistema(id_municipio_labora);

-- nna
CREATE INDEX idx_nna_sexo           ON nna(id_sexo);
CREATE INDEX idx_nna_escolaridad    ON nna(id_escolaridad);
CREATE INDEX idx_nna_motivo         ON nna(id_motivo_ingreso);
CREATE INDEX idx_nna_dir_actual     ON nna(dir_actual);
CREATE INDEX idx_nna_luga_nac       ON nna(luga_nac_nna);
CREATE INDEX idx_nna_fecha_nac      ON nna(fecha_nacimiento);
CREATE INDEX idx_nna_vulnerabilidad ON nna(situacion_calle, es_migrante, es_refugiado, poblacion_indigena);

-- tablas puente
CREATE INDEX idx_nna_tutor_tutor    ON nna_tutor(id_tutor);
CREATE INDEX idx_nna_tutor_parent   ON nna_tutor(id_parentesco);
CREATE INDEX idx_nna_nac_pais       ON nna_nacionalidad(id_pais);
CREATE INDEX idx_nna_lengua_len     ON nna_lengua(id_lengua);
CREATE INDEX idx_nna_enf_enf        ON nna_enfermedad(id_enfermedad);
CREATE INDEX idx_nna_cont_tipo      ON nna_contacto_adicional(id_tipo_contacto);
CREATE INDEX idx_nna_cont_nna       ON nna_contacto_adicional(id_nna);

-- expediente_seguimiento
CREATE INDEX idx_exp_nna            ON expediente_seguimiento(id_nna);
CREATE INDEX idx_exp_usuario        ON expediente_seguimiento(id_usuario);
CREATE INDEX idx_exp_fecha          ON expediente_seguimiento(fecha_atencion DESC);
CREATE INDEX idx_exp_area           ON expediente_seguimiento(id_area_atencion);

-- geografía
CREATE INDEX idx_dir_municipio      ON direccion(id_municipio);
CREATE INDEX idx_dir_cp             ON direccion(codigo_postal);
CREATE INDEX idx_municipio_ent      ON cat_municipio(id_ent);


-- ============================================================
--  RESUMEN DE RELACIONES FNBC — 24 TABLAS (v6.1)
-- ============================================================
--
--  CATÁLOGOS (12)
--    cat_rol_sistema        ( id, nombre )
--    cat_sexo               ( id, nombre )
--    cat_tipo_discapacidad  ( id, nombre )
--    cat_grado_dependencia  ( id, nombre )
--    cat_nivel_competencia  ( id, nombre )
--    cat_parentesco         ( id, nombre )
--    cat_lengua             ( id, nombre )
--    cat_pais               ( id, nombre )
--    cat_escolaridad        ( id, nombre )
--    cat_motivo_ingreso     ( id, nombre )
--    cat_enfermedad         ( id_enfermedad, codigo_cie, nombre )
--    cat_tipo_contacto *v6.1*( id, nombre )
--
--  GEOGRAFÍA HÍBRIDA (3)  — INTACTO DESDE v5
--    entidad_federativa     ( id_ent, nom_ent )
--    cat_municipio          ( id_municipio, nom_mun, id_ent )
--    direccion              ( id_dir, calle_dir, no_ext_dir, no_int_dir,
--                             ref_dir, colonia_abierta, codigo_postal,
--                             id_municipio )
--
--  PLATAFORMA (1)  — INTACTO DESDE v5
--    usuario_sistema        ( id_usuario, curp, rfc, nombre, apellido_paterno,
--                             apellido_materno, correo, contrasena,
--                             id_rol, id_municipio_labora,
--                             estado, fecha_registro )
--
--  ENTIDADES CENTRALES (2)
--    tutor                  ( id_tutor, curp_tutor, nombre, primer_apellido,
--                             segundo_apellido, telefono, correo, es_adulto_mayor )
--    nna                    ( id_nna, folio_nna, nombre, prim_ap, seg_ap,
--                             fecha_nacimiento, curp, id_sexo,
--                             id_escolaridad, id_motivo_ingreso,
--                             dir_actual, luga_nac_nna,
--                             situacion_calle, es_migrante, es_refugiado,
--                             poblacion_indigena, fecha_registro, registrado_por )
--
--  MULTIVALORADAS (6)
--    nna_tutor              ( id_nna, id_tutor, id_parentesco,
--                             es_contacto_ppal, fecha_vinculacion )
--    nna_nacionalidad       ( id_nna, id_pais )
--    nna_discapacidad       ( id_nna, id_tipo_discapacidad, id_grado_dependencia,
--                             diagnostico_medico_oficial, descripcion_adicional )
--    nna_lengua             ( id_nna, id_lengua, es_preferente,
--                             id_nivel_competencia, requiere_interprete )
--    nna_contacto_adicional *v6.1*
--                           ( id_contacto, id_nna, id_tipo_contacto,
--                             valor_contacto, descripcion )
--    nna_enfermedad         ( id_nna, id_enfermedad,
--                             bajo_tratamiento, observaciones )
--
--  SEGUIMIENTO (1)
--    expediente_seguimiento ( id_seguimiento, id_nna, id_usuario,
--                             id_area_atencion, fecha_atencion,
--                             notas_evolucion, archivo_adjunto_path )
--
-- ============================================================
--  FIN DEL SCRIPT — PROYECTO AURORA v6.1 · FNBC · GEO HÍBRIDO · FUD/LGDNNA
-- ============================================================