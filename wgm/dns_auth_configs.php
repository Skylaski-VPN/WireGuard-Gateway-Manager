<html>
<head>
<title>WireGuard Gateway Manager - DNS Auth Configs</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - DNS Auth Configs</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_dns_auth_name_val="Config Name";
$default_dns_auth_desc_val="-";
$default_dns_auth_val="-";

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
		if($_POST['auth_name'] && $_POST['auth_desc'] && $_POST['auth_value'] && $_POST['auth_key0'] && $_POST['auth_key1'] && $_POST['auth_key2'] && $_POST['auth_provider']){
			if($_POST['auth_name'] != $default_dns_auth_name_val && $_POST['auth_desc'] != $default_dns_auth_desc_val && $_POST['auth_value'] != $default_dns_auth_val){
				# Check to see if DNS provider already has an auth config
				$check_auth_sql = "SELECT provider FROM dns_auth_configs WHERE provider=".pg_escape_string($_POST['auth_provider']);
				$check_auth_ret = pg_query($db,$check_auth_sql);
				$auths = pg_fetch_assoc($check_auth_ret);
				if($auths['provider'] == $_POST['auth_provider']){
					echo "<p>DNS Provider already has an Auth Config, please update current config</p>";
				}
				else{
					echo "<p>Adding DNS Auth Config</p>";
					
					$output = shell_exec("./cli/iaas.py dns_provider test ".$_POST['auth_provider']." ".$_POST['auth_key0']);
					echo "<pre>".$output."</pre>";
					$json = json_decode($output, true);
					#var_dump($json);
					if($json['status'] == true){
					
						$new_unique_id = generateUniqueID(64,'dns_auth_configs',$db);
						$insert_dns_auth_sql = "INSERT INTO dns_auth_configs (name, description, value, auth_key0, auth_key1, auth_key2, unique_id, provider) VALUES ('".pg_escape_string($_POST['auth_name'])."','".pg_escape_string($_POST['auth_desc'])."','".pg_escape_string($_POST['auth_value'])."','".pg_escape_string($_POST['auth_key0'])."','".pg_escape_string($_POST['auth_key1'])."','".pg_escape_string($_POST['auth_key2'])."','".pg_escape_string($new_unique_id)."','".pg_escape_string($_POST['auth_provider'])."')";
						$insert_dns_auth_ret = pg_query($db,$insert_dns_auth_sql);
						if($insert_dns_auth_ret){
							echo "<p>Inserted DNS Auth</p>";
						}
						else{
							echo "<p>Failed to insert DNS Auth: ".pg_last_error($db)."</p>";
						}
					}
					else{
						echo "<p>Provided Auth Key is invalid</p>";
					}
				}
			}
			else{
				echo "<p>Unique Values Required</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "update"){	// Update an Auth Config
		if($_POST['auth_id'] && $_POST['auth_name'] && $_POST['auth_desc'] && $_POST['auth_value'] && $_POST['auth_key0'] && $_POST['auth_key1'] && $_POST['auth_key2'] && $_POST['auth_provider']){
			if($_POST['auth_name'] != $default_dns_auth_name_val && $_POST['auth_desc'] != $default_dns_auth_desc_val && $_POST['auth_value'] != $default_dns_auth_val){
				echo "<p>Updating DNS Auth Config</p>";
				$output = shell_exec("./cli/iaas.py dns_provider test ".$_POST['auth_provider']." ".$_POST['auth_key0']);
				echo "<pre>".$output."</pre>";
				$json = json_decode($output, true);
				#var_dump($json);
				if($json['status'] == true){
				
					
					$update_dns_auth_sql = "UPDATE dns_auth_configs SET name = '".pg_escape_string($_POST['auth_name'])."', description = '".pg_escape_string($_POST['auth_desc'])."', value = '".pg_escape_string($_POST['auth_value'])."', auth_key0 = '".pg_escape_string($_POST['auth_key0'])."', auth_key1 = '".pg_escape_string($_POST['auth_key1'])."', auth_key2 = '".pg_escape_string($_POST['auth_key2'])."', provider = '".pg_escape_string($_POST['auth_provider'])."' WHERE id =".pg_escape_string($_POST['auth_id']);
					$update_dns_auth_ret = pg_query($db,$update_dns_auth_sql);
					if($update_dns_auth_ret){
						echo "<p>Updated DNS Auth Config</p>";
					}
					else{
						echo "<p>Failed to update DNS Auth: ".pg_last_error($db)."</p>";
					}
				}
				else{
					echo "<p>Provided Auth Key is Invalid</p>";
				}
			}
			else{
				echo "<p>Unique Values Required</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "delete"){	// Delete an Auth Config
		if($_POST['auth_id']){
			echo "<p>Deleting Auth Config</p>";
			$delete_dns_auth_sql = "DELETE FROM dns_auth_configs WHERE id = ".pg_escape_string($_POST['auth_id']);
			$delete_dns_auth_ret = pg_query($db,$delete_dns_auth_sql);
			if($delete_dns_auth_ret){
				echo "<p>Deleted DNS Auth Config</p>";
			}
			else{
				echo "<p>Failed to delete DNS Auth Config: ".pg_last_error($db)."</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}



// Pull the IaaS Providers Table for Printing
$sql = "SELECT * FROM dns_auth_configs";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<!-- Insert DNS Auth Config -->
<table>
	<thead>
<tr><th>Name</th><th>Description</th><th>Value</th><th>Auth Key0</th><th>Auth Key1</th><th>Auth Key2</th><th>Provider</th></tr>
</thead>
<tr>
<form method="post" action="dns_auth_configs.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="auth_name" value="<?php echo $default_dns_auth_name_val; ?>"></td>
	<td><input type="text" name="auth_desc" value="<?php echo $default_dns_auth_desc_val; ?>"></td>
	<td><input type="text" name="auth_value" value="<?php echo $default_dns_auth_val; ?>"></td>
	
	<td><input type="text" name="auth_key0" value="<?php echo $default_dns_auth_desc_val; ?>"></td>
	<td><input type="text" name="auth_key1" value="<?php echo $default_dns_auth_desc_val; ?>"></td>
	<td><input type="text" name="auth_key2" value="<?php echo $default_dns_auth_desc_val; ?>"></td>

	<td><select name="auth_provider" id="insert_auth_provider">
<?php
// Discover DNS Providers
$discover_dns_sql = "SELECT * FROM dns_providers";
$discover_dns_ret = pg_query($db,$discover_dns_sql);
if($discover_dns_ret){
	while($dns = pg_fetch_assoc($discover_dns_ret)){
		echo "<option value=\"".$dns['id']."\">".$dns['name']."</option>";
	}
}
else{
	echo "<p>".pg_last_error($db)."</p>";
}

?>

	</select>
	</td>
	
		
	<td><input type="submit" name="submit" value="Add DNS Auth Config"></td>
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
	
	echo "<form method=\"post\" action=\"dns_auth_configs.php\" onsubmit=\"return confirm('Are you sure you want to Update this DNS Auth Config?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"auth_id\" value=\"".$row['id']."\">";
	echo "<input type=\"hidden\" name=\"auth_provider\" value=\"".$row['provider']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" name=\"auth_name\" value=\"".$row['name']."\"></td>";	
	echo "<td><input type=\"text\" name=\"auth_desc\" value=\"".$row['description']."\"></td>";	
	echo "<td><input type=\"text\" name=\"auth_value\" value=\"".$row['value']."\"></td>";	
	echo "<td><input type=\"text\" name=\"auth_key0\" value=\"".$row['auth_key0']."\"></td>";
	echo "<td><input type=\"text\" name=\"auth_key1\" value=\"".$row['auth_key1']."\"></td>";
	echo "<td><input type=\"text\" name=\"auth_key2\" value=\"".$row['auth_key2']."\"></td>";
	
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
	
	echo "<td>";
	// Discover DNS Providers
	$discover_dns_sql = "SELECT * FROM dns_providers WHERE id=".pg_escape_string($row['provider']);
	$discover_dns_ret = pg_query($db,$discover_dns_sql);
	if($discover_dns_ret){
		while($dns = pg_fetch_assoc($discover_dns_ret)){
			echo "<p>".$dns['name']."</p>";
		}
	}
	else{
		echo "<p>".pg_last_error($db)."</p>";
	}
		
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update Auth Config\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"dns_auth_configs.php\" onsubmit=\"return confirm('Are you sure you want to Delete this DNS Auth Config?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"auth_id\" value=\"".$row['id']."\">";
	# If provider has servers, we refuse to delete the auth config, it will be needed to destroy the server & DNS record
	$provider_has_servers_sql = "SELECT dns_provider FROM gw_servers WHERE dns_provider=".pg_escape_string($row['provider']);
	$provider_has_servers_ret = pg_query($provider_has_servers_sql);
	$servers = pg_fetch_assoc($provider_has_servers_ret);
	if($servers['dns_provider'] == $row['provider']){ # DNS Provider has GW Servers Don't delete this config
		echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete Auth Config\" >";
	}
	else{
		echo "<input type=\"submit\" name=\"submit\" value=\"Delete Auth Config\" >";
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
