--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.4
-- Dumped by pg_dump version 9.6.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: admins; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE admins (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    username character varying(20) NOT NULL,
    password_hash character varying(255) NOT NULL,
    role_id integer NOT NULL
);


--
-- Name: adminis_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE adminis_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adminis_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE adminis_id_seq OWNED BY admins.id;


--
-- Name: system_event_types; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE system_event_types (
    id smallint NOT NULL,
    event_type character varying(255) NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL,
    description text
);


--
-- Name: log_types_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE log_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: log_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE log_types_id_seq OWNED BY system_event_types.id;


--
-- Name: login_attempts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE login_attempts (
    id bigint NOT NULL,
    admin_id bigint,
    username character varying(20),
    ip character varying(100) NOT NULL,
    created timestamp without time zone NOT NULL,
    success boolean NOT NULL
);


--
-- Name: login_attempts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE login_attempts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: login_attempts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE login_attempts_id_seq OWNED BY login_attempts.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE roles (
    id integer NOT NULL,
    role character varying(100) NOT NULL,
    level smallint NOT NULL,
    CONSTRAINT positive_level CHECK (((level)::double precision > (0)::double precision))
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE roles_id_seq OWNED BY roles.id;


--
-- Name: system_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE system_events (
    id bigint NOT NULL,
    event_type smallint NOT NULL,
    title character varying(255) NOT NULL,
    notes text,
    created timestamp without time zone DEFAULT now() NOT NULL,
    admin_id bigint,
    ip_address character varying(50) NOT NULL,
    resource character varying(100) NOT NULL,
    request_method character varying(20) NOT NULL
);


--
-- Name: system_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE system_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: system_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE system_events_id_seq OWNED BY system_events.id;


--
-- Name: testimonials; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE testimonials (
    testimonial_id bigint NOT NULL,
    testimonial_text text NOT NULL,
    person character varying(50) NOT NULL,
    place character varying(100) NOT NULL,
    active boolean DEFAULT true NOT NULL,
    receive_date date NOT NULL
);


--
-- Name: testimonials_testimonial_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE testimonials_testimonial_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: testimonials_testimonial_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE testimonials_testimonial_id_seq OWNED BY testimonials.testimonial_id;


--
-- Name: admins id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY admins ALTER COLUMN id SET DEFAULT nextval('adminis_id_seq'::regclass);


--
-- Name: login_attempts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY login_attempts ALTER COLUMN id SET DEFAULT nextval('login_attempts_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY roles ALTER COLUMN id SET DEFAULT nextval('roles_id_seq'::regclass);


--
-- Name: system_event_types id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY system_event_types ALTER COLUMN id SET DEFAULT nextval('log_types_id_seq'::regclass);


--
-- Name: system_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY system_events ALTER COLUMN id SET DEFAULT nextval('system_events_id_seq'::regclass);


--
-- Name: testimonials testimonial_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY testimonials ALTER COLUMN testimonial_id SET DEFAULT nextval('testimonials_testimonial_id_seq'::regclass);


--
-- Name: adminis_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('adminis_id_seq', 13, true);


--
-- Data for Name: admins; Type: TABLE DATA; Schema: public; Owner: -
--

COPY admins (id, name, username, password_hash, role_id) FROM stdin;
1	FatCat	owner	$2y$10$v8wggQBQG4fYSBIoHyOD9OAJN5ShMijt9OGTRu8Ah1xdDnSLrZ9Vy	1
\.


--
-- Name: log_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('log_types_id_seq', 9, true);


--
-- Data for Name: login_attempts; Type: TABLE DATA; Schema: public; Owner: -
--

COPY login_attempts (id, admin_id, username, ip, created, success) FROM stdin;
\.


--
-- Name: login_attempts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('login_attempts_id_seq', 98, true);


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: -
--

COPY roles (id, role, level) FROM stdin;
3	director	2
4	manager	3
1	owner	1
5	user	4
\.


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('roles_id_seq', 30, true);


--
-- Data for Name: system_event_types; Type: TABLE DATA; Schema: public; Owner: -
--

COPY system_event_types (id, event_type, created, description) FROM stdin;
1	debug	2017-09-09 07:24:17.407514	Detailed debug information.
2	info	2017-09-09 07:26:34.734512	Interesting events. Examples: User logs in.
3	notice	2017-09-09 07:27:14.758275	Normal but significant events.
5	warning	2017-09-09 07:28:41.128122	Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
6	error	2017-09-09 07:29:17.325642	Runtime errors that do not require immediate action but should typically be logged and monitored.
7	critical	2017-09-09 07:29:57.66948	Critical conditions. Example: Application component unavailable, unexpected exception.
8	alert	2017-09-09 07:31:37.612442	Action must be taken immediately. Example: Entire website down.
9	emergency	2017-09-09 07:32:03.820578	System is unusable.
\.


--
-- Data for Name: system_events; Type: TABLE DATA; Schema: public; Owner: -
--

COPY system_events (id, event_type, title, notes, created, admin_id, ip_address, resource, request_method) FROM stdin;
\.


--
-- Name: system_events_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('system_events_id_seq', 313, true);


--
-- Data for Name: testimonials; Type: TABLE DATA; Schema: public; Owner: -
--

COPY testimonials (testimonial_id, testimonial_text, person, place, active, receive_date) FROM stdin;
\.


--
-- Name: testimonials_testimonial_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('testimonials_testimonial_id_seq', 20, true);


--
-- Name: admins adminis_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY admins
    ADD CONSTRAINT adminis_pkey PRIMARY KEY (id);


--
-- Name: admins admins_username_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY admins
    ADD CONSTRAINT admins_username_key UNIQUE (username);


--
-- Name: system_event_types log_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY system_event_types
    ADD CONSTRAINT log_types_pkey PRIMARY KEY (id);


--
-- Name: login_attempts login_attempts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY login_attempts
    ADD CONSTRAINT login_attempts_pkey PRIMARY KEY (id);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: roles roles_role_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY roles
    ADD CONSTRAINT roles_role_key UNIQUE (role);


--
-- Name: system_events system_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY system_events
    ADD CONSTRAINT system_events_pkey PRIMARY KEY (id);


--
-- Name: testimonials testimonials_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY testimonials
    ADD CONSTRAINT testimonials_pkey PRIMARY KEY (testimonial_id);


--
-- Name: system_events_title_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_events_title_idx ON system_events USING btree (title);


--
-- Name: system_events system_events_admin_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY system_events
    ADD CONSTRAINT system_events_admin_id_fkey FOREIGN KEY (admin_id) REFERENCES admins(id);


--
-- Name: system_events system_events_event_type_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY system_events
    ADD CONSTRAINT system_events_event_type_fkey FOREIGN KEY (event_type) REFERENCES system_event_types(id);


--
-- PostgreSQL database dump complete
--

