--
-- PostgreSQL database dump
--

-- Dumped from database version 12.6 (Ubuntu 12.6-0ubuntu0.20.04.1)
-- Dumped by pg_dump version 12.6 (Ubuntu 12.6-0ubuntu0.20.04.1)

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
-- Name: trigger_set_timestamp(); Type: FUNCTION; Schema: public; Owner: wgm
--

CREATE FUNCTION public.trigger_set_timestamp() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$;


ALTER FUNCTION public.trigger_set_timestamp() OWNER TO wgm;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: auth_providers; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.auth_providers (
    id integer NOT NULL,
    value character varying(256) NOT NULL,
    name character varying(256) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.auth_providers OWNER TO wgm;

--
-- Name: auth_methods_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.auth_methods_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.auth_methods_id_seq OWNER TO wgm;

--
-- Name: auth_methods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.auth_methods_id_seq OWNED BY public.auth_providers.id;


--
-- Name: auth_provider_configs; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.auth_provider_configs (
    id integer NOT NULL,
    name character varying(256),
    value character varying(256),
    client_id character varying(256),
    client_secret character varying(256),
    client_token character varying(256),
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.auth_provider_configs OWNER TO wgm;

--
-- Name: auth_provider_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.auth_provider_configs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.auth_provider_configs_id_seq OWNER TO wgm;

--
-- Name: auth_provider_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.auth_provider_configs_id_seq OWNED BY public.auth_provider_configs.id;


--
-- Name: clients; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.clients (
    id integer NOT NULL,
    client_name character varying(256) NOT NULL,
    unique_id character varying(256) NOT NULL,
    pub_key character varying(256),
    ipv4_addr character varying(256),
    ipv6_addr character varying(256),
    user_id integer NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    domain_id integer NOT NULL,
    dns_type integer NOT NULL,
    gw_id integer,
    v4_mask character varying(16),
    v6_mask character varying(16),
    dns_ip character varying(256),
    gw_key character varying(256),
    gw_addr character varying(256),
    gw_port character varying(256),
    client_config text,
    token character varying(4096),
    local_uid character varying(4096)
);


ALTER TABLE public.clients OWNER TO wgm;

--
-- Name: clients_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.clients_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.clients_id_seq OWNER TO wgm;

--
-- Name: clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.clients_id_seq OWNED BY public.clients.id;


--
-- Name: dns_auth_configs; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.dns_auth_configs (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    description character varying(256) NOT NULL,
    value character varying(256) NOT NULL,
    auth_key0 character varying(8192),
    auth_key1 character varying(8192),
    auth_key2 character varying(8192),
    unique_id character varying(64) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    provider integer NOT NULL
);


ALTER TABLE public.dns_auth_configs OWNER TO wgm;

--
-- Name: dns_auth_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.dns_auth_configs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dns_auth_configs_id_seq OWNER TO wgm;

--
-- Name: dns_auth_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.dns_auth_configs_id_seq OWNED BY public.dns_auth_configs.id;


--
-- Name: dns_provider_types; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.dns_provider_types (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    value character varying(256) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.dns_provider_types OWNER TO wgm;

--
-- Name: dns_provider_types_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.dns_provider_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dns_provider_types_id_seq OWNER TO wgm;

--
-- Name: dns_provider_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.dns_provider_types_id_seq OWNED BY public.dns_provider_types.id;


--
-- Name: dns_providers; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.dns_providers (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    description character varying(256) NOT NULL,
    type character varying(256) NOT NULL,
    unique_id character varying(64) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.dns_providers OWNER TO wgm;

--
-- Name: dns_providers_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.dns_providers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dns_providers_id_seq OWNER TO wgm;

--
-- Name: dns_providers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.dns_providers_id_seq OWNED BY public.dns_providers.id;


--
-- Name: dns_servers; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.dns_servers (
    id integer NOT NULL,
    dns_name character varying(256),
    ipv4_addr character varying(256),
    ipv6_addr character varying(256),
    type character varying(256),
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    unique_id character varying(64) NOT NULL,
    provider integer,
    provider_uid character varying(256)
);


ALTER TABLE public.dns_servers OWNER TO wgm;

--
-- Name: dns_servers_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.dns_servers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dns_servers_id_seq OWNER TO wgm;

--
-- Name: dns_servers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.dns_servers_id_seq OWNED BY public.dns_servers.id;


--
-- Name: dns_types; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.dns_types (
    id integer NOT NULL,
    type_name character varying(256) NOT NULL,
    description character varying(256) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.dns_types OWNER TO wgm;

--
-- Name: dns_types_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.dns_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dns_types_id_seq OWNER TO wgm;

--
-- Name: dns_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.dns_types_id_seq OWNED BY public.dns_types.id;


--
-- Name: dns_zone_record_types; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.dns_zone_record_types (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    value character varying(256) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.dns_zone_record_types OWNER TO wgm;

--
-- Name: dns_zone_record_types_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.dns_zone_record_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dns_zone_record_types_id_seq OWNER TO wgm;

--
-- Name: dns_zone_record_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.dns_zone_record_types_id_seq OWNED BY public.dns_zone_record_types.id;


--
-- Name: dns_zone_records; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.dns_zone_records (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    type character varying(64) NOT NULL,
    content character varying(256) NOT NULL,
    zone integer NOT NULL,
    ttl integer NOT NULL,
    unique_id character varying(64) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    provider_uid character varying(256) NOT NULL
);


ALTER TABLE public.dns_zone_records OWNER TO wgm;

--
-- Name: dns_zone_records_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.dns_zone_records_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dns_zone_records_id_seq OWNER TO wgm;

--
-- Name: dns_zone_records_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.dns_zone_records_id_seq OWNED BY public.dns_zone_records.id;


--
-- Name: dns_zones; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.dns_zones (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    value character varying(256) NOT NULL,
    description character varying(256),
    unique_id character varying(64) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    provider integer NOT NULL
);


ALTER TABLE public.dns_zones OWNER TO wgm;

--
-- Name: dns_zones_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.dns_zones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dns_zones_id_seq OWNER TO wgm;

--
-- Name: dns_zones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.dns_zones_id_seq OWNED BY public.dns_zones.id;


--
-- Name: domain_types; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.domain_types (
    id integer NOT NULL,
    value character varying(256) NOT NULL,
    name character varying(256) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.domain_types OWNER TO wgm;

--
-- Name: domain_types_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.domain_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.domain_types_id_seq OWNER TO wgm;

--
-- Name: domain_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.domain_types_id_seq OWNED BY public.domain_types.id;


--
-- Name: domains; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.domains (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    type character varying(256) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    unique_id character varying(64) NOT NULL,
    total_users integer NOT NULL,
    referral_code character varying(256)
);


ALTER TABLE public.domains OWNER TO wgm;

--
-- Name: domains_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.domains_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.domains_id_seq OWNER TO wgm;

--
-- Name: domains_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.domains_id_seq OWNED BY public.domains.id;


--
-- Name: gw_servers; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.gw_servers (
    id integer NOT NULL,
    name character varying(256),
    port integer,
    ipv4_addr character varying(256),
    ipv6_addr character varying(256),
    pub_key character varying(256),
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    pub_ipv4_addr character varying(256) NOT NULL,
    pub_ipv6_addr character varying(256) NOT NULL,
    dns_record_uid character varying(256) NOT NULL,
    provider integer NOT NULL,
    provider_uid character varying(256) NOT NULL,
    dns_zone integer NOT NULL,
    unique_id character varying(64) NOT NULL,
    dns_provider integer NOT NULL
);


ALTER TABLE public.gw_servers OWNER TO wgm;

--
-- Name: gateway_servers_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.gateway_servers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.gateway_servers_id_seq OWNER TO wgm;

--
-- Name: gateway_servers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.gateway_servers_id_seq OWNED BY public.gw_servers.id;


--
-- Name: iaas_auth_configs; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.iaas_auth_configs (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    description character varying(256) NOT NULL,
    provider integer NOT NULL,
    auth_key0 character varying(8192) NOT NULL,
    auth_key1 character varying(8192) NOT NULL,
    auth_key2 character varying(8192) NOT NULL,
    ssh_key0 character varying(8192) NOT NULL,
    ssh_key1 character varying(8192) NOT NULL,
    ssh_key2 character varying(8192) NOT NULL,
    unique_id character varying(64) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.iaas_auth_configs OWNER TO wgm;

--
-- Name: iaas_auth_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.iaas_auth_configs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.iaas_auth_configs_id_seq OWNER TO wgm;

--
-- Name: iaas_auth_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.iaas_auth_configs_id_seq OWNED BY public.iaas_auth_configs.id;


--
-- Name: iaas_provider_types; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.iaas_provider_types (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    value character varying(256) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.iaas_provider_types OWNER TO wgm;

--
-- Name: iaas_provider_types_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.iaas_provider_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.iaas_provider_types_id_seq OWNER TO wgm;

--
-- Name: iaas_provider_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.iaas_provider_types_id_seq OWNED BY public.iaas_provider_types.id;


--
-- Name: iaas_providers; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.iaas_providers (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    description character varying(256) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    unique_id character varying(64),
    type integer
);


ALTER TABLE public.iaas_providers OWNER TO wgm;

--
-- Name: iaas_providers_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.iaas_providers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.iaas_providers_id_seq OWNER TO wgm;

--
-- Name: iaas_providers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.iaas_providers_id_seq OWNED BY public.iaas_providers.id;


--
-- Name: iaas_vm_images; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.iaas_vm_images (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    value character varying(256) NOT NULL,
    description character varying(256),
    type integer,
    unique_id character varying(64) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    provider integer NOT NULL
);


ALTER TABLE public.iaas_vm_images OWNER TO wgm;

--
-- Name: iaas_vm_images_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.iaas_vm_images_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.iaas_vm_images_id_seq OWNER TO wgm;

--
-- Name: iaas_vm_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.iaas_vm_images_id_seq OWNED BY public.iaas_vm_images.id;


--
-- Name: iaas_vm_types; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.iaas_vm_types (
    id integer NOT NULL,
    name character(256) NOT NULL,
    value character varying(256) NOT NULL,
    description character varying(256) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.iaas_vm_types OWNER TO wgm;

--
-- Name: iaas_vm_types_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.iaas_vm_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.iaas_vm_types_id_seq OWNER TO wgm;

--
-- Name: iaas_vm_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.iaas_vm_types_id_seq OWNED BY public.iaas_vm_types.id;


--
-- Name: iaas_zones; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.iaas_zones (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    value character varying(256) NOT NULL,
    unique_id character varying(64) NOT NULL,
    provider integer NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.iaas_zones OWNER TO wgm;

--
-- Name: iaas_zones_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.iaas_zones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.iaas_zones_id_seq OWNER TO wgm;

--
-- Name: iaas_zones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.iaas_zones_id_seq OWNED BY public.iaas_zones.id;


--
-- Name: ipv4_client_leases; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.ipv4_client_leases (
    id integer NOT NULL,
    network_id integer,
    address character varying(256),
    domain_id integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    loc_id integer NOT NULL,
    client_id integer NOT NULL,
    gw_id integer NOT NULL
);


ALTER TABLE public.ipv4_client_leases OWNER TO wgm;

--
-- Name: ipv4_addresses_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.ipv4_addresses_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ipv4_addresses_id_seq OWNER TO wgm;

--
-- Name: ipv4_addresses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.ipv4_addresses_id_seq OWNED BY public.ipv4_client_leases.id;


--
-- Name: ipv6_client_leases; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.ipv6_client_leases (
    id integer NOT NULL,
    network_id integer,
    address character varying(256),
    domain_id integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    client_id integer NOT NULL,
    loc_id integer NOT NULL,
    gw_id integer NOT NULL
);


ALTER TABLE public.ipv6_client_leases OWNER TO wgm;

--
-- Name: ipv6_addresses_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.ipv6_addresses_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ipv6_addresses_id_seq OWNER TO wgm;

--
-- Name: ipv6_addresses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.ipv6_addresses_id_seq OWNED BY public.ipv6_client_leases.id;


--
-- Name: location_types; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.location_types (
    id integer NOT NULL,
    name character varying(256),
    value character varying(256),
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.location_types OWNER TO wgm;

--
-- Name: location_types_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.location_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.location_types_id_seq OWNER TO wgm;

--
-- Name: location_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.location_types_id_seq OWNED BY public.location_types.id;


--
-- Name: locations; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.locations (
    id integer NOT NULL,
    name character varying(256),
    geo_name character varying(256),
    address character varying(256),
    type character varying(256),
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    network_id integer NOT NULL,
    unique_id character varying(64) NOT NULL
);


ALTER TABLE public.locations OWNER TO wgm;

--
-- Name: locations_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.locations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.locations_id_seq OWNER TO wgm;

--
-- Name: locations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.locations_id_seq OWNED BY public.locations.id;


--
-- Name: networks; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.networks (
    id integer NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    network_name character varying(256) NOT NULL,
    ipv4_netmask character varying(256) NOT NULL,
    ipv4_gateway character varying(256) NOT NULL,
    ipv6_netmask character varying(256) NOT NULL,
    ipv6_gateway character varying(256) NOT NULL,
    unique_id character varying(64) NOT NULL
);


ALTER TABLE public.networks OWNER TO wgm;

--
-- Name: networks_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.networks_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.networks_id_seq OWNER TO wgm;

--
-- Name: networks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.networks_id_seq OWNED BY public.networks.id;


--
-- Name: rel_client_loc; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.rel_client_loc (
    client_id integer NOT NULL,
    loc_id integer NOT NULL
);


ALTER TABLE public.rel_client_loc OWNER TO wgm;

--
-- Name: rel_domain_network; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.rel_domain_network (
    domain_id integer NOT NULL,
    network_id integer NOT NULL
);


ALTER TABLE public.rel_domain_network OWNER TO wgm;

--
-- Name: rel_net_loc_gw; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.rel_net_loc_gw (
    net_id integer NOT NULL,
    loc_id integer NOT NULL,
    gw_id integer NOT NULL
);


ALTER TABLE public.rel_net_loc_gw OWNER TO wgm;

--
-- Name: rel_network_dns; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.rel_network_dns (
    network_id integer NOT NULL,
    dns_id integer NOT NULL
);


ALTER TABLE public.rel_network_dns OWNER TO wgm;

--
-- Name: templates; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.templates (
    id integer NOT NULL,
    name character varying(256) NOT NULL,
    data text NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.templates OWNER TO wgm;

--
-- Name: templates_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.templates_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.templates_id_seq OWNER TO wgm;

--
-- Name: templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.templates_id_seq OWNED BY public.templates.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: wgm
--

CREATE TABLE public.users (
    id integer NOT NULL,
    user_name character varying(256) NOT NULL,
    user_email character varying(256) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    domain_id integer NOT NULL,
    unique_id character varying(64) NOT NULL,
    total_clients integer NOT NULL,
    auth_provider integer,
    provider_id character varying(256),
    token character varying(256),
    role character varying
);


ALTER TABLE public.users OWNER TO wgm;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: wgm
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_id_seq OWNER TO wgm;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wgm
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: auth_provider_configs id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.auth_provider_configs ALTER COLUMN id SET DEFAULT nextval('public.auth_provider_configs_id_seq'::regclass);


--
-- Name: auth_providers id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.auth_providers ALTER COLUMN id SET DEFAULT nextval('public.auth_methods_id_seq'::regclass);


--
-- Name: clients id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.clients ALTER COLUMN id SET DEFAULT nextval('public.clients_id_seq'::regclass);


--
-- Name: dns_auth_configs id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.dns_auth_configs ALTER COLUMN id SET DEFAULT nextval('public.dns_auth_configs_id_seq'::regclass);


--
-- Name: dns_provider_types id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.dns_provider_types ALTER COLUMN id SET DEFAULT nextval('public.dns_provider_types_id_seq'::regclass);


--
-- Name: dns_providers id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.dns_providers ALTER COLUMN id SET DEFAULT nextval('public.dns_providers_id_seq'::regclass);


--
-- Name: dns_servers id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.dns_servers ALTER COLUMN id SET DEFAULT nextval('public.dns_servers_id_seq'::regclass);


--
-- Name: dns_types id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.dns_types ALTER COLUMN id SET DEFAULT nextval('public.dns_types_id_seq'::regclass);


--
-- Name: dns_zone_record_types id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.dns_zone_record_types ALTER COLUMN id SET DEFAULT nextval('public.dns_zone_record_types_id_seq'::regclass);


--
-- Name: dns_zone_records id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.dns_zone_records ALTER COLUMN id SET DEFAULT nextval('public.dns_zone_records_id_seq'::regclass);


--
-- Name: dns_zones id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.dns_zones ALTER COLUMN id SET DEFAULT nextval('public.dns_zones_id_seq'::regclass);


--
-- Name: domain_types id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.domain_types ALTER COLUMN id SET DEFAULT nextval('public.domain_types_id_seq'::regclass);


--
-- Name: domains id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.domains ALTER COLUMN id SET DEFAULT nextval('public.domains_id_seq'::regclass);


--
-- Name: gw_servers id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.gw_servers ALTER COLUMN id SET DEFAULT nextval('public.gateway_servers_id_seq'::regclass);


--
-- Name: iaas_auth_configs id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.iaas_auth_configs ALTER COLUMN id SET DEFAULT nextval('public.iaas_auth_configs_id_seq'::regclass);


--
-- Name: iaas_provider_types id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.iaas_provider_types ALTER COLUMN id SET DEFAULT nextval('public.iaas_provider_types_id_seq'::regclass);


--
-- Name: iaas_providers id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.iaas_providers ALTER COLUMN id SET DEFAULT nextval('public.iaas_providers_id_seq'::regclass);


--
-- Name: iaas_vm_images id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.iaas_vm_images ALTER COLUMN id SET DEFAULT nextval('public.iaas_vm_images_id_seq'::regclass);


--
-- Name: iaas_vm_types id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.iaas_vm_types ALTER COLUMN id SET DEFAULT nextval('public.iaas_vm_types_id_seq'::regclass);


--
-- Name: iaas_zones id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.iaas_zones ALTER COLUMN id SET DEFAULT nextval('public.iaas_zones_id_seq'::regclass);


--
-- Name: ipv4_client_leases id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.ipv4_client_leases ALTER COLUMN id SET DEFAULT nextval('public.ipv4_addresses_id_seq'::regclass);


--
-- Name: ipv6_client_leases id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.ipv6_client_leases ALTER COLUMN id SET DEFAULT nextval('public.ipv6_addresses_id_seq'::regclass);


--
-- Name: location_types id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.location_types ALTER COLUMN id SET DEFAULT nextval('public.location_types_id_seq'::regclass);


--
-- Name: locations id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.locations ALTER COLUMN id SET DEFAULT nextval('public.locations_id_seq'::regclass);


--
-- Name: networks id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.networks ALTER COLUMN id SET DEFAULT nextval('public.networks_id_seq'::regclass);


--
-- Name: templates id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.templates ALTER COLUMN id SET DEFAULT nextval('public.templates_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: auth_provider_configs; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.auth_provider_configs (id, name, value, client_id, client_secret, client_token, created_at, updated_at) FROM stdin;
1	Google WGM0	google				2021-03-12 20:36:39.011316	2021-04-07 03:27:15.0592
4	Twitter WGM0	twitter				2021-03-20 05:14:52.225951	2021-04-07 03:27:28.826492
3	Facebook WGM0	facebook				2021-03-19 22:43:14.608866	2021-04-07 03:27:46.094025
2	Microsoft WGM0	microsoft				2021-03-19 21:10:02.154862	2021-04-07 03:28:11.040344
5	GitHub WGM0	github				2021-04-01 00:32:11.645297	2021-04-07 03:28:16.257717
\.


--
-- Data for Name: auth_providers; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.auth_providers (id, value, name, created_at, updated_at) FROM stdin;
1	email	Email	2021-01-19 04:51:34.693982	2021-01-19 04:51:34.693982
2	google	Google	2021-03-12 16:15:01.725531	2021-03-12 19:30:12.830813
3	microsoft	Microsoft	2021-03-19 19:54:30.307895	2021-03-19 19:54:30.307895
4	facebook	Facebook	2021-03-19 22:42:39.06984	2021-03-19 22:42:39.06984
5	twitter	Twitter	2021-03-20 05:13:00.739688	2021-03-20 05:13:00.739688
6	apple	Apple	2021-03-29 22:23:00.223112	2021-03-29 22:23:00.223112
7	github	GitHub	2021-04-01 00:30:51.620819	2021-04-01 00:30:51.620819
\.


--
-- Data for Name: clients; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.clients (id, client_name, unique_id, pub_key, ipv4_addr, ipv6_addr, user_id, created_at, updated_at, domain_id, dns_type, gw_id, v4_mask, v6_mask, dns_ip, gw_key, gw_addr, gw_port, client_config, token, local_uid) FROM stdin;
\.


--
-- Data for Name: dns_auth_configs; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.dns_auth_configs (id, name, description, value, auth_key0, auth_key1, auth_key2, unique_id, created_at, updated_at, provider) FROM stdin;
\.


--
-- Data for Name: dns_provider_types; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.dns_provider_types (id, name, value, created_at, updated_at) FROM stdin;
1	Cloudflare	CF	2021-02-28 02:36:17.843445	2021-02-28 02:36:17.843445
2	Other DNS Type	other	2021-02-28 02:51:15.254479	2021-02-28 02:51:15.254479
\.


--
-- Data for Name: dns_providers; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.dns_providers (id, name, description, type, unique_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: dns_servers; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.dns_servers (id, dns_name, ipv4_addr, ipv6_addr, type, created_at, updated_at, unique_id, provider, provider_uid) FROM stdin;
\.


--
-- Data for Name: dns_types; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.dns_types (id, type_name, description, created_at, updated_at) FROM stdin;
2	private	private dns service with no logging	2021-01-23 16:40:50.927726	2021-01-23 16:40:50.927726
3	blocking	private dns service with blocking capabilities	2021-01-23 16:41:16.055065	2021-01-23 16:41:16.055065
\.


--
-- Data for Name: dns_zone_record_types; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.dns_zone_record_types (id, name, value, created_at, updated_at) FROM stdin;
1	Address	A	2021-03-04 22:50:14.695672	2021-03-04 22:50:40.66815
\.


--
-- Data for Name: dns_zone_records; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.dns_zone_records (id, name, type, content, zone, ttl, unique_id, created_at, updated_at, provider_uid) FROM stdin;
\.


--
-- Data for Name: dns_zones; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.dns_zones (id, name, value, description, unique_id, created_at, updated_at, provider) FROM stdin;
\.


--
-- Data for Name: domain_types; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.domain_types (id, value, name, created_at, updated_at) FROM stdin;
4	user	User	2021-01-19 01:01:21.872302	2021-01-19 01:01:21.872302
5	team	Team	2021-01-19 01:01:31.998343	2021-01-19 01:01:31.998343
\.


--
-- Data for Name: domains; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.domains (id, name, type, created_at, updated_at, unique_id, total_users, referral_code) FROM stdin;
\.


--
-- Data for Name: gw_servers; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.gw_servers (id, name, port, ipv4_addr, ipv6_addr, pub_key, created_at, updated_at, pub_ipv4_addr, pub_ipv6_addr, dns_record_uid, provider, provider_uid, dns_zone, unique_id, dns_provider) FROM stdin;
\.


--
-- Data for Name: iaas_auth_configs; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.iaas_auth_configs (id, name, description, provider, auth_key0, auth_key1, auth_key2, ssh_key0, ssh_key1, ssh_key2, unique_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: iaas_provider_types; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.iaas_provider_types (id, name, value, created_at, updated_at) FROM stdin;
2	Other	other	2021-02-25 00:12:40.948816	2021-02-25 00:12:40.948816
1	DigitalOcean	DO	2021-02-24 23:45:28.153409	2021-02-28 02:36:35.701284
\.


--
-- Data for Name: iaas_providers; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.iaas_providers (id, name, description, created_at, updated_at, unique_id, type) FROM stdin;
\.


--
-- Data for Name: iaas_vm_images; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.iaas_vm_images (id, name, value, description, type, unique_id, created_at, updated_at, provider) FROM stdin;
\.


--
-- Data for Name: iaas_vm_types; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.iaas_vm_types (id, name, value, description, created_at, updated_at) FROM stdin;
1	Gateway                                                                                                                                                                                                                                                         	gw	WireGuard VPN Gateway Server	2021-02-25 15:03:00.039187	2021-02-25 15:03:00.039187
2	DNS                                                                                                                                                                                                                                                             	dns	Network DNS Server	2021-02-25 15:03:17.153411	2021-02-25 15:03:17.153411
\.


--
-- Data for Name: iaas_zones; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.iaas_zones (id, name, value, unique_id, provider, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: ipv4_client_leases; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.ipv4_client_leases (id, network_id, address, domain_id, created_at, updated_at, loc_id, client_id, gw_id) FROM stdin;
\.


--
-- Data for Name: ipv6_client_leases; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.ipv6_client_leases (id, network_id, address, domain_id, created_at, updated_at, client_id, loc_id, gw_id) FROM stdin;
\.


--
-- Data for Name: location_types; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.location_types (id, name, value, created_at, updated_at) FROM stdin;
1	Digitalocean	digitalocean	2021-02-24 15:28:29.411729	2021-02-24 15:29:13.791845
2	Other	other	2021-02-24 15:50:24.446666	2021-02-24 15:50:24.446666
\.


--
-- Data for Name: locations; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.locations (id, name, geo_name, address, type, created_at, updated_at, network_id, unique_id) FROM stdin;
\.


--
-- Data for Name: networks; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.networks (id, created_at, updated_at, network_name, ipv4_netmask, ipv4_gateway, ipv6_netmask, ipv6_gateway, unique_id) FROM stdin;
\.


--
-- Data for Name: rel_client_loc; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.rel_client_loc (client_id, loc_id) FROM stdin;
\.


--
-- Data for Name: rel_domain_network; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.rel_domain_network (domain_id, network_id) FROM stdin;
\.


--
-- Data for Name: rel_net_loc_gw; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.rel_net_loc_gw (net_id, loc_id, gw_id) FROM stdin;
\.


--
-- Data for Name: rel_network_dns; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.rel_network_dns (network_id, dns_id) FROM stdin;
\.


--
-- Data for Name: templates; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.templates (id, name, data, created_at, updated_at) FROM stdin;
5	default_client	[Interface]\r\nAddress = <IPV4><V4MASK> , <IPV6><V6MASK>\r\nMTU = 1280\r\nDNS = <DNS>\r\nPrivateKey = <PRIVATEKEY>\r\n\r\n[Peer]\r\nPublicKey = <GWKEY>\r\nEndpoint = <GWADDR>:<GWPORT>\r\nAllowedIPs = 0.0.0.0/0 , ::0/0\r\nPersistentKeepalive = 21\r\n	2021-03-09 01:28:46.440614	2021-03-10 03:52:30.46442
4	default_gw	[Interface]\r\nSaveConfig = true\r\nAddress = <IPV4><V4MASK>,<IPV6><V6MASK>\r\nListenPort = <LISTENPORT>\r\nPrivateKey = <PRIVATEKEY>\r\nPostUp = iptables -A FORWARD -i %i -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE\r\nPostDown = iptables -D FORWARD -i %i -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE	2021-03-09 01:21:43.82583	2021-03-10 22:49:52.82056
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: wgm
--

COPY public.users (id, user_name, user_email, created_at, updated_at, domain_id, unique_id, total_clients, auth_provider, provider_id, token, role) FROM stdin;
\.


--
-- Name: auth_methods_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.auth_methods_id_seq', 8, true);


--
-- Name: auth_provider_configs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.auth_provider_configs_id_seq', 5, true);


--
-- Name: clients_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.clients_id_seq', 1, false);


--
-- Name: dns_auth_configs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.dns_auth_configs_id_seq', 1, false);


--
-- Name: dns_provider_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.dns_provider_types_id_seq', 2, true);


--
-- Name: dns_providers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.dns_providers_id_seq', 1, false);


--
-- Name: dns_servers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.dns_servers_id_seq', 1, false);


--
-- Name: dns_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.dns_types_id_seq', 3, true);


--
-- Name: dns_zone_record_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.dns_zone_record_types_id_seq', 1, true);


--
-- Name: dns_zone_records_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.dns_zone_records_id_seq', 1, false);


--
-- Name: dns_zones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.dns_zones_id_seq', 1, false);


--
-- Name: domain_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.domain_types_id_seq', 5, true);


--
-- Name: domains_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.domains_id_seq', 1, false);


--
-- Name: gateway_servers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.gateway_servers_id_seq', 1, false);


--
-- Name: iaas_auth_configs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.iaas_auth_configs_id_seq', 1, false);


--
-- Name: iaas_provider_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.iaas_provider_types_id_seq', 34, true);


--
-- Name: iaas_providers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.iaas_providers_id_seq', 1, false);


--
-- Name: iaas_vm_images_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.iaas_vm_images_id_seq', 1, false);


--
-- Name: iaas_vm_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.iaas_vm_types_id_seq', 2, true);


--
-- Name: iaas_zones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.iaas_zones_id_seq', 1, false);


--
-- Name: ipv4_addresses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.ipv4_addresses_id_seq', 1, false);


--
-- Name: ipv6_addresses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.ipv6_addresses_id_seq', 1, false);


--
-- Name: location_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.location_types_id_seq', 2, true);


--
-- Name: locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.locations_id_seq', 1, false);


--
-- Name: networks_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.networks_id_seq', 1, false);


--
-- Name: templates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.templates_id_seq', 5, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wgm
--

SELECT pg_catalog.setval('public.users_id_seq', 1, false);


--
-- Name: auth_provider_configs auth_provider_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: wgm
--

ALTER TABLE ONLY public.auth_provider_configs
    ADD CONSTRAINT auth_provider_configs_pkey PRIMARY KEY (id);


--
-- Name: auth_provider_configs set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.auth_provider_configs FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: auth_providers set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.auth_providers FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: clients set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.clients FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: dns_auth_configs set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.dns_auth_configs FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: dns_providers set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.dns_providers FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: dns_servers set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.dns_servers FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: dns_types set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.dns_types FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: dns_zone_record_types set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.dns_zone_record_types FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: dns_zone_records set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.dns_zone_records FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: dns_zones set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.dns_zones FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: domain_types set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.domain_types FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: domains set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.domains FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: gw_servers set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.gw_servers FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: iaas_auth_configs set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.iaas_auth_configs FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: iaas_provider_types set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.iaas_provider_types FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: iaas_providers set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.iaas_providers FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: iaas_vm_images set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.iaas_vm_images FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: iaas_zones set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.iaas_zones FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ipv4_client_leases set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.ipv4_client_leases FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ipv6_client_leases set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.ipv6_client_leases FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: location_types set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.location_types FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: locations set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.locations FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: networks set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.networks FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: templates set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.templates FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: users set_timestamp; Type: TRIGGER; Schema: public; Owner: wgm
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.users FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- PostgreSQL database dump complete
--

