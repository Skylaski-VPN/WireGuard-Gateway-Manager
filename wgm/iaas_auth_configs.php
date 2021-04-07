<html>
<head>
<title>WireGuard Gateway Manager - IaaS Auth Configs</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - IaaS Auth Configs</h1>

<?php

require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_iaas_auth_name_val="Config Name";
$default_iaas_auth_desc_val="-";

//
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
	elseif($_POST['action'] == "add"){	// Add an Auth Config
		if($_POST['auth_name'] && $_POST['auth_desc'] && $_POST['auth_provider'] && $_POST['auth_key0'] && $_POST['auth_key1'] && $_POST['auth_key2'] && $_POST['ssh_key0'] && $_POST['ssh_key1'] && $_POST['ssh_key2']){
			if($_POST['auth_name'] != $default_iaas_auth_name_val && $_POST['auth_desc'] != $default_iaas_auth_desc_val){
				# First make sure there's not already an auth config for this provider
				$check_for_provider_sql = "SELECT provider FROM iaas_auth_configs WHERE provider=".pg_escape_string($_POST['auth_provider']);
				$check_for_provider_ret = pg_query($db,$check_for_provider_sql);
				$provider = pg_fetch_assoc($check_for_provider_ret);
				if($provider['provider'] != $_POST['auth_provider']){
					# New provider auth config. Let's test the provided key for validity.
					$output = shell_exec("./cli/iaas.py iaas_provider test ".$_POST['auth_provider']." ".$_POST['auth_key0']);
					echo "<pre>".$output."</pre>";
					$jso = json_decode($output);
					if($jso->status == true){
						# Cool, we have a valid IaaS Authentication method. Now let's make sure at least 1 SSH Key is valid.
						
						if(SSHKeypasses($_POST['ssh_key0']) == true){

							echo "<p>Adding Auth Config</p>";
							$new_unique_id = generateUniqueID(64,'iaas_auth_configs',$db);
							$insert_auth_sql = "INSERT INTO iaas_auth_configs (name,description,provider,auth_key0,auth_key1,auth_key2,ssh_key0,ssh_key1,ssh_key2,unique_id) VALUES ('".pg_escape_string($_POST['auth_name'])."','".pg_escape_string($_POST['auth_desc'])."','".pg_escape_string($_POST['auth_provider'])."','".pg_escape_string($_POST['auth_key0'])."','".pg_escape_string($_POST['auth_key1'])."','".pg_escape_string($_POST['auth_key2'])."','".pg_escape_string($_POST['ssh_key0'])."','".pg_escape_string($_POST['ssh_key1'])."','".pg_escape_string($_POST['ssh_key2'])."','".pg_escape_string($new_unique_id)."')";
							$insert_auth_ret = pg_query($db,$insert_auth_sql);
							if($insert_auth_ret){
								echo "<p>Added Auth Config</p>";
							}
							else{
								echo "<p>Failed to Add Auth Config: ".pg_last_error($db)."</p>";
							}

						}
						else{
							echo "<p>SSH Key0 must be a valid SSH Key</p>";
						}
					}
					else{
						echo "<p>Invalid Auth Key0</p>";
					}
				}
				else{
					echo "<p>Provider already has an Auth Config, please update that one</p>";
				}
			}
			else{
				echo "<p>Unique values required</p>";
			}
		}
		else{
			echo "<p>Not enough parameters</p>";
		}
	}
	elseif($_POST['action'] == "update"){	// Update an Auth Config
		if($_POST['auth_id'] && $_POST['auth_name'] && $_POST['auth_desc'] && $_POST['auth_provider'] && $_POST['auth_key0'] && $_POST['auth_key1'] && $_POST['auth_key2'] && $_POST['ssh_key0'] && $_POST['ssh_key1'] && $_POST['ssh_key2']){
			if($_POST['auth_name'] != $default_iaas_auth_name_val && $_POST['auth_desc'] != $default_iaas_auth_desc_val){
				# New provider auth config. Let's test the provided key for validity.
				$output = shell_exec("./cli/iaas.py iaas_provider test ".$_POST['auth_provider']." ".$_POST['auth_key0']);
				echo "<pre>".$output."</pre>";
				$jso = json_decode($output);
				if($jso->status == true){
					# Cool, we have a valid IaaS Authentication method. Now let's make sure at least 1 SSH Key is valid.
					if(SSHKeypasses($_POST['ssh_key0']) == true){
						echo "<p>Updating Auth Config</p>";
						$update_auth_sql = "UPDATE iaas_auth_configs SET name = '".pg_escape_string($_POST['auth_name'])."', description = '".pg_escape_string($_POST['auth_desc'])."', provider = '".pg_escape_string($_POST['auth_provider'])."', auth_key0 = '".pg_escape_string($_POST['auth_key0'])."', auth_key1 = '".pg_escape_string($_POST['auth_key1'])."', auth_key2 = '".pg_escape_string($_POST['auth_key2'])."', ssh_key0 = '".pg_escape_string($_POST['ssh_key0'])."', ssh_key1 = '".pg_escape_string($_POST['ssh_key1'])."', ssh_key2 = '".pg_escape_string($_POST['ssh_key2'])."' WHERE id=".pg_escape_string($_POST['auth_id'])."";
						$update_auth_ret = pg_query($db,$update_auth_sql);
						if($update_auth_ret){
							echo "<p>Updated Auth Config</p>";
						}
						else{
							echo "<p>Failed to update Auth Config: ".pg_last_error($db)."</p>";
						}
					}
					else{
						echo "<p>Invalid SSH Key0</p>";
					}
				}
				else{
					echo "<p>Invalid Auth Key0</p>";
				}
				
			}
			else{
				echo "<p>Unique Values Required!</p>";
			}
		}
		else{
			echo "<p>Not enough parameters</p>";
		}
	}
	elseif($_POST['action'] == "delete"){	// Delete an Auth Config
		if($_POST['auth_id']){
			$delete_auth_sql = "DELETE FROM iaas_auth_configs WHERE id=".pg_escape_string($_POST['auth_id']);
			$delete_auth_ret = pg_query($db,$delete_auth_sql);
			if($delete_auth_ret){
				echo "<p>Deleted DNS Server</p>";
			}
			else{
				echo "<p>Failed to delete DNS Server: ".pg_last_error($db)."</p>";
			}
		}
		else{
			echo "<p>Not enough parameters</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}



// Pull the IaaS Providers Table for Printing
$sql = "SELECT * FROM iaas_auth_configs";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<!-- Insert IaaS Provider -->
<table>
	<thead>
<tr><th>Name</th><th>Description</th><th>Provider</th><th>Auth Key0</th><th>Auth Key1</th><th>Auth Key2</th><th>SSH Key0</th><th>SSH Key1</th><th>SSH Key2</th></tr>
</thead>
<tr>
<form method="post" action="iaas_auth_configs.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="auth_name" value="<?php echo $default_iaas_auth_name_val; ?>"></td>
	<td><input type="text" name="auth_desc" value="<?php echo $default_iaas_auth_desc_val; ?>"></td>
	<td><select name="auth_provider" id="insert_auth_provider">
<?php
// Discover IaaS Types
$discover_iaas_sql = "SELECT * FROM iaas_providers";
$discover_iaas_ret = pg_query($db,$discover_iaas_sql);
if($discover_iaas_ret){
	while($iaas = pg_fetch_assoc($discover_iaas_ret)){
		echo "<option value=\"".$iaas['id']."\">".$iaas['name']."</option>";
	}
}
else{
	echo "<p>".pg_last_error($db)."</p>";
}

?>
	<td><input type="text" name="auth_key0" value="<?php echo $default_iaas_auth_desc_val; ?>"></td>
	<td><input type="text" name="auth_key1" value="<?php echo $default_iaas_auth_desc_val; ?>"></td>
	<td><input type="text" name="auth_key2" value="<?php echo $default_iaas_auth_desc_val; ?>"></td>
	<td><input type="text" name="ssh_key0" value="<?php echo $default_iaas_auth_desc_val; ?>"></td>
	<td><input type="text" name="ssh_key1" value="<?php echo $default_iaas_auth_desc_val; ?>"></td>
	<td><input type="text" name="ssh_key2" value="<?php echo $default_iaas_auth_desc_val; ?>"></td>
	</select>
	</td>
	
		
	<td><input type="submit" name="submit" value="Add IaaS Auth Config"></td>
</form>
</tr>
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
	
	echo "<form method=\"post\" action=\"iaas_auth_configs.php\" onsubmit=\"return confirm('Are you sure you want to Update this IaaS Auth Config?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"auth_id\" value=\"".$row['id']."\">";
	echo "<input type=\"hidden\" name=\"auth_provider\" value=\"".$row['provider']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" name=\"auth_name\" value=\"".$row['name']."\"></td>";	
	echo "<td><input type=\"text\" name=\"auth_desc\" value=\"".$row['description']."\"></td>";	
	echo "<td>";
	// Discover Auth Providers
	$discover_iaas_sql = "SELECT * FROM iaas_providers WHERE id=".pg_escape_string($row['provider']);
	$discover_iaas_ret = pg_query($db,$discover_iaas_sql);
	if($discover_iaas_ret){
		while($iaas = pg_fetch_assoc($discover_iaas_ret)){
			echo "<p>".$iaas['name']."</p>";
		}
	}
	else{
		echo "<p>".pg_last_error($db)."</p>";
	}
	echo "</td>";
		
	echo "<td><input type=\"text\" name=\"auth_key0\" value=\"".$row['auth_key0']."\"></td>";
	echo "<td><input type=\"text\" name=\"auth_key1\" value=\"".$row['auth_key1']."\"></td>";
	echo "<td><input type=\"text\" name=\"auth_key2\" value=\"".$row['auth_key2']."\"></td>";
	
	echo "<td><input type=\"text\" name=\"ssh_key0\" value=\"".$row['ssh_key0']."\"></td>";
	echo "<td><input type=\"text\" name=\"ssh_key1\" value=\"".$row['ssh_key1']."\"></td>";
	echo "<td><input type=\"text\" name=\"ssh_key2\" value=\"".$row['ssh_key2']."\"></td>";
	
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
	
	
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update Auth Config\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"iaas_auth_configs.php\" onsubmit=\"return confirm('Are you sure you want to Delete this IaaS Auth Config?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"auth_id\" value=\"".$row['id']."\">";
	# If DNS Servers or GW Servers exist we shouldn't delete this config
	$get_dns_servers_sql = "SELECT provider FROM dns_servers WHERE provider=".pg_escape_string($row['provider']);
	$get_dns_servers_ret = pg_query($db,$get_dns_servers_sql);
	$dns_servers = pg_fetch_assoc($get_dns_servers_ret);
	if($dns_servers['provider'] != $row['provider']){
		# Okay, there aren't any DNS Servers, now let's check for GW Servers
		$get_gw_servers_sql = "SELECT provider FROM gw_servers WHERE provider=".pg_escape_string($row['provider']);
		$get_gw_servers_ret = pg_query($db,$get_gw_servers_sql);
		$gw_servers = pg_fetch_assoc($get_gw_servers_ret);
		if($gw_servers['provider'] != $row['provider']){
			# No Servers depend on this auth config, We can delete it
			echo "<input type=\"submit\" name=\"submit\" value=\"Delete Auth Config\" >";
		}
		else{
			echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete Auth Config\" >";
		}
	}
	else{
		echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete Auth Config\" >";
	}
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
