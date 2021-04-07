<html>
<head>
<title>WireGuard Gateway Manager</title>

<link rel="stylesheet" href="style.css">

</head>
<body>

<h1>WireGuard Gateway Manager</h1>

<?php

# Disable and enable links as things are setup to be used
require 'wgm_config.php';
require 'wgm_include.php';
//
// attempt to connect to DB
$wgm_db = pg_connect( "$db_host $db_port $db_name $db_credentials"  );
if(!$wgm_db) {
	echo "Error : Unable to open database\n";
} else {
	echo "<p>Opened database successfully</p>";
}


?>

<h2>WGM Main Console</h2>
<hr>
<h3>Infrastructure Configuration</h3>
<ul>
<li><a href="iaas.php" class="enabled" id="iaas_link">IaaS Providers</a>
<ul><li><a href="iaas_auth_configs.php" class="disabled" id="iaas_auth_configs_link">Add Auth Config</a></li>
	<li><a href="setup_iaas.php" class="disabled" id="setup_iaas_link">Setup Provider</a></li>
		<ul>
			<li><a href="iaas_zones.php" id="iaas_zones_link" class="disabled">Zones</a></li>
			<li><a href="iaas_vm_images.php" id="iaas_vm_images_link" class="disabled">VM Images</a></li>
		</ul>
</ul>
</li>
<br>
<br>
<li><a href="dns.php" class="enabled" id="dns_link">DNS Providers</a>
<ul>
	<li><a href="dns_auth_configs.php" id="dns_auth_configs_link" class="disabled">Add DNS Auth Config</a></li>
	<li><a href="setup_dns.php" id="setup_dns_link" class="disabled">Setup DNS Provider</a>
		<ul>
			<li><a href="dns_zones.php" id="dns_zones_link" class="disabled">DNS Zones</a></li>
			<li><a href="dns_zone_records.php" id="dns_zone_records_link" class="disabled">DNS Records</a></li>
		</ul>
	</li>
	
</ul>
</li>
<br><br>
<li><a href="config_templates.php" class="enabled">Config Templates</a></li>
</ul>
<hr>
<h3>WGM Servers</h3>
<ul>
<li><a href="gw_servers.php" id="gw_servers_link" class="disabled">Gateways</a></li>
</br>
</br>
<li><a href="dns_servers.php" id="dns_servers_link" class="disabled">DNS Servers</a></li>
</ul>

<hr>
<h3>WGM State</h3>
<ul>
<li><a href="networks.php" id="networks_link" class="disabled">Networks</a>
	<ul>
		<li><a href="locations.php" id="locations_link" class="disabled"> Add Locations</a>
			<ul>
				<li><a href="rel_net_loc_gw.php" id="rel_net_loc_gw_link" class="disabled">Attatch/Detach Gateways</a></li>
			</ul>
		</li>
		<li><a href="rel_network_dns.php" id="rel_network_dns_link" class="disabled">Attach/Detach DNS Servers</a></li>
	</ul>
</li>
<br><br>
<li><a href="domains.php" id="domains_link" class="disabled">Domains</a>
	<ul>
		<li><a href="rel_domain_network.php" id="rel_domain_network_link" class="disabled">Attach/Detach Network</a></li>
		<li><a href="users.php" id="users_link" class="disabled">Add Users</a>
			<ul>
				<li><a href="clients.php" id="clients_link" class="disabled">Add Clients</a></li>
				<ul>
					<li><a href="rel_client_loc.php" id="rel_client_loc_link" class="disabled" >Attach/Detach Location</a></li>
				</ul>
			</ul>
		</li>
	</ul>
</li>
</ul>
<hr>

</body>
</html>

<?php

# Here we control which links are active/disabled based on current configuration

# DNS Setup
$dns_is_setup = False;
$get_dns_providers_sql = "SELECT * FROM dns_providers";
$get_dns_providers_ret = pg_query($get_dns_providers_sql);
if($dns_provider = pg_fetch_assoc($get_dns_providers_ret)){
	//echo "There's a DNS Provider";
	//DNS Provider exists, open auth link
	echo "<script>document.getElementById('dns_auth_configs_link').classList.remove('disabled'); document.getElementById('dns_auth_configs_link').classList.add('enabled') </script>";
	$get_dns_auth_sql = "SELECT * FROM dns_auth_configs";
	$get_dns_auth_ret = pg_query($get_dns_auth_sql);
	if($get_dns_auth = pg_fetch_assoc($get_dns_auth_ret)){
		echo "<script>document.getElementById('setup_dns_link').classList.remove('disabled'); document.getElementById('setup_dns_link').classList.add('enabled')</script>";
		$get_dns_zones_sql = "SELECT * FROM dns_zones";
		$get_dns_zones_ret = pg_query($wgm_db,$get_dns_zones_sql);
		if($get_dns_zone = pg_fetch_assoc($get_dns_zones_ret)){
			echo "<script>document.getElementById('dns_zones_link').classList.remove('disabled'); document.getElementById('dns_zones_link').classList.add('enabled');</script>";
			$get_dns_records_sql = "SELECT * FROM dns_zone_records";
			$get_dns_records_ret = pg_query($wgm_db,$get_dns_records_sql);
			if($get_dns_records = pg_fetch_assoc($get_dns_records_ret)){
				echo "<script>document.getElementById('dns_zone_records_link').classList.remove('disabled'); document.getElementById('dns_zone_records_link').classList.add('enabled');</script>";
				$dns_is_setup = True;
			}
		}
	}
}

