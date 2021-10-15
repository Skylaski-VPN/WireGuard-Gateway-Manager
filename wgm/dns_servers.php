<html>
<head>
<title>WireGuard Gateway Manager - DNS Servers</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - DNS Servers</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_dns_name_val="Server Name";
$default_dns_ipv4_val="-";
$default_dns_ipv6_val="-";
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
		if($_POST['dns_name'] && $_POST['provider'] && $_POST['image'] && $_POST['type'] && $_POST['zone']){
			if($_POST['dns_name'] != $default_dns_name_val){
				if(is_valid_domain_name($_POST['dns_name']) == 1){
					# Now let's make sure the zone and image belong to the provider
					$get_image_provider_sql = "SELECT id FROM iaas_vm_images WHERE provider=".pg_escape_string($_POST['provider'])." AND id=".pg_escape_string($_POST['image']);
					$get_image_provider_ret = pg_query($db,$get_image_provider_sql);
					$image_id = pg_fetch_assoc($get_image_provider_ret);
					$get_zone_provider_sql = "SELECT id FROM iaas_zones WHERE provider=".pg_escape_string($_POST['provider'])." AND id=".pg_escape_string($_POST['zone']);
					$get_zone_provider_ret = pg_query($db,$get_zone_provider_sql);
					$zone_id = pg_fetch_assoc($get_zone_provider_ret);
					if($image_id['id'] == $_POST['image'] && $zone_id['id'] == $_POST['zone']){
						echo "<p>Adding DNS Server</p>";
						$output = shell_exec("./cli/iaas.py dns_server create ".$_POST['dns_name']." ".$_POST['type']." ".$_POST['provider']." ".$_POST['image']." ".$_POST['zone']."");
						echo "<pre>".$output."</pre>";
					}
					else{
						echo "<p>VM Image Zone does not match Provider</p>";
					}
				}
				else{
					echo "<p>Name is an Invalid DNS Name</p>";
				}
			}
			else{
				echo "<p>Please use unique DNS Name</p>";
			}
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	elseif($_POST['action'] == "update"){	// Update a DNS Server
		if($_POST['dns_id'] && $_POST['dns_name'] && $_POST['ipv4_address'] && $_POST['ipv6_address'] && $_POST['type']){
			echo "<p>Updating DNS Server</p>";
			if(filter_var($_POST['ipv4_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
					if(filter_var($_POST['ipv6_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
						echo "<p>Updating DNS Server</p>";
						$update_dns_sql = "UPDATE dns_servers SET dns_name = '".pg_escape_string($_POST['dns_name'])."', ipv4_addr = '".pg_escape_string($_POST['ipv4_address'])."', ipv6_addr = '".pg_escape_string($_POST['ipv6_address'])."', type = '".pg_escape_string($_POST['type'])."' WHERE id=".pg_escape_string($_POST['dns_id'])."";
						$update_dns_ret = pg_query($db,$update_dns_sql);
						if($update_dns_ret){
							echo "<p>Updated DNS Server</p>";
						}
						else{
							echo "<p>Failed to update DNS Server: ".pg_last_error($db)."</p>";
						}
					}
					else{
						echo "<p>Invalid IPv6 Address</p>";
					}
				}
				else{
					echo "<p>Invalid IPv4 Address</p>";
				}
		}
		else{
			echo "Missing Arguments";
		}
	}
	elseif($_POST['action'] == "delete"){	// Delete a DNS Server
		if($_POST['dns_id']){
			echo "<p>Deleting DNS Server</p>";
			$output = shell_exec("./cli/iaas.py dns_server destroy ".$_POST['dns_id']);
			echo "<pre>".$output."</pre>";
		}
		else{
			echo "<p>Missing Arguments</p>";
		}
	}
	else{
		echo "<p>undefined action: ".$_POST['action']."</p>";
	}
}



// Pull the DNS Servers Table for Printing
$sql = "SELECT * FROM dns_servers";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<!-- Insert Domain -->
<table>
	<thead>
<tr><th>Name</th><th>Type</th><th>Provider</th><th>VM Image</th><th>Zone</th></tr>
</thead>
<tr>
<form method="post" action="dns_servers.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="dns_name" value="<?php echo $default_dns_name_val; ?>"></td>
	<td><select name="type" id="insert_dns_type">
<?php
// Discover DNS Types
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
	
	</select>
	</td>
	
	<td><select name="provider" id="insert_dns_provider">
<?php
// Discover IaaS Providers
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
	</select>
	</td>
	
	<td><select name="image" id="insert_dns_image">
<?php
// Discover IaaS Providers
$discover_image_sql = "SELECT * FROM iaas_vm_images WHERE type=2";
$discover_image_ret = pg_query($db,$discover_image_sql);
if($discover_image_ret){
	while($image = pg_fetch_assoc($discover_image_ret)){
		echo "<option value=\"".$image['id']."\">".$image['name']."</option>";
	}
}
else{
	echo "<p>".pg_last_error($db)."</p>";
}
	
?>	
	</select>
	</td>
	
	<td><select name="zone" id="insert_dns_zone">
<?php
// Discover IaaS Providers
$discover_zone_sql = "SELECT * FROM iaas_zones";
$discover_zone_ret = pg_query($db,$discover_zone_sql);
if($discover_zone_ret){
	while($zone = pg_fetch_assoc($discover_zone_ret)){
		echo "<option value=\"".$zone['id']."\">".$zone['name']."</option>";
	}
}
else{
	echo "<p>".pg_last_error($db)."</p>";
}
	
?>	
	</select>
	</td>
		
	<td><input type="submit" name="submit" value="Add DNS Server"></td>
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
	if(pg_field_name($ret,$i) != "unique_id" && pg_field_name($ret,$i) != 'provider_uid'){
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
	
	echo "<form method=\"post\" action=\"dns_servers.php\" onsubmit=\"return confirm('Are you sure you want to Update this DNS Server?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"dns_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"dns_name\" value=\"".$row['dns_name']."\"></td>";	
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"ipv4_address\" value=\"".$row['ipv4_addr']."\"></td>";	
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"ipv6_address\" value=\"".$row['ipv6_addr']."\"></td>";
	echo "<td><select name=\"type\" id=\"".$row['id']."_type\">";
	// Discover DNS Types
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
	echo "</select></td>";
	echo "<script>setValue('".$row['id']."_type','".$row['type']."')</script>";
	
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
	
	
	// Count DNS Servers
	// Count Gateway Servers
	
	echo "<td>"; 
	// Discover Provider
	$discover_provider_sql = "SELECT * FROM iaas_providers WHERE id=".$row['provider'];
	$discover_provider_ret = pg_query($db,$discover_provider_sql);
	if($discover_provider_ret){
		while($prov = pg_fetch_assoc($discover_provider_ret)){
			echo "<p>".$prov['name']."</p>";
		}
	}
	else{
		echo "<p>".pg_last_error($db)."</p>";
	}
	echo "</td>";
	
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Update DNS Server\"></td></form>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"dns_servers.php\" onsubmit=\"return confirm('Are you sure you want to Delete this DNS Server?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"dns_id\" value=\"".$row['id']."\">";
	# If this DNS server is attached to a network, don't delete it
	$find_net_sql = "SELECT dns_id FROM rel_network_dns WHERE dns_id=".pg_escape_string($row['id']);
	$find_net_rel = pg_query($db,$find_net_sql);
	if($find_net = pg_fetch_assoc($find_net_rel)){
		if($find_net['dns_id'] == $row['id']){
			echo "<input type=\"submit\" disabled=\"disabled\" name=\"submit\" value=\"Delete DNS Server\" >";
		}
		else{
			echo "<input type=\"submit\" name=\"submit\" value=\"Delete DNS Server\" >";
		}
	}
	else{
		echo "<input type=\"submit\" name=\"submit\" value=\"Delete DNS Server\" >";
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
