<html>
<head>
<title>WireGuard Gateway Manager - Attach/Detach Location</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - Attach/Detach Location</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

#$default_domain_name_val="Domain Name";
#$default_domain_type_val="Team/User";
// attempt to connect to DB
$db = pg_connect( "$db_host $db_port $db_name $db_credentials"  );
if(!$db) {
	echo "Error : Unable to open database\n";
} else {
	echo "<p>Opened database successfully</p>";
}

// Are we receiving data?
if($_SERVER['REQUEST_METHOD'] == "POST"){
	if(empty($_POST['action'])){
		echo "<p>action is required</p>";
	}
	elseif($_POST['action'] == "update"){	// Attach a Network to a Domain
		if($_POST['client_id'] && $_POST['loc_id']){
			echo "<p>Adding Client to Location</p>";
			
			# Get IP Leases
			$lease = get_client_lease($_POST['client_id'],$_POST['loc_id'],$db);
			# Record IPv4
			$insert_ipv4_sql = "INSERT INTO ipv4_client_leases (network_id,address,domain_id,loc_id,client_id,gw_id) VALUES ('".pg_escape_string($lease['ipv4_lease']['network_id'])."','".pg_escape_string($lease['ipv4_lease']['address'])."','".pg_escape_string($lease['ipv4_lease']['domain_id'])."','".pg_escape_string($lease['ipv4_lease']['loc_id'])."','".pg_escape_string($lease['ipv4_lease']['client_id'])."','".pg_escape_string($lease['ipv4_lease']['gw_id'])."')";
			$insert_ipv4_ret = pg_query($db,$insert_ipv4_sql);
			if($insert_ipv4_ret){
				echo "<p>Inserted IPv4 Lease</p>";
				# Record IPv6
				$insert_ipv6_sql = "INSERT INTO ipv6_client_leases (network_id,address,domain_id,client_id,loc_id,gw_id) VALUES ('".pg_escape_string($lease['ipv6_lease']['network_id'])."','".pg_escape_string($lease['ipv6_lease']['address'])."','".pg_escape_string($lease['ipv6_lease']['domain_id'])."','".pg_escape_string($lease['ipv6_lease']['client_id'])."','".pg_escape_string($lease['ipv6_lease']['loc_id'])."','".pg_escape_string($lease['ipv6_lease']['gw_id'])."')";
				$insert_ipv6_ret = pg_query($db,$insert_ipv6_sql);
				if($insert_ipv6_ret){
					echo "<p>Inserted IPv6 Lease</p>";
					# Updating Client Record
					$update_client_sql = "UPDATE clients SET ipv4_addr='".pg_escape_string($lease['ipv4_lease']['address'])."', ipv6_addr='".pg_escape_string($lease['ipv6_lease']['address'])."', gw_id='".pg_escape_string($lease['gw']['id'])."', v4_mask='".pg_escape_string($lease['ipv4_lease']['v4_mask'])."', v6_mask='".pg_escape_string($lease['ipv6_lease']['v6_mask'])."', dns_ip='".pg_escape_string($lease['dns_ip'])."', gw_key='".pg_escape_string($lease['gw']['gw_key'])."', gw_addr='".pg_escape_string($lease['gw']['gw_addr'])."', gw_port='".pg_escape_string($lease['gw']['gw_port'])."' WHERE id=".pg_escape_string($_POST['client_id']);
					$update_client_ret = pg_query($db,$update_client_sql);
					if($update_client_ret){
						echo "<p>Updated Client Record</p>";
						# Attaching Client via rel table
						$attach_client_sql = "INSERT INTO rel_client_loc (client_id,loc_id) VALUES ('".pg_escape_string($_POST['client_id'])."','".pg_escape_string($_POST['loc_id'])."')";
						$attach_client_ret = pg_query($db,$attach_client_sql);
						if($attach_client_ret){
							echo "<p>Added Client to Location</p>";
							#echo "<p>Gateway: ".var_dump($lease['gw'])."</p>";
							#echo "<p>DNS: ".var_dump($lease['dns_ip'])."</p>";
							
							# Everything is good, add peer to GW
							$output = shell_exec("./cli/wgmcontroller.py attach_client ".$_POST['client_id']);
							echo "<pre>".$output."</pre>";
						}
						else{
							echo "<p>Failed to add client to location: ".pg_last_error($db)."</p>";
						}
					}
					else{
						echo "<p>Failed to update Client Record: ".pg_last_error($db)."</p>";
					}
				}
				else{
					echo "<p>Failed to insert IPv6 Lease: ".pg_last_error($db)."</p>";
					var_dump($lease['ipv6_lease']);
				}
			}
			else{
				echo "<p>Failed to insert IPv4 Lease: ".pg_last_error($db)."</p>";
			}
			
			#var_dump($lease['ipv4_lease']['address']);
			
			
			
			
			
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "clear"){	// Detach a Network from a domain
		if($_POST['client_id']){
			echo "<p>Depeering Client from GW Server</p>";
			$output = shell_exec("./cli/wgmcontroller.py detach_client ".$_POST['client_id']);
			echo "<pre>".$output."</pre>";
			echo "<p>Cleaning Up Client Leases</p>";
			$delete_ipv4_lease_sql = "DELETE FROM ipv4_client_leases WHERE client_id=".pg_escape_string($_POST['client_id']);
			$delete_ipv4_lease_ret = pg_query($db,$delete_ipv4_lease_sql);
			if($delete_ipv4_lease_ret){
				echo "<p>Release IPv4 Address</p>";
				$delete_ipv6_lease_sql = "DELETE FROM ipv6_client_leases WHERE client_id=".pg_escape_string($_POST['client_id']);
				$delete_ipv6_lease_ret = pg_query($db,$delete_ipv6_lease_sql);
				if($delete_ipv6_lease_ret){
					echo "<p>Released IPv6 Address</p>";
					echo "<p>Detaching Client From Location</p>";
					$detach_client_sql = "DELETE FROM rel_client_loc WHERE client_id=".pg_escape_string($_POST['client_id']);
					$detach_client_ret = pg_query($db,$detach_client_sql);
					if($detach_client_ret){
						echo "<p>Detached Client From Location</p>";
						echo "<p>Clearing Client Record</p>";
						$clear_client_config_sql = "UPDATE clients set ipv4_addr=NULL, ipv6_addr=NULL, gw_id=NULL, v4_mask=NULL, v6_mask=NULL, dns_ip=NULL, gw_key=NULL, gw_addr=NULL, gw_port=NULL, client_config=NULL WHERE id=".pg_escape_string($_POST['client_id']);
						$clear_client_config_ret = pg_query($db,$clear_client_config_sql);
						if($clear_client_config_ret){
							echo "<p>Cleared Client Record</p>";
							
						}
						else{
							echo "<p>Failed to clear client config: ".pg_last_error($db)."</p>";
						}
					}
					else{
						echo "<p>Failed to Detach Client From Location</p>";
					}
				}
				else{
					echo "<p>Failed to release IPv6 Address</p>";
				}

			}
			else{
				echo "<p>Failed to release IPv4 Address: ".pg_last_error($db)."</p>";
			}
			
		}
		else{
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}



// Pull the Clients table for printing
$clients_sql = "SELECT * FROM clients";
$clients_ret = pg_query($db, $clients_sql);
if(!$clients_ret){
	echo pg_last_error($db);
	exit;
}




?>



<hr>


<table> <!-- Show table data in an html table -->
<thead>
<tr>
<th>ID</th><th>Domain</th><th>Client</th><th>Location</th><th>Update</th><th>Clear</th>
</tr>
</thead>
<tbody>

<?php

while($client = pg_fetch_assoc($clients_ret)){
	echo "<tr>";
	echo "<form action=\"rel_client_loc.php\" method=\"post\" onsubmit=\"return confirm('Are you sure you want to Update this Client?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"client_id\" value=\"".$client['id']."\">";
	echo "<td>".$client['id']."</td>";
	echo "<td>".$client['domain_id']."</td>";
	echo "<td>".$client['client_name']."</td>";

	echo "<td>";
	echo "<select name=\"loc_id\" id=\"".$client['id']."_loc_id\">";
	// Findout what locations are available to this client
	$locations_sql = "SELECT loc.id,loc.name FROM locations loc
        JOIN rel_domain_network domnet ON loc.network_id=domnet.network_id
WHERE domnet.domain_id=".pg_escape_string($client['domain_id']);
	//echo "<td><p>".$locations_sql."</p></td>";

	$locations_ret = pg_query($db, $locations_sql);
	if(!$locations_ret){
		echo pg_last_error($db);
		exit;
	}
	while($location = pg_fetch_assoc($locations_ret)){
		echo "<option value=\"".$location['id']."\">".$location['name']."</option>";
	}
	echo "</select>";
	// Find out if Domain already has a Network
	$discover_client_loc_sql = "SELECT loc_id FROM rel_client_loc WHERE client_id=".$client['id'];
	$discover_client_loc_ret = pg_query($db, $discover_client_loc_sql);
	if($discover_client_loc_ret){
		$discover_row = pg_fetch_assoc($discover_client_loc_ret);
		echo "<script>setValue('".$client['id']."_loc_id', '".$discover_row['loc_id']."')</script>";
	}
	else{
		//echo "<p>".pg_last_error($db)."</p>";
	}
	echo "</td>";
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update Client\"></td>";
	echo "</form>";
	echo "<td><form method=\"post\" action=\"rel_client_loc.php\" onsubmit=\"return confirm('Are you sure you want to Clear this Client?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"clear\">";
	echo "<input type=\"hidden\" name=\"client_id\" value=\"".$client['id']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Clear Client\">";
	echo "</form></td>";
	
	echo "</tr>";
}


?>
</tbody>
<!-- Don't need a foot yet
<tfoot>
<tr><td>1</td><td>2</td><td>3</td></tr>
</tfoot>
-->
</table>



</body>
</html>
<?php
pg_close($db);
?>
