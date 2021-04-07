# WireGuard-Gateway-Manager
WireGuard Gateway Manager is a web app for deploying and managing WireGuard Gateway's and Clients

## About

## Dependencies

## Install

### Extract Tarball
First extract the tarball to a working webdirectory. 

`tar -xvf [WGM_RELEASE].tar.bz2`

### Setup Database
WireGuard Gateway Manager uses PostgreSQL to maintain it's state. You can read [this guide](https://medium.com/coding-blocks/creating-user-database-and-adding-access-on-postgresql-8bfcd2f4a91e) on getting started with PostgreSQL.

Once you have a user and database ready to go, use the provided `pg_dump` file to setup the database.

`psql --dbname=[DATABASE_NAME] < wgm_db.sql`

### Configure WireGuard Gateway Manager
Once the database is ready to go open `wgm/wgm_config.sample.php` and edit your database settings.

```
<?php
        // Configuration for connecting to PostgreSQL Database
        $db_host        = "host = <DB HOST>";
        $db_port        = "port = <DB PORT>";
        $db_name      = "dbname = <DB>";
        $db_credentials = "user = <DB USER> password=<DB PASSWORD>"
?>
```

Once finished save as `wgm/wgm_config.php`.

### Configure the API
To configure the API open api/<version>/config.sample.php and edit the necessary variables. The path to the CLI must be the full path starting from root `/`.

```
<?php
        // Configuration for connecting to PostgreSQL Database
        $db_host        = "host = <DB HOST>";
        $db_port        = "port = <DB PORT>";
        $db_name      = "dbname = <DB>";
        $db_credentials = "user = <DB USER> password=<DB PASSWORD>";

        $PATH_TO_CLI = "<FULL PATH TO wgm/cli/>";
?>
```

### Configure the CLI

The configuration example for the CLI is stored in `wgm/cli/wgm_db/` as `config.sample.ini`. Open that file, edit, and save as `config.ini`.

```
[postgresql]
host=<DB HOST>
database=<DB NAME>
user=<DB USER>
password=<DB PASSWORD>
```

### Open WireGuard Gateway Manager for the first time
Browse over to your webapp making sure to point your browser to the `wgm` directory; example: `https://www.example.com/wgm/`.

![wgm1](docs/screenshots/wgm1.png)