# IaaS Setup
$iaas_is_setup = False;
$get_iaas_providers_sql = "SELECT * FROM iaas_providers";
$get_iaas_providers_ret = pg_query($get_iaas_providers_sql);
if($iaas_provider = pg_fetch_assoc($get_iaas_providers_ret)){
	//IaaS provider exists, open auth link
	echo "<script>document.getElementById('iaas_auth_configs_link').classList.remove('disabled'); document.getElementById('iaas_auth_configs_link').classList.add('enabled');</script>";
	$get_iaas_auth_sql = "SELECT * FROM iaas_auth_configs";
	$get_iaas_auth_ret = pg_query($get_iaas_auth_sql);
	if($iaas_auth = pg_fetch_assoc($get_iaas_auth_ret)){
		echo "<script>document.getElementById('setup_iaas_link').classList.remove('disabled'); document.getElementById('setup_iaas_link').classList.add('enabled');</script>";
		$get_iaas_zones_sql = "SELECT * FROM iaas_zones";
		$get_iaas_zones_ret = pg_query($wgm_db,$get_iaas_zones_sql);
		if($iaas_zones = pg_fetch_assoc($get_iaas_zones_ret)){
			echo "<script>document.getElementById('iaas_zones_link').classList.remove('disabled'); document.getElementById('iaas_zones_link').classList.add('enabled');</script>";
			$get_iaas_images_sql = "SELECT * FROM iaas_vm_images";
			$get_iaas_images_ret = pg_query($get_iaas_images_sql);
			if($images = pg_fetch_assoc($get_iaas_images_ret)){
				echo "<script>document.getElementById('iaas_vm_images_link').classList.remove('disabled'); document.getElementById('iaas_vm_images_link').classList.add('enabled');</script>";
				$iaas_is_setup = True;
			}
		}
	}
}

#echo "<p>DNS is Setup: ".$dns_is_setup."</p>";
#echo "<p>IaaS is Setup: ".$iaas_is_setup."</p>";
$dns_image_count = 0;
$gw_image_count = 0;
if($dns_is_setup == true && $iaas_is_setup == true){
	# Infrastructure is almost setup. Check for valid image types. 
	$get_dns_image_count_sql = "SELECT count(id) as count FROM iaas_vm_images WHERE type=2";
	$get_dns_image_count_ret = pg_query($wgm_db,$get_dns_image_count_sql);
	while($dns_images = pg_fetch_assoc($get_dns_image_count_ret)){
		$dns_image_count += $dns_images['count'];
	}
	#echo "<p>DNS Images: ".$dns_image_count."</p>";
	$get_gw_image_count_sql = "SELECT count(id) as count FROM iaas_vm_images WHERE type=1";
	$get_gw_image_count_ret = pg_query($get_gw_image_count_sql);
	while($gw_images = pg_fetch_assoc($get_gw_image_count_ret)){
		$gw_image_count += $gw_images['count'];
	}
	#echo "<p>GW Images: ".$gw_image_count."</p>";
}

# Turn on server building if images exist
if($dns_image_count > 0){
	echo "<script>document.getElementById('dns_servers_link').classList.remove('disabled'); document.getElementById('dns_servers_link').classList.add('enabled');</script>";
}
if($gw_image_count > 0){
	echo "<script>document.getElementById('gw_servers_link').classList.remove('disabled'); document.getElementById('gw_servers_link').classList.add('enabled');</script>";
}

