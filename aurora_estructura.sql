-- ============================================================
--  PROYECTO AURORA — SCRIPT UNIFICADO
-- ============================================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================================
--  CATÁLOGOS Y DATOS POR DEFECTO
-- ============================================================

CREATE TABLE cat_rol_sistema (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_rol_sistema (nombre) VALUES
    ('Administrador'),('Psicologo'),('Medico'),('Trabajador_Social'),('Abogado');
COMMENT ON TABLE cat_rol_sistema IS 'Roles operativos del personal.';

CREATE TABLE cat_sexo (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_sexo (nombre) VALUES ('Hombre'),('Mujer'),('Indeterminado');
COMMENT ON TABLE cat_sexo IS 'Sexo registral conforme al FUD/LGDNNA.';

CREATE TABLE cat_tipo_discapacidad (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_tipo_discapacidad (nombre) VALUES
    ('Física'),('Intelectual'),('Sensorial'),('Mental'),('Múltiple'),('Síndrome');
COMMENT ON TABLE cat_tipo_discapacidad IS 'Tipos de discapacidad y síndromes.';

CREATE TABLE cat_grado_dependencia (
    id          SERIAL          PRIMARY KEY,
    nombre      VARCHAR(100)    NOT NULL UNIQUE,
    descripcion TEXT
);
INSERT INTO cat_grado_dependencia (nombre, descripcion) VALUES
    ('Independiente', 'Realiza todas las actividades sin asistencia de terceros.'),
    ('Requiere Apoyo Moderado', 'Necesita asistencia parcial en algunas actividades cotidianas.'),
    ('Requiere Apoyo Total', 'Depende completamente de un tercero para actividades básicas.');
COMMENT ON TABLE cat_grado_dependencia IS 'Grados de dependencia funcional.';

CREATE TABLE cat_nivel_competencia (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_nivel_competencia (nombre) VALUES
    ('Básico'),('Intermedio'),('Avanzado'),('Nativo / Lengua Materna'),
    ('A1 - Acceso'),('A2 - Plataforma'),('B1 - Umbral'),('B2 - Avanzado'),
    ('C1 - Dominio Operativo Eficaz'),('C2 - Maestría'),('No evaluado');
COMMENT ON TABLE cat_nivel_competencia IS 'Niveles de competencia lingüística.';

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

CREATE TABLE cat_lengua (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_lengua (nombre) VALUES
    ('Español'),('Lengua de Señas Mexicana (LSM)'),('Inglés'),('Francés'),('Francés Criollo Haitiano'),
    ('Portugués'),('Árabe'),('Mandarin'),('Ruso'),('Alemán'),('Italiano'),
    ('Akateko'),('Amuzgo'),('Awakateko'),('Ayapaneco'),('Chatino'),('Chichimeca Jonaz'),('Chinanteco'),('Chocholteco'),('Chol'),('Chontal de Oaxaca'),('Chontal de Tabasco'),
    ('Chuj'),('Cochimi'),('Cora'),('Cuicateco'),('Guarijio'),('Huave'),('Huichol'),('Ixcateco'),('Ixil'),('Jacalteko'),('Kaqchikel'),('Kickapoo'),('Kiche'),
    ('Kiliwa'),('Kumiai'),('Lacandon'),('Mam'),('Mateo'),('Matlatzinca'),('Maya Yucateco'),('Mazahua'),('Mazateco'),('Mixe'),('Mixteco'),('Nahuatl'),('Oluteco'),
    ('Opata'),('Otomi'),('Paipai'),('Pame'),('Papago'),('Pima'),('Popoloca'),('Popoluca'),('Popoluca de la Sierra'),('Qanjobal'),('Qeqchi'),('Qatok'),
    ('Sakapulteko'),('Sayulteco'),('Seri'),('Sipakapense'),('Tarahumara'),('Tarasco Purepecha'),('Teko'),('Tektiteko'),('Tepehua'),('Tepehuano del Norte'),('Tepehuano del Sur'),
    ('Texistepequeño'),('Tlapaneco Mephaa'),('Tlahuica'),('Totonaco'),('Triqui'),('Tseltal'),('Tsotsil'),('Uspanteko'),('Yaqui'),('Zapoteco'),('Zoque'),
    ('Mam guatemalteco'),('Kiche guatemalteco'),('Kaqchikel guatemalteco'),('Garífuna'),('Otra');

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

CREATE TABLE cat_escolaridad (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_escolaridad (nombre) VALUES
    ('Sin escolaridad'),('Preescolar'),('Preescolar Incompleto'),('Primaria Incompleta'),('Primaria Completa'),
    ('Secundaria Incompleta'),('Secundaria Completa'),('Bachillerato o Preparatoria Incompleta'),
    ('Bachillerato o Preparatoria Completa'),('Carrera Técnica o Vocacional'),('Licenciatura Incompleta'),
    ('Licenciatura Completa'),('Educación Especial'),('Centro de Atención Múltiple (CAM)'),
    ('Educación para Adultos (INEA)'),('Primaria para Adultos (INEA)'),('Secundaria para Adultos (INEA / Telesecundaria)'),
    ('Rezago Educativo — Analfabeta'),('Rezago Educativo — Sin Primaria Completa'),('Rezago Educativo — Sin Secundaria Completa'),
    ('No aplica / Menor de edad sin escolaridad formal'),('Desconocido');

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

CREATE TABLE cat_enfermedad (
    id_enfermedad   SERIAL          PRIMARY KEY,
    codigo_cie      VARCHAR(10)     UNIQUE,
    nombre          VARCHAR(255)    NOT NULL UNIQUE,
    tipo            VARCHAR(50)     NOT NULL 
);

CREATE TEMP TABLE temp_cat (codigo_cie varchar(10), nombre varchar(255), tipo varchar(50));
\copy temp_cat(codigo_cie, nombre, tipo) FROM '/var/www/html/Aurora/catalogo_de_enfermedades.txt' DELIMITER ',' CSV;
INSERT INTO cat_enfermedad (codigo_cie, nombre, tipo) 
SELECT codigo_cie, nombre, tipo FROM temp_cat 
ON CONFLICT (nombre) DO NOTHING;

CREATE TABLE cat_tipo_contacto (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_tipo_contacto (nombre) VALUES
    ('Teléfono fijo'),('Celular'),('Correo'),('Instagram'),('Facebook'),('LinkedIn'),('Telegram'),
    ('Caseta Comunitaria'),('Red Vecinal / Autoridad Local'),('Teléfono de Albergue / Refugio'),('Enlace Institucional TS');

CREATE TABLE cat_grupo_sanguineo (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(20)     NOT NULL UNIQUE
);
INSERT INTO cat_grupo_sanguineo (nombre) VALUES
    ('A+'),('A-'),('B+'),('B-'),('AB+'),('AB-'),('O+'),('O-'),('Bombay'),('Rh nulo'),('Desconocido');

CREATE TABLE cat_tipo_visita (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_tipo_visita (nombre) VALUES
    ('Visita domiciliaria'),
    ('Valoración médica'),
    ('Valoración psicológica'),
    ('Comparecencia / Diligencia legal'),
    ('Audiencia judicial'),
    ('Entrevista a tutor o familiar'),
    ('Visita escolar'),
    ('Canalización a institución'),
    ('Seguimiento de caso'),
    ('Verificación de medida de protección'),
    ('Visita a albergue / Centro de Asistencia'),
    ('Otra');

-- ============================================================
--  GEOGRAFÍA
-- ============================================================

CREATE TABLE entidad_federativa (
    id_ent      SERIAL          PRIMARY KEY,
    nom_ent     VARCHAR(200)    NOT NULL UNIQUE
);
INSERT INTO entidad_federativa (nom_ent) VALUES
    ('Aguascalientes'),('Baja California'),('Baja California Sur'),('Campeche'),('Chiapas'),
    ('Chihuahua'),('Ciudad de México'),('Coahuila'),('Colima'),('Durango'),
    ('Guanajuato'),('Guerrero'),('Hidalgo'),('Jalisco'),('México'),
    ('Michoacán'),('Morelos'),('Nayarit'),('Nuevo León'),('Oaxaca'),
    ('Puebla'),('Querétaro'),('Quintana Roo'),('San Luis Potosí'),('Sinaloa'),
    ('Sonora'),('Tabasco'),('Tamaulipas'),('Tlaxcala'),('Veracruz'),
    ('Yucatán'),('Zacatecas');

CREATE TABLE cat_municipio (
    id_municipio    SERIAL          PRIMARY KEY,
    nom_mun         VARCHAR(200)    NOT NULL,
    id_ent          INT             NOT NULL REFERENCES entidad_federativa(id_ent) ON DELETE RESTRICT,
    CONSTRAINT uq_municipio_ent UNIQUE (nom_mun, id_ent)
);
CREATE INDEX idx_municipio_ent ON cat_municipio(id_ent);

INSERT INTO cat_municipio (nom_mun, id_ent) VALUES
    ('Aguascalientes',1),('Jesús María',1),('Calvillo',1),
    ('Tijuana',2),('Mexicali',2),('Ensenada',2),
    ('Tuxtla Gutiérrez',5),('San Cristóbal de las Casas',5),('Tapachula',5),('Comitán de Domínguez',5),
    ('Chihuahua',6),('Ciudad Juárez',6),('Delicias',6),
    ('Álvaro Obregón',7),('Benito Juárez',7),('Cuauhtémoc',7),('Gustavo A. Madero',7),('Iztapalapa',7),
    ('Miguel Hidalgo',7),('Tlalpan',7),('Xochimilco',7),
    ('Azcapotzalco',7),('Coyoacán',7),('Cuajimalpa de Morelos',7),('Iztacalco',7),
    ('La Magdalena Contreras',7),('Milpa Alta',7),('Tláhuac',7),('Venustiano Carranza',7),
    ('Guadalajara',14),('Zapopan',14),('Tlaquepaque',14),('Puerto Vallarta',14),
    ('Ecatepec de Morelos',15),('Nezahualcóyotl',15),('Toluca',15),('Naucalpan de Juárez',15),('Tlalnepantla de Baz',15),
    ('Monterrey',19),('San Nicolás de los Garza',19),('Guadalupe',19),('Apodaca',19),
    ('Oaxaca de Juárez',20),('San Juan Bautista Tuxtepec',20),('Juchitán de Zaragoza',20),
    ('Puebla',21),('Tehuacán',21),('San Martín Texmelucan',21),
    ('Hermosillo',26),('Nogales',26),('Ciudad Obregón',26),
    ('Centro',27),('Cárdenas',27),('Comalcalco',27),
    ('Veracruz',30),('Xalapa',30),('Coatzacoalcos',30),('Córdoba',30),
    ('Mérida',31),('Valladolid',31),('Progreso',31);

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
CREATE INDEX idx_dir_municipio ON direccion(id_municipio);
CREATE INDEX idx_dir_cp        ON direccion(codigo_postal);

-- ============================================================
--  EQUIPOS MULTIDISCIPLINARIOS
-- ============================================================

CREATE TABLE equipo (
    id_equipo       SERIAL          PRIMARY KEY,
    nombre_equipo   VARCHAR(150)    NOT NULL UNIQUE,
    descripcion     TEXT,
    estado          VARCHAR(20)     NOT NULL DEFAULT 'ACTIVO',
    fecha_creacion  TIMESTAMP       NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_estado_equipo CHECK (estado IN ('ACTIVO', 'INACTIVO'))
);

INSERT INTO equipo (nombre_equipo, descripcion, estado) VALUES
    ('Equipo Alfa',  'Equipo multidisciplinario de atención general.',          'ACTIVO'),
    ('Equipo Beta',  'Equipo enfocado en casos de migración y refugio.',        'ACTIVO'),
    ('Equipo Gamma', 'Equipo de atención a casos de salud y discapacidad.',     'ACTIVO');

-- ============================================================
--  OPERACIÓN DE LA PLATAFORMA
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
    id_equipo               INT             REFERENCES equipo(id_equipo)                ON DELETE SET NULL,
    estado                  VARCHAR(20)     NOT NULL DEFAULT 'ACTIVO',
    fecha_registro          TIMESTAMP       NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_curp_usuario   CHECK (LENGTH(TRIM(curp)) = 18),
    CONSTRAINT chk_correo_usuario CHECK (correo LIKE '%@%.%'),
    CONSTRAINT chk_estado_usuario CHECK (estado IN ('ACTIVO', 'INACTIVO', 'SUSPENDIDO'))
);

-- ============================================================
--  ENTIDADES CENTRALES
-- ============================================================

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

CREATE TABLE nna (
    id_nna              SERIAL          PRIMARY KEY,
    folio_nna           VARCHAR(50)     NOT NULL UNIQUE,
    nombre              VARCHAR(100)    NOT NULL,
    apodo               VARCHAR(100),
    prim_ap             VARCHAR(100)    NOT NULL,
    seg_ap              VARCHAR(100),
    fecha_nacimiento    DATE            NOT NULL,
    curp                VARCHAR(18)     UNIQUE,
    id_sexo             INT             NOT NULL REFERENCES cat_sexo(id)              ON DELETE RESTRICT,
    id_escolaridad      INT             REFERENCES cat_escolaridad(id)                ON DELETE RESTRICT,
    id_motivo_ingreso   INT             REFERENCES cat_motivo_ingreso(id)             ON DELETE RESTRICT,
    id_grupo_sanguineo  INT             REFERENCES cat_grupo_sanguineo(id)            ON DELETE RESTRICT,
    id_equipo           INT             REFERENCES equipo(id_equipo)                  ON DELETE SET NULL,
    dir_actual          INT             UNIQUE REFERENCES direccion(id_dir)            ON DELETE SET NULL,
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

-- ============================================================
--  TRIGGER DE PERMISOS
-- ============================================================

CREATE OR REPLACE FUNCTION fn_verificar_rol_trabajador_social()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    v_rol_nombre VARCHAR(100);
BEGIN
    IF NEW.registrado_por IS NULL THEN
        RETURN NEW;
    END IF;

    IF TG_OP = 'UPDATE' AND OLD.registrado_por IS NOT DISTINCT FROM NEW.registrado_por THEN
        RETURN NEW;
    END IF;

    SELECT crs.nombre
    INTO   v_rol_nombre
    FROM   usuario_sistema  us
    JOIN   cat_rol_sistema  crs ON crs.id = us.id_rol
    WHERE  us.id_usuario = NEW.registrado_por
      AND  us.estado     = 'ACTIVO';

    IF v_rol_nombre IS NULL THEN
        RAISE EXCEPTION
            'AURORA-001: El usuario con id % no existe o no está ACTIVO en usuario_sistema.',
            NEW.registrado_por
            USING ERRCODE = 'P0001';
    END IF;

    IF v_rol_nombre <> 'Trabajador_Social' THEN
        RAISE EXCEPTION
            'AURORA-002: Solo un usuario con rol Trabajador_Social puede registrar NNA. Rol detectado: %.',
            v_rol_nombre
            USING ERRCODE = 'P0001';
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_nna_rol_registrador
    BEFORE INSERT OR UPDATE OF registrado_por
    ON nna
    FOR EACH ROW
    EXECUTE FUNCTION fn_verificar_rol_trabajador_social();

-- ============================================================
--  RELACIONES Y LISTAS MULTIVALORADAS
-- ============================================================

CREATE TABLE nna_tutor (
    id_nna              INT     NOT NULL REFERENCES nna(id_nna)        ON DELETE CASCADE,
    id_tutor            INT     NOT NULL REFERENCES tutor(id_tutor)    ON DELETE CASCADE,
    id_parentesco       INT     NOT NULL REFERENCES cat_parentesco(id) ON DELETE RESTRICT,
    es_contacto_ppal    BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_vinculacion   DATE,
    PRIMARY KEY (id_nna, id_tutor)
);

CREATE TABLE nna_nacionalidad (
    id_nna      INT     NOT NULL REFERENCES nna(id_nna)  ON DELETE CASCADE,
    id_pais     INT     NOT NULL REFERENCES cat_pais(id) ON DELETE RESTRICT,
    PRIMARY KEY (id_nna, id_pais)
);

CREATE TABLE nna_discapacidad (
    id_nna                     INT     NOT NULL REFERENCES nna(id_nna)               ON DELETE CASCADE,
    id_tipo_discapacidad       INT     NOT NULL REFERENCES cat_tipo_discapacidad(id)  ON DELETE RESTRICT,
    id_grado_dependencia       INT     NOT NULL REFERENCES cat_grado_dependencia(id)  ON DELETE RESTRICT,
    diagnostico_medico_oficial BOOLEAN NOT NULL DEFAULT FALSE,
    descripcion_adicional      TEXT,
    bajo_tratamiento           BOOLEAN NOT NULL DEFAULT FALSE,
    medicamento_actual         TEXT,
    PRIMARY KEY (id_nna, id_tipo_discapacidad)
);

CREATE TABLE tutor_discapacidad (
    id_tutor                   INT     NOT NULL REFERENCES tutor(id_tutor)            ON DELETE CASCADE,
    id_tipo_discapacidad       INT     NOT NULL REFERENCES cat_tipo_discapacidad(id)  ON DELETE RESTRICT,
    id_grado_dependencia       INT     NOT NULL REFERENCES cat_grado_dependencia(id)  ON DELETE RESTRICT,
    diagnostico_medico_oficial BOOLEAN NOT NULL DEFAULT FALSE,
    descripcion_adicional      TEXT,
    bajo_tratamiento           BOOLEAN NOT NULL DEFAULT FALSE,
    medicamento_actual         TEXT,
    PRIMARY KEY (id_tutor, id_tipo_discapacidad)
);

CREATE TABLE nna_lengua (
    id_nna               INT         NOT NULL REFERENCES nna(id_nna)               ON DELETE CASCADE,
    id_lengua            INT         NOT NULL REFERENCES cat_lengua(id)            ON DELETE RESTRICT,
    es_preferente        BOOLEAN     NOT NULL DEFAULT FALSE,
    id_nivel_competencia INT         NOT NULL REFERENCES cat_nivel_competencia(id) ON DELETE RESTRICT,
    requiere_interprete  BOOLEAN     NOT NULL DEFAULT FALSE,
    variante_indigena    VARCHAR(150),
    autodenominacion     VARCHAR(150),
    modo_adquisicion     VARCHAR(100),
    PRIMARY KEY (id_nna, id_lengua)
);

CREATE TABLE tutor_lengua (
    id_tutor             INT         NOT NULL REFERENCES tutor(id_tutor)           ON DELETE CASCADE,
    id_lengua            INT         NOT NULL REFERENCES cat_lengua(id)            ON DELETE RESTRICT,
    es_preferente        BOOLEAN     NOT NULL DEFAULT FALSE,
    id_nivel_competencia INT         NOT NULL REFERENCES cat_nivel_competencia(id) ON DELETE RESTRICT,
    requiere_interprete  BOOLEAN     NOT NULL DEFAULT FALSE, 
    variante_indigena    VARCHAR(150),
    autodenominacion     VARCHAR(150),
    modo_adquisicion     VARCHAR(100),
    PRIMARY KEY (id_tutor, id_lengua)
);

CREATE TABLE usuario_lengua (
    id_usuario           UUID        NOT NULL REFERENCES usuario_sistema(id_usuario) ON DELETE CASCADE,
    id_lengua            INT         NOT NULL REFERENCES cat_lengua(id)              ON DELETE RESTRICT,
    es_preferente        BOOLEAN     NOT NULL DEFAULT FALSE,
    id_nivel_competencia INT         NOT NULL REFERENCES cat_nivel_competencia(id)   ON DELETE RESTRICT,
    requiere_interprete  BOOLEAN     NOT NULL DEFAULT FALSE, 
    variante_indigena    VARCHAR(150),
    autodenominacion     VARCHAR(150),
    modo_adquisicion     VARCHAR(100),
    PRIMARY KEY (id_usuario, id_lengua)
);

CREATE TABLE nna_contacto_adicional (
    id_contacto         SERIAL       PRIMARY KEY,
    id_nna              INT          NOT NULL REFERENCES nna(id_nna)           ON DELETE CASCADE,
    id_tipo_contacto    INT          NOT NULL REFERENCES cat_tipo_contacto(id) ON DELETE RESTRICT,
    valor_contacto      VARCHAR(255) NOT NULL,
    descripcion         VARCHAR(255),
    CONSTRAINT uq_nna_contacto UNIQUE (id_nna, id_tipo_contacto, valor_contacto)
);

CREATE TABLE nna_enfermedad (
    id_nna              INT     NOT NULL REFERENCES nna(id_nna)                   ON DELETE CASCADE,
    id_enfermedad       INT     NOT NULL REFERENCES cat_enfermedad(id_enfermedad)  ON DELETE RESTRICT,
    bajo_tratamiento    BOOLEAN NOT NULL DEFAULT FALSE,
    observaciones       TEXT,
    PRIMARY KEY (id_nna, id_enfermedad)
);

CREATE TABLE tutor_enfermedad (
    id_tutor            INT     NOT NULL REFERENCES tutor(id_tutor)               ON DELETE CASCADE,
    id_enfermedad       INT     NOT NULL REFERENCES cat_enfermedad(id_enfermedad) ON DELETE RESTRICT,
    bajo_tratamiento    BOOLEAN NOT NULL DEFAULT FALSE,
    observaciones       TEXT,
    PRIMARY KEY (id_tutor, id_enfermedad)
);

-- ============================================================
--  SEGUIMIENTO MULTIDISCIPLINARIO
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
        archivo_adjunto_path IS NULL OR archivo_adjunto_path ~ '^[a-zA-Z0-9_./\-]+$'
    )
);

CREATE TABLE visita_seguimiento (
    id_visita        UUID        PRIMARY KEY DEFAULT uuid_generate_v4(),
    id_nna           INT         NOT NULL REFERENCES nna(id_nna)                ON DELETE CASCADE,
    id_usuario       UUID        NOT NULL REFERENCES usuario_sistema(id_usuario) ON DELETE RESTRICT,
    id_rol_ejecutor  INT         NOT NULL REFERENCES cat_rol_sistema(id)         ON DELETE RESTRICT,
    id_tipo_visita   INT         NOT NULL REFERENCES cat_tipo_visita(id)         ON DELETE RESTRICT,
    lugar_visita     VARCHAR(300),
    fecha_programada TIMESTAMP,
    fecha_realizada  TIMESTAMP,
    estado_visita    VARCHAR(20) NOT NULL DEFAULT 'PROGRAMADA',
    objetivo         TEXT,
    resultado        TEXT,

    CONSTRAINT chk_estado_visita CHECK (estado_visita IN ('PROGRAMADA','REALIZADA','CANCELADA'))
);

-- ============================================================
--  DATOS OPERATIVOS INICIALES
-- ============================================================

UPDATE usuario_sistema
SET    estado = 'INACTIVO'
WHERE  id_rol = (SELECT id FROM cat_rol_sistema WHERE nombre = 'Trabajador_Social');

INSERT INTO usuario_sistema (
    curp, rfc, nombre, apellido_paterno, apellido_materno,
    correo, contrasena, id_rol, estado
) VALUES (
    'XEXX010101HNEXXXA4','XEXX010101000','Administrador','Aurora','Sistema',
    'luis@aurora.com','Admin2024',
    (SELECT id FROM cat_rol_sistema WHERE nombre = 'Administrador'),
    'ACTIVO'
);

-- ============================================================
--  ÍNDICES DE RENDIMIENTO
-- ============================================================

CREATE INDEX idx_usuario_rol        ON usuario_sistema(id_rol);
CREATE INDEX idx_usuario_estado     ON usuario_sistema(estado);
CREATE INDEX idx_usuario_mun_lab    ON usuario_sistema(id_municipio_labora);
CREATE INDEX idx_usuario_equipo     ON usuario_sistema(id_equipo);

CREATE INDEX idx_nna_sexo           ON nna(id_sexo);
CREATE INDEX idx_nna_escolaridad    ON nna(id_escolaridad);
CREATE INDEX idx_nna_motivo         ON nna(id_motivo_ingreso);
CREATE INDEX idx_nna_sangre         ON nna(id_grupo_sanguineo);
CREATE INDEX idx_nna_equipo         ON nna(id_equipo);
CREATE INDEX idx_nna_luga_nac       ON nna(luga_nac_nna);
CREATE INDEX idx_nna_fecha_nac      ON nna(fecha_nacimiento);
CREATE INDEX idx_nna_vulnerabilidad ON nna(situacion_calle, es_migrante, es_refugiado, poblacion_indigena);
CREATE INDEX idx_nna_registrado_por ON nna(registrado_por);

CREATE INDEX idx_nna_tutor_tutor    ON nna_tutor(id_tutor);
CREATE INDEX idx_nna_tutor_parent   ON nna_tutor(id_parentesco);
CREATE INDEX idx_nna_nac_pais       ON nna_nacionalidad(id_pais);

CREATE INDEX idx_nna_lengua_len     ON nna_lengua(id_lengua);
CREATE INDEX idx_tutor_lengua_len   ON tutor_lengua(id_lengua);
CREATE INDEX idx_usr_lengua_len     ON usuario_lengua(id_lengua);

CREATE INDEX idx_nna_disc_tipo      ON nna_discapacidad(id_tipo_discapacidad);
CREATE INDEX idx_tutor_disc_tipo    ON tutor_discapacidad(id_tipo_discapacidad);

CREATE INDEX idx_nna_enf_enf        ON nna_enfermedad(id_enfermedad);
CREATE INDEX idx_nna_cont_tipo      ON nna_contacto_adicional(id_tipo_contacto);
CREATE INDEX idx_nna_cont_nna       ON nna_contacto_adicional(id_nna);

CREATE INDEX idx_exp_nna            ON expediente_seguimiento(id_nna);
CREATE INDEX idx_exp_usuario        ON expediente_seguimiento(id_usuario);
CREATE INDEX idx_exp_fecha          ON expediente_seguimiento(fecha_atencion DESC);
CREATE INDEX idx_exp_area           ON expediente_seguimiento(id_area_atencion);

CREATE INDEX idx_visita_nna         ON visita_seguimiento(id_nna);
CREATE INDEX idx_visita_usuario     ON visita_seguimiento(id_usuario);
CREATE INDEX idx_visita_tipo        ON visita_seguimiento(id_tipo_visita);
CREATE INDEX idx_visita_estado      ON visita_seguimiento(estado_visita);
CREATE INDEX idx_visita_fecha_prog  ON visita_seguimiento(fecha_programada);