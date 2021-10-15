<html>
<head>
<title>WireGuard Gateway Manager - DNS Providers</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - DNS Providers</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_dns_name_val="Provider Name";
$default_dns_desc_val="-";

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
	elseif($_POST['action'] == "add"){	// Add a DNS Server
		if($_POST['dns_name'] && $_POST['dns_desc'] && $_POST['dns_type']){
			if($_POST['dns_name'] != $default_dns_name_val && $_POST['dns_desc'] != $default_dns_desc_val){
				echo "<p>Adding DNS Provider</p>";
				$new_unique_id = generateUniqueID(64,'dns_providers',$db);
				$insert_dns_sql = "INSERT INTO dns_providers (name,description,type,unique_id) VALUES ('".pg_escape_string($_POST['dns_name'])."','".pg_escape_string($_POST['dns_desc'])."','".pg_escape_string($_POST['dns_type'])."','".pg_escape_string($new_unique_id)."')";
				$insert_dns_ret = pg_query($db,$insert_dns_sql);
				if($insert_dns_ret){
					echo "<p>Added DNS Provider</p>";
				}
				else{
					echo "<p>Failed to Add DNS Provider: ".pg_last_error($db)."</p>";
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
	elseif($_POST['action'] == "update"){	// Update a DNS Server
		if($_POST['dns_id'] && $_POST['dns_name'] && $_POST['dns_desc'] && $_POST['dns_type']){
			if($_POST['dns_name'] != $default_dns_name_val && $_POST['dns_desc'] != $default_dns_desc_val){
				echo "<p>Updating DNS Provider</p>";
				$update_dns_sql = "UPDATE dns_providers SET name = '".pg_escape_string($_POST['dns_name'])."', description = '".pg_escape_string($_POST['dns_desc'])."', type = '".pg_escape_string($_POST['dns_type'])."' WHERE id=".pg_escape_string($_POST['dns_id']);
				$update_dns_ret = pg_query($db,$update_dns_sql);
				if($update_dns_ret){
					echo "<p>DNS Provider Updated!</p>";
				}
				else{
					echo "<p>Failed to update DNS Provider: ".pg_last_error($db)."</p>";
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
	elseif($_POST['action'] == "delete"){	// Delete a DNS Server
		if($_POST['dns_id']){
			echo "<p>Deleting DNS Provider</p>";
			
			# First delete all zones & records
			$get_dns_zones = "SELECT * FROM dns_zones WHERE provider=".pg_escape_string($_POST['dns_id']);
			$get_dns_ret = pg_query($db,$get_dns_zones);
			while($zone = pg_fetch_assoc($get_dns_ret)){
				$delete_dns_records_sql = "DELETE FROM dns_zone_records WHERE zone=".pg_escape_string($zone['id']);
				$delete_dns_records_ret = pg_query($db,$delete_dns_records_sql);
			}
			$delete_zone_sql = "DELETE FROM dns_zones WHERE provider=".pg_escape_string($_POST['dns_id']);
			$delete_zone_ret = pg_query($db,$delete_zone_sql);
			# delete Auth Configs
			$delete_auth_sql = "DELETE FROM dns_auth_configs WHERE provider=".pg_escape_string($_POST['dns_id']);
			$delete_auth_ret = pg_query($db,$delete_auth_sql);
			
			
			# Now delete DNS provider
			$delete_dns_sql = "DELETE FROM dns_providers WHERE id=".pg_escape_string($_POST['dns_id']);
			$delete_dns_ret = pg_query($db,$delete_dns_sql);
			if($delete_dns_ret){
				echo "<p>Deleted DNS Provider</p>";
			}
			else{
				echo "<p>Failed to delete DNS Provider: ".pg_last_error($db)."</p>";
			}
		}
		else{
			echo "<p>Missing arguments</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}



// Pull the DNS Providers Table for Printing
$sql = "SELECT * FROM dns_providers";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<!-- Insert DNS Provider -->
<table>
	<thead>
<tr><th>Name</th><th>Description</th><th>Type</th></tr>
</thead>
<tr>
<form method="post" action="dns.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="dns_name" value="<?php echo $default_dns_name_val; ?>"></td>
	<td><input type="text" name="dns_desc" value="<?php echo $default_dns_desc_val; ?>"></td>
	<td><select name="dns_type" id="insert_dns_type">
<?php
// Discover IaaS Types
$discover_dns_sql = "SELECT * FROM dns_provider_types";
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
	
		
	<td><input type="submit" name="submit" value="Add DNS Provider"></td>
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
	
	echo "<form method=\"post\" action=\"dns.php\" onsubmit=\"return confirm('Are you sure you want to Update this DNS Provider?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"dns_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" name=\"dns_name\" value=\"".$row['name']."\"></td>";	
	echo "<td><input type=\"text\" name=\"dns_desc\" value=\"".$row['description']."\"></td>";	
	
	# If there is an auth config available for this provider we disable switching types
	$check_auth_sql = "SELECT provider FROM dns_auth_configs WHERE provider=".pg_escape_string($row['id']);
	$check_auth_ret = pg_query($db,$check_auth_sql);
	$auths = pg_fetch_assoc($check_auth_ret);
	if($auths['provider'] == $row['id']){
		echo "<td>";
		$discover_dns_sql = "SELECT * FROM dns_provider_types WHERE id=".pg_escape_string($row['type']);
		$discover_dns_ret = pg_query($db,$discover_dns_sql);
		$types = pg_fetch_assoc($discover_dns_ret);
		echo "<p>".$types['name']."</p>";
		echo "<input type=\"hidden\" name=\"dns_type\" value=\"".$row['type']."\">";
		
		
	}
	else{
		echo "<td><select name=\"dns_type\" id=\"".$row['id']."_type\">";
		
		// Discover DNS Types
		$discover_dns_sql = "SELECT * FROM dns_provider_types";
		$discover_dns_ret = pg_query($db,$discover_dns_sql);
		if($discover_dns_ret){
			while($dns = pg_fetch_assoc($discover_dns_ret)){
				echo "<option value=\"".$dns['id']."\">".$dns['name']."</option>";
			}
		}
		else{
			echo "<p>".pg_last_error($db)."</p>";
		}
		echo "</select></td>";
		echo "<script>setValue('".$row['id']."_type','".$row['type']."')</script>";
	}
	
	
	
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
			
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update DNS Provider\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"dns.php\" onsubmit=\"return confirm('Are you sure you want to Delete this DNS Provider?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"dns_id\" value=\"".$row['id']."\">";
	
	# If provider has auth, we refuse to delete it, it will be needed to destroy other records
	$provider_has_auth_sql = "SELECT provider FROM dns_auth_configs WHERE provider=".pg_escape_string($row['id']);
	$provider_has_auth_ret = pg_query($provider_has_auth_sql);
	$auth = pg_fetch_assoc($provider_has_auth_ret);
	if($auth['provider'] == $row['id']){ # DNS Provider has Auth Config Don't delete this config
		echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete DNS Provider\" >";
	}
	else{
		echo "<input type=\"submit\" name=\"submit\" value=\"Delete DNS Provider\" >";
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