# Count GW and DNS Servers, if >1 of each, turn on Network link
$gw_server_count=0;
$dns_server_count=0;
$get_gw_server_count_sql="SELECT count(id) as count FROM gw_servers";
$get_gw_server_count_ret = pg_query($wgm_db,$get_gw_server_count_sql);
while($get_gw_servers = pg_fetch_assoc($get_gw_server_count_ret)){
	$gw_server_count += $get_gw_servers['count'];
}
#echo "<p>GW Servers: ".$gw_server_count."</p>";
$get_dns_server_count_sql="SELECT count(id) as count FROM dns_servers";
$get_dns_server_count_ret=pg_query($wgm_db,$get_dns_server_count_sql);
while($get_dns_servers = pg_fetch_assoc($get_dns_server_count_ret)){
	$dns_server_count+=$get_dns_servers['count'];
}
#echo "<p>DNS Servers: ".$dns_server_count."</p>";
if($dns_server_count > 0 && $gw_server_count > 0){
	echo "<script>document.getElementById('networks_link').classList.remove('disabled'); document.getElementById('networks_link').classList.add('enabled');</script>";
	echo "<script>document.getElementById('rel_network_dns_link').classList.remove('disabled'); document.getElementById('rel_network_dns_link').classList.add('enabled');</script>";
	echo "<script>document.getElementById('locations_link').classList.remove('disabled'); document.getElementById('locations_link').classList.add('enabled');</script>";
}
# If locations exist, allow attaching GW Servers
$loc_count=0;
$get_locations_sql = "SELECT count(id) as count FROM locations";
$get_locations_ret = pg_query($wgm_db,$get_locations_sql);
while($locations = pg_fetch_assoc($get_locations_ret)){
	$loc_count += $locations['count'];
}
#echo "<p>Locations: ".$loc_count."</p>";
if($loc_count > 0){
	echo "<script>document.getElementById('rel_net_loc_gw_link').classList.remove('disabled'); document.getElementById('rel_net_loc_gw_link').classList.add('enabled');</script>";
}

# If a Network has a DNS Server and Gateway Server attached, allow setting up Domains
$network_ready = False;
$get_net_gw_sql = "SELECT * FROM rel_net_loc_gw";
$get_net_gw_ret = pg_query($wgm_db,$get_net_gw_sql);
while($get_net_gw = pg_fetch_assoc($get_net_gw_ret)){
	if($network_ready == True){
		break;
	}
	if($get_net_gw['net_id']){
		# We have a network with a gw server, check for DNS
		$get_net_dns_sql = "SELECT * FROM rel_network_dns";
		$get_net_dns_ret = pg_query($wgm_db,$get_net_dns_sql);
		while($get_net_dns = pg_fetch_assoc($get_net_dns_ret)){
			if($get_net_gw['net_id'] == $get_net_dns['network_id']){
				$network_ready = True;
			}
		}
	}
}
#echo "<p>Network Configured: ".$network_ready."</p>";
if($network_ready == True){
	echo "<script>document.getElementById('domains_link').classList.remove('disabled'); document.getElementById('domains_link').classList.add('enabled')</script>";
}

# If domains exist and Network Ready, unlock user configuration and the ability to attach networks
if($network_ready = True){
	$count_domains_sql = "SELECT count(id) as count FROM domains";
	$count_domains_ret = pg_query($wgm_db,$count_domains_sql);
	if(($count_domains = pg_fetch_assoc($count_domains_ret))['count'] > 0){
		#echo "<p>Domains: ".$count_domains['count']."<p>";
		echo "<script>document.getElementById('rel_domain_network_link').classList.remove('disabled'); document.getElementById('rel_domain_network_link').classList.add('enabled');</script>";
		
		# Domains exist, there must be users.
		echo "<script>document.getElementById('users_link').classList.remove('disabled'); document.getElementById('users_link').classList.add('enabled');</script>";
		
		# If Networks are attached to domains, we can add clients.
		$get_net_domain_rel_sql = "SELECT count(domain_id) as count FROM rel_domain_network";
		$get_net_domain_rel_ret = pg_query($wgm_db,$get_net_domain_rel_sql);
		if(($get_net_domain = pg_fetch_assoc($get_net_domain_rel_ret))['count'] > 0){
				
			# If users exist, we can create clients
			$get_users_sql = "SELECT count(id) as count FROM users";
			$get_users_ret = pg_query($wgm_db,$get_users_sql);
			if(($get_users = pg_fetch_assoc($get_users_ret))['count'] > 0){
				#echo "<p>Users: ".$get_users['count']."</p>";
				echo "<script>document.getElementById('clients_link').classList.remove('disabled'); document.getElementById('clients_link').classList.add('enabled');</script>";
				
				# If clients exist, we can attach locations
				$client_count = 0;
				$get_clients_sql = "SELECT count(id) as count FROM clients";
				$get_clients_ret = pg_query($wgm_db,$get_clients_sql);
				if($get_clients_ret){
					$client_count = ($get_clients = pg_fetch_assoc($get_clients_ret))['count'];
					$client_count = $get_clients['count'];
				}
				else{
					#echo "<p>Failed to get Clients: ".pg_last_error($wgm_db)."</p>";
				}
				#echo "<p>Clients: ".$client_count."</p>";
				if($client_count > 0){
					echo "<script>document.getElementById('rel_client_loc_link').classList.remove('disabled'); document.getElementById('rel_client_loc_link').classList.add('enabled');</script>";
				}
			}
		}
	}
}

pg_close($wgm_db)


?>
