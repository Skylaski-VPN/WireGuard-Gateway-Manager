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

## Using WireGuard Gateway Manager

### Open WireGuard Gateway Manager for the first time
Browse over to your webapp making sure to point your browser to the `wgm` directory; example: `https://www.example.com/wgm/`.

![wgm1](docs/screenshots/wgm1.png)

### Setup IaaS Provider
Once here, the first thing you need to do is setup your IaaS provider, add an auth config for authenticating with your providers API and download the list of available zones and virtual machine images.

Currently WireGuard Gateway Manager only supports DigitalOcean. Please see [this document](docs/iaas/digitalocean/README.md) on getting DigitalOcean setup for WireGuard Gateway Manager.

Once DigitalOcean is ready to go create the IaaS provider in WireGuard Gateway Manager by clicking the 'IaaS Providers' link.
![iaas](docs/screenshots/iaas1.png)

Here you'll set a Name and Description as well as Type. Make sure you select DigitalOcean as the provider type. 'Other' is not supported.

Once created we can move on to setting up the authentication configuration.

Click 'HOME' or navigate back to `/wgm/` in your web browser and you'll see the 'Add Auth Config' link is now active. 

Click 'Add Auth Config' to setup authentication for this provider's API.

![auth-config](docs/screenshots/iaas2.png)

Here you *MUST* fill out a Name and Description as well as 'Auth Key0' which is your DigitalOcean API token created earlier. 

'SSH Key0' *MUST* also be provided. Make sure this is a Public SSH Key that has been setup in your DigitalOcean environment as well. 

When you go to add an Auth Config, WireGuard Gateway Manager will test your tokens and keys to make sure their valid. 

![authtest](docs/screenshots/iaas3.png)

Once a proper Auth Config has been setup, we need to download the available Zones and Images for this IaaS Provider. 

Navigate back to 'HOME' and you'll see the 'Setup Provider' link is enabled.

![setupiaas](docs/screenshots/iaas4.png)

Click 'Update Zones' to download a list of available zones for this provider.

Click 'Update Images' to download a list of available images for this provider. 

Click 'HOME' to go back to the main page. 

Now you'll notice the Zones and VM Images links are both available. You can confirm the list of Zones by clicking that link, but let's jump over to our VM Images.

![images](docs/screenshots/iaas5.png)

Here is where we will identify which images are DNS servers and which ones are WireGuard Gateway Servers.

You'll need to update the type and description for at least 1 of each (Gateway & DNS) to continue. 

Once you've done that, your IaaS Provider is ready to rock and roll. 

### Setup DNS Provider

### Check Config Templates

### Deploy Gateway Servers

### Deploy DNS Servers

### Create a Network

### Create Network Locations

### Attach Gateway Servers to Network Locations

### Attach DNS Servers to Network

### Create Your First Domain and User

### Create a Client

### Attach Client to Location

### Test Client Connectivity

### Setup PrivacyBot


