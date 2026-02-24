--
-- PostgreSQL database dump
--

\restrict Sjwz2FENIGGUXGbUWywinesbfVGpqY5lkGrvr7Fw29ed5WuFBAJOIyq5GkY6GYA

-- Dumped from database version 16.11 (Ubuntu 16.11-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.11 (Ubuntu 16.11-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: direccion_mex; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.direccion_mex AS (
	calle character varying(100),
	numero_exterior character varying(10),
	numero_interior character varying(10),
	codigo_postal character varying(10),
	municipio character varying(100),
	estado character varying(100)
);


ALTER TYPE public.direccion_mex OWNER TO postgres;

--
-- Name: nombre_mex; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.nombre_mex AS (
	apellido_paterno character varying(50),
	apellido_materno character varying(50),
	nombres character varying(100)
);


ALTER TYPE public.nombre_mex OWNER TO postgres;

--
-- Name: rol_enum; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.rol_enum AS ENUM (
    'Director',
    'Coordinador',
    'Psicologo',
    'Doctor',
    'Abogado',
    'Trabajador Social',
    'Analista'
);


ALTER TYPE public.rol_enum OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: usuario; Type: TABLE; Schema: public; Owner: postgres
--

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


ALTER TABLE public.usuario OWNER TO postgres;

--
-- Data for Name: usuario; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuario (curp, rfc, nombre, direccion, sexo, tipo_personal, rol, fecha_registro, estado, correo, contrasena, nacimiento) FROM stdin;
AUTL061220HMCGRSA5	AUTL061220LKA	(AGUILAR,TORRES,LUIS)	(kmmm,90,,67890,"GUSTAVO A. MADERO",Nayarit)	Masculino	Empleado	Director	2026-02-23 20:04:12.940229	Activo	luis@aurora.com	123456	2026-02-03
\.


--
-- Name: usuario usuario_correo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_correo_key UNIQUE (correo);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (curp);


--
-- Name: usuario usuario_rfc_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_rfc_key UNIQUE (rfc);


--
-- PostgreSQL database dump complete
--

\unrestrict Sjwz2FENIGGUXGbUWywinesbfVGpqY5lkGrvr7Fw29ed5WuFBAJOIyq5GkY6GYA

