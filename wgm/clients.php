<html>
<head>
<title>WireGuard Gateway Manager - Clients</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - Clients</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_client_name_val="clientname";
$default_client_pub_key_val="-";
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
	elseif($_POST['action'] == "add"){	// Add a client
		if($_POST['client_name'] != $default_client_name_val){
			if($_POST['client_name'] && $_POST['pub_key'] && $_POST['user_id'] && $_POST['dns_type']){
				
				// Make sure user_id has an associated domain
				$get_user_domain_sql = "SELECT domain_id FROM users WHERE id=".pg_escape_string($_POST["user_id"])."";
				$get_user_domain_ret = pg_query($db, $get_user_domain_sql);
				if($get_user_domain_ret){
					$get_user_domain_row = pg_fetch_assoc($get_user_domain_ret);
					$user_domain_id = $get_user_domain_row['domain_id'];
					
					# Make sure we're not creating more clients than the users is allows
					$max_allowed_clients_sql = "SELECT total_clients FROM users WHERE id=".pg_escape_string($_POST['user_id']);
					$max_allowed_clients_ret = pg_query($db,$max_allowed_clients_sql);
					$cur_configured_clients_sql = "SELECT count(id) as client_count FROM clients WHERE user_id=".pg_escape_string($_POST['user_id']);
					$cur_configured_clients_ret = pg_query($db,$cur_configured_clients_sql);
					
					if((($cur_clients = pg_fetch_assoc($cur_configured_clients_ret))['client_count'] +1) <= ($max_allowed = pg_fetch_assoc($max_allowed_clients_ret)['total_clients'])){
						
						# Make sure DNS type is available to current Domain
						$get_avail_dns_sql = "SELECT dns.type FROM dns_servers dns
												JOIN rel_network_dns netdns ON dns.id=netdns.dns_id
												JOIN rel_domain_network domnet ON netdns.network_id=domnet.network_id
												WHERE domnet.domain_id=".pg_escape_string($get_user_domain_row['domain_id']);
						$get_avail_dns_ret = pg_query($db,$get_avail_dns_sql);
						$dns_is_avail = False;
						while($dns_types = pg_fetch_assoc($get_avail_dns_ret)){
							if($dns_is_avail == True){
								break;
							}
							if($dns_types['type'] == $_POST['dns_type']){
								$dns_is_avail = True;
							}
						}
						if($dns_is_avail == True){
							$new_unique_id = generateUniqueID(64,'clients',$db);
							$insert_client_sql = "INSERT INTO clients (client_name,unique_id,pub_key,user_id,domain_id,dns_type) VALUES ('".pg_escape_string($_POST['client_name'])."', '".pg_escape_string($new_unique_id)."', '".pg_escape_string($_POST['pub_key'])."', ".pg_escape_string($_POST['user_id']).", ".$user_domain_id.", '".pg_escape_string($_POST['dns_type'])."')";
							$insert_client_ret = pg_query($db,$insert_client_sql);
							if($insert_client_ret){
								echo "<p>Client Inserted</p>";					}
							else{
								echo "<p>Failed to add client: ".pg_last_error($db)."</p>";
							}
						}
						else{
							echo "<p>DNS Type is Unavailable on this Network</p>";
						}
					}
					else{
						echo "<p>User already has a max of ".$max_allowed['total_clients']." clients configured</p>";
					}
				}
				else{
					echo "<p>Failed to find user domain: ".pg_last_error($db)." SQL: ".$get_user_domain_sql."</p>";
				}
			}
			else{
				echo "<p>Missing Arguments</p>";
			}
		}
		else{
			echo "<p>Please Enter Unique Client Name</p>";
		}
		
	}
	elseif($_POST['action'] == "update"){	// Update a client
		if($_POST['client_id'] && $_POST['client_name'] && $_POST['pub_key'] && $_POST['user_id'] && $_POST['dns_type']){
			
			// Make sure user_id has an associated domain
			$get_user_domain_sql = "SELECT domain_id FROM users WHERE id=".pg_escape_string($_POST['user_id'])."";
			$get_user_domain_ret = pg_query($db, $get_user_domain_sql);
			if($get_user_domain_ret){
				$get_user_domain_row = pg_fetch_assoc($get_user_domain_ret);
				$user_domain_id = $get_user_domain_row['domain_id'];
				
				# Make sure DNS type is available to current Domain
				$get_avail_dns_sql = "SELECT dns.type FROM dns_servers dns
										JOIN rel_network_dns netdns ON dns.id=netdns.dns_id
										JOIN rel_domain_network domnet ON netdns.network_id=domnet.network_id
										WHERE domnet.domain_id=".pg_escape_string($get_user_domain_row['domain_id']);
				$get_avail_dns_ret = pg_query($db,$get_avail_dns_sql);
				$dns_is_avail = False;
				while($dns_types = pg_fetch_assoc($get_avail_dns_ret)){
					if($dns_is_avail == True){
						break;
					}
					if($dns_types['type'] == $_POST['dns_type']){
						$dns_is_avail = True;
					}
				}
				if($dns_is_avail == True){
				
					$update_client_sql = "UPDATE clients SET client_name ='".pg_escape_string($_POST['client_name'])."', pub_key = '".pg_escape_string($_POST['pub_key'])."', user_id =".pg_escape_string($_POST['user_id'])." , domain_id = ".$user_domain_id.", dns_type=".pg_escape_string($_POST['dns_type'])." WHERE id= ".pg_escape_string($_POST['client_id'])."";
					$update_client_ret = pg_query($db,$update_client_sql);
					if($insert_client_ret){
						echo "<p>Client Inserted</p>";					
					}
					else{
						echo "<p>Failed to add client: ".pg_last_error($db)."</p>";
					}
				}
				else{
					echo "<p>DNS Type is unavailable to this Domain</p>";
				}
			}
			else{
				echo "<p>Failed to find user domain: ".pg_last_error($db)." SQL: ".$get_user_domain_sql."</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "delete"){	// Delete a client
		if($_POST['client_id']){
			# Delete peers from related gateways
			$output = shell_exec("./cli/wgmcontroller.py detach_client ".$_POST['client_id']);
			echo "<pre>".$output."</pre>";
			# Clear any location relationship
			echo "<p>Detaching Client From Location</p>";
			$detach_client_sql = "DELETE FROM rel_client_loc WHERE client_id=".pg_escape_string($_POST['client_id']);
			$detach_client_ret = pg_query($db,$detach_client_sql);
			if($detach_client_ret){
				echo "<p>Detached Client From Location</p>";
			}
			else{
				echo "<p>Failed to Detach Client From Location</p>";
			}
			
			$delete_client_sql = "DELETE FROM clients WHERE id=".pg_escape_string($_POST['client_id']);
			$delete_client_ret = pg_query($db, $delete_client_sql);
			if($delete_client_ret){
				echo "<p>Client Deleted</p>";
			}
			else{
				echo "<p>Failed to delete client: ".pg_last_error($db)."</p>";
			}
		}
		else{
			echo "<p>Client ID Not Provided</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}



// Pull the Domains table for printing
$sql = "SELECT * FROM clients";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<!-- Insert client -->
<table>
<thead>
<tr><th>Name</th><th>Public Key</th><th>User</th><th>DNS Type</th></tr>
</thead>
<tbody>
<tr>
<form method="post" action="clients.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="client_name" value="<?php echo $default_client_name_val; ?>"></td>
	
	<td><input type="text" name="pub_key" value="<?php echo $default_client_pub_key_val; ?>"></td>
	
	<td><select name="user_id" id="insert_user_id"> <!-- user_id selector -->
	
<?php
// Discover Users
$sel_users_sql = "SELECT * FROM users";
$sel_users_ret = pg_query($db,$sel_users_sql);
if(!$sel_users_ret){
	echo "<p>Error getting Users: ".pg_last_error($db)."</p>";
}
else{
	while($sel_row = pg_fetch_assoc($sel_users_ret)){
		echo "<option value=\"".$sel_row['id']."\">".$sel_row['user_name']."</option>";
	}
}

?>
	
	</select></td>
	<td><select name="dns_type" id="insert_dns_type"> <!-- DNS Type Preference Selector -->
	
<?php
// discover dns types
$discover_dns_sql = "SELECT * FROM dns_types";
$discover_dns_ret = pg_query($db,$discover_dns_sql);
if($discover_dns_ret){
	while($dns = pg_fetch_assoc($discover_dns_ret)){
		echo "<option value=\"".$dns['id']."\">".$dns['type_name']."</option>";
	}
}
else{
	echo "<p>".pg_last_error($db)."</p>";
}

?>
	
	</select></td>


	<td><input type="submit" name="submit" value="Add Client"></td>
</form>
</tr>
</tbody>
</table>

<hr>


<table> <!-- Show table data in an html table -->
<thead>
<tr>
<?php
//dynamically print field names
for ($i = 0; $i < $num_fields; $i++) {
	if(pg_field_name($ret,$i) != "unique_id"){
		echo "<th>".pg_field_name($ret,$i)."</th>";
	}
}
?>
<th>Insert/Modify</th><th>Or Delete</th>
</tr>
</thead>




<?php
//dynamically print row data ready to be modified

while($row = pg_fetch_assoc($ret)){
	
	echo "<tr>";

	// modify form
	
	echo "<form method=\"post\" action=\"clients.php\" onsubmit=\"return confirm('Are you sure you want to Update this Client?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"client_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" name=\"client_name\" value=\"".$row['client_name']."\"></td>";
	echo "<td><input type=\"text\" name=\"pub_key\" value=\"".$row['pub_key']."\"></td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"ipv4_addr\" value=\"".$row['ipv4_addr']."\"></td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"ipv6_addr\" value=\"".$row['ipv6_addr']."\"></td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"user_id\" value=\"".$row['user_id']."\"></td>";

	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";
	echo "<td>".$row['domain_id']."</td>";
	echo "<td>";
	echo "<select name=\"dns_type\" id=\"".$row['id']."_dns_type\">";
	
	//discover DNS types
	$discover_dns_sql = "SELECT * FROM dns_types";
	$discover_dns_ret = pg_query($db,$discover_dns_sql);
	if($discover_dns_ret){
		while($dns = pg_fetch_assoc($discover_dns_ret)){
			echo "<option value=\"".$dns['id']."\">".$dns['type_name']."</option>";
		}
	}
	else{
		echo "<p>".pg_last_error($db)."</p>";
	}
	
	echo "</select>";
	//set proper dns type value
	echo "<script>setValue('".$row['id']."_dns_type','".$row['dns_type']."')</script>";
	echo "</td>";
	echo "<td>".$row['gw_id']."</td>";
	echo "<td>".$row['v4_mask']."</td>";
	echo "<td>".$row['v6_mask']."</td>";
	echo "<td>".$row['dns_ip']."</td>";
	echo "<td>".$row['gw_key']."</td>";
	echo "<td>".$row['gw_addr']."</td>";
	echo "<td>".$row['gw_port']."</td>";
	echo "<td><textarea readonly=\"readonly\" cols=\"40\" rows=\"10\" name=\"client_config\">".$row['client_config']."</textarea></td>";
	
	echo "<td>".$row['token']."</td>";
	echo "<td>".$row['local_uid']."</td>";
	
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update Client\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"clients.php\" onsubmit=\"return confirm('Are you sure you want to Delete this Client?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"client_id\" value=\"".$row['id']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Delete Client\" >";
	echo "</form>";
	echo "</td>";
	echo "</tr>";
}

?>

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
