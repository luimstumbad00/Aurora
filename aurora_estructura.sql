-- ============================================================
--  PROYECTO AURORA — SCRIPT UNIFICADO Y DEFINITIVO v8 (FNBC)
--  PostgreSQL | Normalización: 1FN ✓  2FN ✓  3FN ✓  FNBC ✓
--  34 relaciones · Equipos Multidisciplinarios · Seguimiento de Visitas
--  Lenguas universales · Discapacidades/Síndromes ampliadas · Trigger de permisos
-- ============================================================
--
--  CAMBIOS RESPECTO A v7:
--  [REF] nna.dir_actual        → UNIQUE constraint → relación 1:1 estricta con direccion
--  [REF] nna                   → +apodo VARCHAR(100) para construcción de confianza
--  [REF] nna_lengua            → +variante_indigena, +autodenominacion, +modo_adquisicion
--                                  (requiere_interprete eliminado: absorbido por modo/variante)
--  [NEW] tutor_lengua          → espejo de nna_lengua para tutores
--  [NEW] usuario_lengua        → espejo de nna_lengua para usuarios del sistema
--  [REF] cat_tipo_discapacidad → +INSERT 'Síndrome' (síndromes tratados como discapacidad)
--  [REF] cat_grado_dependencia → +descripcion TEXT
--  [REF] nna_discapacidad      → +bajo_tratamiento BOOLEAN, +medicamento_actual TEXT
--  [NEW] tutor_discapacidad    → espejo de nna_discapacidad para tutores
--  [NEW] fn_verificar_rol_trabajador_social()  → función de validación de rol
--  [NEW] trg_nna_rol_registrador               → TRIGGER BEFORE INSERT OR UPDATE en nna
--
--  JUSTIFICACIÓN FNBC DE LOS CAMBIOS:
--  · nna.dir_actual UNIQUE: la FK sigue dependiendo solo de id_nna; el UNIQUE
--    no introduce nuevas dependencias funcionales → FNBC se mantiene.
--  · nna.apodo: atributo simple que depende únicamente de id_nna → FNBC trivial.
--  · Nuevos atributos en *_lengua: todos dependen de la PK compuesta (id_entidad, id_lengua)
--    porque describen la relación específica entre esa entidad y esa lengua → FNBC.
--  · requiere_interprete eliminado de nna_lengua: era transitivamente inferible de
--    id_nivel_competencia en muchos casos. Se puede derivar en la capa de aplicación.
--    Su eliminación mejora la calidad de la normalización.
--  · tutor_lengua / usuario_lengua: tablas puente simétricas. Evita súper-tablas
--    polimórficas que generarían FKs anulables y violarían el principio de entidad única.
--  · tutor_discapacidad: misma lógica que tutor_lengua. PK compuesta (id_tutor,
--    id_tipo_discapacidad); todos los atributos dependen de ella → FNBC.
--  · bajo_tratamiento / medicamento_actual en nna_discapacidad: dependen de la PK
--    compuesta (id_nna, id_tipo_discapacidad) → sin violación de FNBC.
--  · cat_grado_dependencia.descripcion: depende solo de id (PK) → FNBC trivial.
--  · Trigger: lógica de negocio a nivel motor. No altera la estructura relacional
--    ni introduce dependencias funcionales nuevas → neutral para FNBC.
--
--  HISTORIAL:
--  v6.4 → FIX cat_grupo_sanguineo VARCHAR(20) + admin + datos extra
--  v7   → Equipos multidisciplinarios + visitas
--  v8   → 1:1 dirección · apodo · lenguas universales · discapacidades ampliadas
--          · síndromes · trigger permisos TS
-- ============================================================


-- ============================================================
--  FASE 1: EXTENSIONES
-- ============================================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";


-- ============================================================
--  FASE 2: TABLAS DE CATÁLOGO + DATOS POR DEFECTO
-- ============================================================

CREATE TABLE cat_rol_sistema (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_rol_sistema (nombre) VALUES
    ('Administrador'),('Psicologo'),('Medico'),('Trabajador_Social'),('Abogado');
COMMENT ON TABLE cat_rol_sistema IS 'Catálogo de roles operativos del personal en la plataforma Aurora.';

CREATE TABLE cat_sexo (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_sexo (nombre) VALUES ('Hombre'),('Mujer'),('Indeterminado');
COMMENT ON TABLE cat_sexo IS 'Catálogo de sexo registral conforme al FUD/LGDNNA.';

CREATE TABLE cat_tipo_discapacidad (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_tipo_discapacidad (nombre) VALUES
    ('Física'),('Intelectual'),('Sensorial'),('Mental'),('Múltiple'),
    -- v8: los síndromes se tratan conceptualmente como discapacidades.
    -- Se agregan como categoría propia para permitir especificidad en el campo
    -- descripcion_adicional de las tablas asociativas, sin requerir un catálogo
    -- de síndromes independiente (FNBC: {id}→{nombre}, sin dependencias parciales).
    ('Síndrome');
COMMENT ON TABLE cat_tipo_discapacidad IS 'Catálogo de tipos de discapacidad según clasificación gubernamental. v8: incluye "Síndrome" como categoría, cuya especificidad se registra en descripcion_adicional de la tabla asociativa.';

CREATE TABLE cat_grado_dependencia (
    id          SERIAL          PRIMARY KEY,
    nombre      VARCHAR(100)    NOT NULL UNIQUE,
    -- v8: descripción narrativa del grado de dependencia para orientar al profesionista.
    -- Depende únicamente de id (PK) → FNBC trivial.
    descripcion TEXT
);
INSERT INTO cat_grado_dependencia (nombre, descripcion) VALUES
    ('Independiente',
     'La persona realiza todas las actividades de la vida diaria sin asistencia de terceros.'),
    ('Requiere Apoyo Moderado',
     'La persona necesita asistencia parcial en algunas actividades cotidianas (movilidad, higiene, comunicación, etc.).'),
    ('Requiere Apoyo Total',
     'La persona depende completamente de un tercero para la realización de actividades básicas de la vida diaria.');
COMMENT ON TABLE  cat_grado_dependencia             IS 'Catálogo de grados de dependencia funcional del NNA o tutor.';
COMMENT ON COLUMN cat_grado_dependencia.descripcion IS 'v8: Descripción narrativa del grado para orientar al profesionista registrador. Depende solo de la PK → FNBC.';

CREATE TABLE cat_nivel_competencia (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_nivel_competencia (nombre) VALUES
    ('Básico'),('Intermedio'),('Avanzado'),('Nativo / Lengua Materna'),
    ('A1 - Acceso'),('A2 - Plataforma'),('B1 - Umbral'),('B2 - Avanzado'),
    ('C1 - Dominio Operativo Eficaz'),('C2 - Maestría'),('No evaluado');
COMMENT ON TABLE cat_nivel_competencia IS 'Catálogo de niveles de competencia lingüística. Escala funcional FUD + escala MCER.';

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
COMMENT ON TABLE cat_parentesco IS 'Catálogo de tipos de parentesco o relación entre tutor y NNA.';

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
COMMENT ON TABLE cat_lengua IS 'Catálogo de lenguas habladas/señadas por NNA, tutores y usuarios conforme al FUD/LGDNNA.';

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
COMMENT ON TABLE cat_pais IS 'Catálogo de países de nacionalidad/origen del NNA conforme al FUD/LGDNNA.';

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
COMMENT ON TABLE cat_escolaridad IS 'Catálogo de niveles de escolaridad homologado con SEP/INEA.';

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
COMMENT ON TABLE cat_motivo_ingreso IS 'Catálogo de motivos de ingreso del NNA al sistema de protección.';

CREATE TABLE cat_enfermedad (
    id_enfermedad   SERIAL          PRIMARY KEY,
    codigo_cie      VARCHAR(10)     UNIQUE,
    nombre          VARCHAR(255)    NOT NULL UNIQUE
);
INSERT INTO cat_enfermedad (codigo_cie, nombre) VALUES
    ('J45','Asma'),('F84.0','Trastorno del espectro autista'),
    ('J00','Resfriado comun'),('J01','Sinusitis aguda'),('J02','Faringitis aguda'),('J03','Amigdalitis'),
    ('J04','Laringitis'),('J05','Crup'),('J06','Infeccion respiratoria superior'),('J10','Influenza'),
    ('J11','Influenza no especificada'),('J12','Neumonia viral'),('J13','Neumonia por neumococo'),
    ('J14','Neumonia por Haemophilus'),('J15','Neumonia bacteriana'),('J18','Neumonia no especificada'),
    ('H65','Otitis media serosa'),('H66','Otitis media aguda'),('H67','Otitis en enfermedades clasificadas'),
    ('A08','Gastroenteritis viral'),('A09','Gastroenteritis infecciosa'),('A37','Tos ferina'),('A38','Escarlatina'),
    ('A39','Meningitis meningococica'),('B01','Varicela'),('B05','Sarampion'),('B06','Rubeola'),
    ('B15','Hepatitis A'),('B16','Hepatitis B'),('N39','Infeccion urinaria'),('K35','Apendicitis'),
    ('K52','Colitis no infecciosa'),('L20','Dermatitis atopica'),('L21','Dermatitis seborreica'),
    ('L22','Dermatitis del pañal'),('E10','Diabetes tipo 1'),('E11','Diabetes tipo 2'),
    ('D50','Anemia por deficiencia de hierro'),('G40','Epilepsia'),('F32','Depresion'),('F90','TDAH'),
    ('R50','Fiebre'),('R51','Dolor de cabeza'),('R56','Convulsiones'),('T78','Reacciones alergicas'),
    ('T79','Complicaciones tempranas de trauma');
COMMENT ON TABLE cat_enfermedad IS 'Catálogo de enfermedades con código CIE-10.';

CREATE TABLE cat_tipo_contacto (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(100)    NOT NULL UNIQUE
);
INSERT INTO cat_tipo_contacto (nombre) VALUES
    ('Teléfono fijo'),('Celular'),('Correo'),('Instagram'),('Facebook'),('LinkedIn'),('Telegram'),
    ('Caseta Comunitaria'),('Red Vecinal / Autoridad Local'),('Teléfono de Albergue / Refugio'),('Enlace Institucional TS');
COMMENT ON TABLE cat_tipo_contacto IS 'Catálogo de tipos de contacto alternativo del NNA.';

CREATE TABLE cat_grupo_sanguineo (
    id      SERIAL          PRIMARY KEY,
    nombre  VARCHAR(20)     NOT NULL UNIQUE
);
INSERT INTO cat_grupo_sanguineo (nombre) VALUES
    ('A+'),('A-'),('B+'),('B-'),('AB+'),('AB-'),('O+'),('O-'),('Bombay'),('Rh nulo'),('Desconocido');
COMMENT ON TABLE cat_grupo_sanguineo IS 'Catálogo de grupos sanguíneos sistema ABO + factor Rh.';

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
COMMENT ON TABLE cat_tipo_visita IS 'v7: Catálogo de tipos de visita/diligencia realizadas por los profesionistas del equipo.';


-- ============================================================
--  FASE 3: GEOGRAFÍA NORMALIZADA — MODELO HÍBRIDO (FNBC)
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
COMMENT ON TABLE entidad_federativa IS 'Catálogo de entidades federativas de México.';

CREATE TABLE cat_municipio (
    id_municipio    SERIAL          PRIMARY KEY,
    nom_mun         VARCHAR(200)    NOT NULL,
    id_ent          INT             NOT NULL REFERENCES entidad_federativa(id_ent) ON DELETE RESTRICT,
    CONSTRAINT uq_municipio_ent UNIQUE (nom_mun, id_ent)
);
COMMENT ON TABLE cat_municipio IS '3FN/FNBC: Catálogo de municipios/alcaldías vinculados a su entidad federativa.';
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
COMMENT ON TABLE direccion IS 'Modelo Geográfico Híbrido: domicilio con colonia y CP como texto abierto, anclado al municipio normalizado.';
CREATE INDEX idx_dir_municipio ON direccion(id_municipio);
CREATE INDEX idx_dir_cp        ON direccion(codigo_postal);


-- ============================================================
--  FASE 4: EQUIPOS MULTIDISCIPLINARIOS  (v7)
-- ============================================================

CREATE TABLE equipo (
    id_equipo       SERIAL          PRIMARY KEY,
    nombre_equipo   VARCHAR(150)    NOT NULL UNIQUE,
    descripcion     TEXT,
    estado          VARCHAR(20)     NOT NULL DEFAULT 'ACTIVO',
    fecha_creacion  TIMESTAMP       NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_estado_equipo CHECK (estado IN ('ACTIVO', 'INACTIVO'))
);
COMMENT ON TABLE  equipo               IS 'v7: Equipos multidisciplinarios. Un equipo agrupa varios profesionistas y atiende varios NNA (1:N en ambos sentidos).';
COMMENT ON COLUMN equipo.nombre_equipo IS 'Nombre identificador del equipo (ej. Equipo Centro CDMX).';
COMMENT ON COLUMN equipo.estado        IS 'ACTIVO | INACTIVO. Atributo propio del equipo, NO transitivo.';

INSERT INTO equipo (nombre_equipo, descripcion, estado) VALUES
    ('Equipo Alfa',  'Equipo multidisciplinario de atención general.',          'ACTIVO'),
    ('Equipo Beta',  'Equipo enfocado en casos de migración y refugio.',        'ACTIVO'),
    ('Equipo Gamma', 'Equipo de atención a casos de salud y discapacidad.',     'ACTIVO');


-- ============================================================
--  FASE 5: OPERACIÓN DE LA PLATAFORMA
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
COMMENT ON TABLE  usuario_sistema           IS 'Usuarios operativos de la plataforma Aurora.';
COMMENT ON COLUMN usuario_sistema.id_equipo IS 'v7: FK a equipo. Un profesionista pertenece a un solo equipo (nullable: admin u otros sin equipo).';


-- ============================================================
--  FASE 6: ENTIDADES CENTRALES
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
COMMENT ON TABLE tutor IS 'Tutores, padres o responsables legales de los NNA registrados.';

CREATE TABLE nna (
    id_nna              SERIAL          PRIMARY KEY,
    folio_nna           VARCHAR(50)     NOT NULL UNIQUE,
    nombre              VARCHAR(100)    NOT NULL,
    -- v8: apodo para facilitar la construcción de confianza con el NNA.
    -- Atributo simple que depende únicamente de id_nna → FNBC trivial.
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
    -- v8: UNIQUE en dir_actual garantiza relación 1:1 estricta con direccion.
    -- Una dirección solo puede estar asignada a un NNA a la vez.
    -- La FK sigue dependiendo únicamente de id_nna (PK) → FNBC se mantiene.
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
COMMENT ON TABLE  nna              IS 'Registro central de NNA conforme al FUD y la LGDNNA.';
COMMENT ON COLUMN nna.apodo        IS 'v8: Nombre o apodo por el que prefiere ser llamado el NNA. Facilita la construcción de confianza en la intervención.';
COMMENT ON COLUMN nna.dir_actual   IS 'v8: UNIQUE → relación 1:1 estricta con direccion. Una dirección no puede asignarse a más de un NNA simultáneamente.';
COMMENT ON COLUMN nna.id_equipo    IS 'v7: FK a equipo. Un NNA es atendido por un solo equipo (nullable: sin equipo asignado todavía).';
COMMENT ON COLUMN nna.registrado_por IS 'UUID del usuario que dio de alta el NNA. El trigger trg_nna_rol_registrador valida que sea Trabajador_Social.';


-- ============================================================
--  FASE 7: TRIGGER DE PERMISOS — REGISTRO DE NNA (v8)
--  Solo un usuario con rol 'Trabajador_Social' puede dar de alta
--  o modificar el campo registrado_por en la tabla nna.
--  La validación ocurre a nivel motor (BEFORE INSERT OR UPDATE),
--  lo que impide inserciones directas vía SQL o herramientas externas.
-- ============================================================

-- ----------------------------------------------------------
--  7A. FUNCIÓN DE VALIDACIÓN
--  Retorna TRIGGER (requerido por PostgreSQL).
--  Se ejecuta antes de INSERT o UPDATE en nna.
--  Solo valida cuando NEW.registrado_por no es NULL.
--  Semántica: registrado_por NULL = NNA sin asignación de registrador aún
--  (caso de datos migrados o seeds); el trigger no bloquea ese caso.
-- ----------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_verificar_rol_trabajador_social()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    v_rol_nombre VARCHAR(100);
BEGIN
    -- Solo se valida cuando se proporciona un registrador
    IF NEW.registrado_por IS NULL THEN
        RETURN NEW;
    END IF;

    -- En UPDATE, si registrado_por no cambió, no re-validar (optimización)
    IF TG_OP = 'UPDATE' AND OLD.registrado_por IS NOT DISTINCT FROM NEW.registrado_por THEN
        RETURN NEW;
    END IF;

    -- Obtener el nombre del rol del usuario indicado como registrador
    SELECT crs.nombre
    INTO   v_rol_nombre
    FROM   usuario_sistema  us
    JOIN   cat_rol_sistema  crs ON crs.id = us.id_rol
    WHERE  us.id_usuario = NEW.registrado_por
      AND  us.estado     = 'ACTIVO';

    -- Si el UUID no existe o el usuario está inactivo
    IF v_rol_nombre IS NULL THEN
        RAISE EXCEPTION
            'AURORA-001: El usuario con id % no existe o no está ACTIVO en usuario_sistema.',
            NEW.registrado_por
            USING ERRCODE = 'P0001';
    END IF;

    -- Si el rol no es Trabajador_Social
    IF v_rol_nombre <> 'Trabajador_Social' THEN
        RAISE EXCEPTION
            'AURORA-002: Solo un usuario con rol Trabajador_Social puede registrar NNA. '
            'Rol detectado: %.',
            v_rol_nombre
            USING ERRCODE = 'P0001';
    END IF;

    RETURN NEW;
END;
$$;

COMMENT ON FUNCTION fn_verificar_rol_trabajador_social() IS
    'v8: Valida que el usuario en registrado_por tenga rol Trabajador_Social y esté ACTIVO. '
    'Se dispara BEFORE INSERT OR UPDATE en nna. NULL en registrado_por se permite (seeds/migración).';

-- ----------------------------------------------------------
--  7B. TRIGGER
-- ----------------------------------------------------------
CREATE TRIGGER trg_nna_rol_registrador
    BEFORE INSERT OR UPDATE OF registrado_por
    ON nna
    FOR EACH ROW
    EXECUTE FUNCTION fn_verificar_rol_trabajador_social();

COMMENT ON TRIGGER trg_nna_rol_registrador ON nna IS
    'v8: BEFORE INSERT OR UPDATE OF registrado_por. '
    'Rechaza el registro si el usuario no tiene rol Trabajador_Social o está inactivo. '
    'El trigger se activa solo cuando registrado_por cambia para evitar re-validaciones innecesarias en UPDATE.';


-- ============================================================
--  FASE 8: RELACIONES Y LISTAS MULTIVALORADAS
-- ============================================================

CREATE TABLE nna_tutor (
    id_nna              INT     NOT NULL REFERENCES nna(id_nna)        ON DELETE CASCADE,
    id_tutor            INT     NOT NULL REFERENCES tutor(id_tutor)    ON DELETE CASCADE,
    id_parentesco       INT     NOT NULL REFERENCES cat_parentesco(id) ON DELETE RESTRICT,
    es_contacto_ppal    BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_vinculacion   DATE,
    PRIMARY KEY (id_nna, id_tutor)
);
COMMENT ON TABLE nna_tutor IS 'Relación N:M entre NNA y sus tutores/responsables.';

CREATE TABLE nna_nacionalidad (
    id_nna      INT     NOT NULL REFERENCES nna(id_nna)  ON DELETE CASCADE,
    id_pais     INT     NOT NULL REFERENCES cat_pais(id) ON DELETE RESTRICT,
    PRIMARY KEY (id_nna, id_pais)
);
COMMENT ON TABLE nna_nacionalidad IS 'Nacionalidades del NNA; admite doble o múltiple nacionalidad.';

-- ----------------------------------------------------------
--  8C. nna_discapacidad  — v8: +bajo_tratamiento, +medicamento_actual
--  PK compuesta (id_nna, id_tipo_discapacidad).
--  bajo_tratamiento y medicamento_actual describen la relación específica
--  entre este NNA y esta discapacidad → dependen de la PK completa → FNBC.
--  El tipo 'Síndrome' de cat_tipo_discapacidad se especifica en
--  descripcion_adicional (texto libre), evitando proliferación de catálogos.
-- ----------------------------------------------------------
CREATE TABLE nna_discapacidad (
    id_nna                     INT     NOT NULL REFERENCES nna(id_nna)               ON DELETE CASCADE,
    id_tipo_discapacidad       INT     NOT NULL REFERENCES cat_tipo_discapacidad(id)  ON DELETE RESTRICT,
    id_grado_dependencia       INT     NOT NULL REFERENCES cat_grado_dependencia(id)  ON DELETE RESTRICT,
    diagnostico_medico_oficial BOOLEAN NOT NULL DEFAULT FALSE,
    descripcion_adicional      TEXT,
    -- v8: seguimiento clínico de la discapacidad/síndrome
    bajo_tratamiento           BOOLEAN NOT NULL DEFAULT FALSE,
    medicamento_actual         TEXT,
    PRIMARY KEY (id_nna, id_tipo_discapacidad)
);
COMMENT ON TABLE  nna_discapacidad                     IS 'Discapacidades y síndromes registrados del NNA. v8: +bajo_tratamiento, +medicamento_actual.';
COMMENT ON COLUMN nna_discapacidad.bajo_tratamiento    IS 'v8: Indica si el NNA está bajo tratamiento médico activo por esta discapacidad/síndrome.';
COMMENT ON COLUMN nna_discapacidad.medicamento_actual  IS 'v8: Medicamento(s) actualmente prescritos. Texto libre para máxima flexibilidad clínica.';
COMMENT ON COLUMN nna_discapacidad.descripcion_adicional IS 'Para tipo "Síndrome", registrar aquí el nombre específico (ej. Síndrome de Down, Síndrome de Rett).';

-- ----------------------------------------------------------
--  8D. tutor_discapacidad  ← NUEVO (v8)
--  Espejo simétrico de nna_discapacidad para tutores.
--  PK compuesta (id_tutor, id_tipo_discapacidad); todos los atributos
--  dependen de la PK completa → FNBC. No se usa tabla polimórfica
--  para mantener FKs fuertes y JOINs simples.
-- ----------------------------------------------------------
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
COMMENT ON TABLE  tutor_discapacidad                     IS 'v8: Discapacidades y síndromes del tutor. Espejo simétrico de nna_discapacidad. Permite registrar si el tutor también tiene condiciones que afectan su capacidad de cuidado.';
COMMENT ON COLUMN tutor_discapacidad.bajo_tratamiento    IS 'v8: Indica si el tutor está bajo tratamiento médico activo por esta condición.';
COMMENT ON COLUMN tutor_discapacidad.medicamento_actual  IS 'v8: Medicamento(s) actualmente prescritos al tutor.';
COMMENT ON COLUMN tutor_discapacidad.descripcion_adicional IS 'Para tipo "Síndrome", registrar el nombre específico del síndrome.';

-- ----------------------------------------------------------
--  8E. nna_lengua  — v8.1: atributos ampliados + requiere_interprete RESTAURADO
--  Todos dependen de la PK compuesta (id_nna, id_lengua) → 5FN asegurada.
-- ----------------------------------------------------------
CREATE TABLE nna_lengua (
    id_nna               INT         NOT NULL REFERENCES nna(id_nna)               ON DELETE CASCADE,
    id_lengua            INT         NOT NULL REFERENCES cat_lengua(id)            ON DELETE RESTRICT,
    es_preferente        BOOLEAN     NOT NULL DEFAULT FALSE,
    id_nivel_competencia INT         NOT NULL REFERENCES cat_nivel_competencia(id) ON DELETE RESTRICT,
    requiere_interprete  BOOLEAN     NOT NULL DEFAULT FALSE, -- RESTAURADO: Regla de negocio crítica
    variante_indigena    VARCHAR(150),
    autodenominacion     VARCHAR(150),
    modo_adquisicion     VARCHAR(100),
    PRIMARY KEY (id_nna, id_lengua)
);
COMMENT ON TABLE  nna_lengua                    IS 'Lenguas habladas/señadas por el NNA. v8.1: requiere_interprete restaurado junto con los atributos de universalidad.';
COMMENT ON COLUMN nna_lengua.variante_indigena  IS 'Variante dialectal específica de la lengua indígena (ej. Zapoteco del Istmo, Mixteco de la Costa).';
COMMENT ON COLUMN nna_lengua.autodenominacion   IS 'Nombre con el que la comunidad hablante denomina su propia lengua (ej. Diidxazá para Zapoteco).';
COMMENT ON COLUMN nna_lengua.modo_adquisicion   IS 'Forma en que el NNA adquirió la lengua (ej. Lengua materna, Adquirida en comunidad).';

-- ----------------------------------------------------------
--  8F. tutor_lengua  (v8.1)
--  Espejo simétrico de nna_lengua para tutores. 
--  PK compuesta (id_tutor, id_lengua) → 5FN.
-- ----------------------------------------------------------
CREATE TABLE tutor_lengua (
    id_tutor             INT         NOT NULL REFERENCES tutor(id_tutor)           ON DELETE CASCADE,
    id_lengua            INT         NOT NULL REFERENCES cat_lengua(id)            ON DELETE RESTRICT,
    es_preferente        BOOLEAN     NOT NULL DEFAULT FALSE,
    id_nivel_competencia INT         NOT NULL REFERENCES cat_nivel_competencia(id) ON DELETE RESTRICT,
    requiere_interprete  BOOLEAN     NOT NULL DEFAULT FALSE, -- RESTAURADO: Aplica también para tutores en diligencias
    variante_indigena    VARCHAR(150),
    autodenominacion     VARCHAR(150),
    modo_adquisicion     VARCHAR(100),
    PRIMARY KEY (id_tutor, id_lengua)
);
COMMENT ON TABLE  tutor_lengua                    IS 'Lenguas del tutor/responsable. Espejo simétrico de nna_lengua. Necesario para servicios de interpretación.';
COMMENT ON COLUMN tutor_lengua.requiere_interprete IS 'Indica si el tutor necesita apoyo de intérprete para esta lengua durante procesos legales/psicológicos.';

-- ----------------------------------------------------------
--  8G. usuario_lengua  (v8.1)
--  Espejo de lenguas para usuarios del sistema (los profesionistas).
--  NOTA: Se incluye requiere_interprete para mantener la estructura universal, 
--  aunque operativamente un profesionista rara vez lo marcará en "TRUE".
-- ----------------------------------------------------------
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
COMMENT ON TABLE  usuario_lengua                    IS 'Lenguas del profesionista/usuario del sistema. Permite identificar personal bilingüe.';
COMMENT ON COLUMN usuario_lengua.modo_adquisicion   IS 'Forma de adquisición de la lengua por el profesionista (ej. Nativa, Aprendida, Certificada).';

CREATE TABLE nna_contacto_adicional (
    id_contacto         SERIAL       PRIMARY KEY,
    id_nna              INT          NOT NULL REFERENCES nna(id_nna)           ON DELETE CASCADE,
    id_tipo_contacto    INT          NOT NULL REFERENCES cat_tipo_contacto(id) ON DELETE RESTRICT,
    valor_contacto      VARCHAR(255) NOT NULL,
    descripcion         VARCHAR(255),
    CONSTRAINT uq_nna_contacto UNIQUE (id_nna, id_tipo_contacto, valor_contacto)
);
COMMENT ON TABLE nna_contacto_adicional IS 'Medios de contacto alternativos del NNA.';

CREATE TABLE nna_enfermedad (
    id_nna              INT     NOT NULL REFERENCES nna(id_nna)                   ON DELETE CASCADE,
    id_enfermedad       INT     NOT NULL REFERENCES cat_enfermedad(id_enfermedad)  ON DELETE RESTRICT,
    bajo_tratamiento    BOOLEAN NOT NULL DEFAULT FALSE,
    observaciones       TEXT,
    PRIMARY KEY (id_nna, id_enfermedad)
);
COMMENT ON TABLE nna_enfermedad IS 'Relación N:M entre NNA y enfermedades diagnosticadas (CIE-10).';


-- ============================================================
--  FASE 9: SEGUIMIENTO MULTIDISCIPLINARIO
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
COMMENT ON TABLE expediente_seguimiento IS 'Registro cronológico de intervenciones. La nota la hace un usuario individual, pero todo su equipo puede verla vía JOIN usuario_sistema.id_equipo = nna.id_equipo.';

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
COMMENT ON TABLE  visita_seguimiento                 IS 'v7: Visitas y diligencias en campo (domiciliarias, legales, médicas...) realizadas por profesionistas.';
COMMENT ON COLUMN visita_seguimiento.id_rol_ejecutor IS 'Rol con el que se realizó la visita. Se guarda explícito porque refleja el rol al momento del registro.';
COMMENT ON COLUMN visita_seguimiento.estado_visita   IS 'PROGRAMADA | REALIZADA | CANCELADA.';
COMMENT ON COLUMN visita_seguimiento.lugar_visita    IS 'Lugar donde se realizó/realizará la visita (texto libre).';


-- ============================================================
--  FASE 10: DATOS OPERATIVOS INICIALES
-- ============================================================

UPDATE usuario_sistema
SET    estado = 'INACTIVO'
WHERE  id_rol = (SELECT id FROM cat_rol_sistema WHERE nombre = 'Trabajador_Social');

-- Usuario administrador de prueba (contraseña texto plano: Admin2024)
-- registrado_por es NULL → el trigger no bloquea este seed.
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
--  FASE 11: ÍNDICES DE RENDIMIENTO
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
-- nna.dir_actual ya tiene índice implícito por la constraint UNIQUE (v8)
CREATE INDEX idx_nna_luga_nac       ON nna(luga_nac_nna);
CREATE INDEX idx_nna_fecha_nac      ON nna(fecha_nacimiento);
CREATE INDEX idx_nna_vulnerabilidad ON nna(situacion_calle, es_migrante, es_refugiado, poblacion_indigena);
CREATE INDEX idx_nna_registrado_por ON nna(registrado_por);  -- v8: para auditoría por registrador

CREATE INDEX idx_nna_tutor_tutor    ON nna_tutor(id_tutor);
CREATE INDEX idx_nna_tutor_parent   ON nna_tutor(id_parentesco);
CREATE INDEX idx_nna_nac_pais       ON nna_nacionalidad(id_pais);

-- Lenguas (v8: tres tablas)
CREATE INDEX idx_nna_lengua_len     ON nna_lengua(id_lengua);
CREATE INDEX idx_tutor_lengua_len   ON tutor_lengua(id_lengua);
CREATE INDEX idx_usr_lengua_len     ON usuario_lengua(id_lengua);

-- Discapacidades (v8: dos tablas)
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

-- NOTA: idx_dir_municipio, idx_dir_cp e idx_municipio_ent se crean en Fase 3.


-- ============================================================
--  RESUMEN DE RELACIONES FNBC — 34 TABLAS (v8)
-- ============================================================
--
--  CATÁLOGOS (14)
--    cat_rol_sistema, cat_sexo, cat_tipo_discapacidad [+Síndrome v8],
--    cat_grado_dependencia [+descripcion v8], cat_nivel_competencia,
--    cat_parentesco, cat_lengua, cat_pais, cat_escolaridad,
--    cat_motivo_ingreso, cat_enfermedad, cat_tipo_contacto,
--    cat_grupo_sanguineo, cat_tipo_visita
--
--  GEOGRAFÍA HÍBRIDA (3)
--    entidad_federativa, cat_municipio, direccion
--
--  EQUIPOS (1)
--    equipo ( id_equipo, nombre_equipo, descripcion, estado, fecha_creacion )
--
--  PLATAFORMA (1)
--    usuario_sistema ( ..., id_equipo, ... )
--
--  ENTIDADES CENTRALES (2)
--    tutor
--    nna ( ..., apodo [v8], dir_actual UNIQUE [v8], id_equipo, registrado_por [trigger v8], ... )
--
--  MULTIVALORADAS — LENGUAS (3)  [v8: tablas espejo]
--    nna_lengua    [+variante_indigena, +autodenominacion, +modo_adquisicion v8]
--    tutor_lengua  [NUEVO v8]
--    usuario_lengua [NUEVO v8]
--
--  MULTIVALORADAS — DISCAPACIDADES (2)  [v8: ampliadas + tabla espejo]
--    nna_discapacidad   [+bajo_tratamiento, +medicamento_actual v8]
--    tutor_discapacidad [NUEVO v8]
--
--  MULTIVALORADAS — OTRAS (4)
--    nna_tutor, nna_nacionalidad, nna_contacto_adicional, nna_enfermedad
--
--  SEGUIMIENTO (2)
--    expediente_seguimiento
--    visita_seguimiento
--
--  LÓGICA DE NEGOCIO (1)  [v8]
--    fn_verificar_rol_trabajador_social()  → función TRIGGER
--    trg_nna_rol_registrador               → BEFORE INSERT OR UPDATE OF registrado_por ON nna
--
--  RELACIONES CLAVE:
--    nna.dir_actual UNIQUE → 1:1 estricta entre nna y direccion  [v8]
--    equipo 1 ── N usuario_sistema   (usuario_sistema.id_equipo)
--    equipo 1 ── N nna               (nna.id_equipo)
--
-- ============================================================
--  FIN DEL SCRIPT — PROYECTO AURORA v8 · FNBC · 34 TABLAS
-- ============================================================