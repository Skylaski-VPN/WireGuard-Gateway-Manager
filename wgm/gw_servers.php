<html>
<head>
<title>WireGuard Gateway Manager - GW Servers</title>
<script src="wgm.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>WireGuard Gateway Manager - GW Servers</h1>

<?php
require 'wgm_config.php';
require 'wgm_include.php';

// include navigation
include 'navigation.php';

$default_gw_name_val="Server Name";
$default_gw_ipv4_val="-";
$default_gw_ipv6_val="-";
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
	elseif($_POST['action'] == "add"){	// Add a GW Server
		# name, dns_zone, provider, provider_image, provider_zone
		if($_POST['gw_name'] && $_POST['provider'] && $_POST['image'] && $_POST['zone'] && $_POST['dns_zone']){
			if($_POST['gw_name'] != $default_gw_name_val){
				if(is_valid_domain_name($_POST['gw_name']) == 1){
					# Now let's make sure the zone and image belong to the provider
					$get_image_provider_sql = "SELECT id FROM iaas_vm_images WHERE provider=".pg_escape_string($_POST['provider'])." AND id=".pg_escape_string($_POST['image']);
					$get_image_provider_ret = pg_query($db,$get_image_provider_sql);
					$image_id = pg_fetch_assoc($get_image_provider_ret);
					$get_zone_provider_sql = "SELECT id FROM iaas_zones WHERE provider=".pg_escape_string($_POST['provider'])." AND id=".pg_escape_string($_POST['zone']);
					$get_zone_provider_ret = pg_query($db,$get_zone_provider_sql);
					$zone_id = pg_fetch_assoc($get_zone_provider_ret);
					if($image_id['id'] == $_POST['image'] && $zone_id['id'] == $_POST['zone']){
						
						echo "<p>Adding GW Server</p>";
						$output = shell_exec("./cli/iaas.py gw_server create ".$_POST['gw_name']." ".$_POST['dns_zone']." ".$_POST['provider']." ".$_POST['image']." ".$_POST['zone']."");
						echo "<pre>".$output."</pre>";
					}
					else{
						echo "<p>Image or Zone does not match IaaS Provider</p>";
					}
				}
				else{
					echo "<p>Invalid Domain Name</p>";
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
	elseif($_POST['action'] == "update"){	// Update a GW Server
		
	}
	elseif($_POST['action'] == "delete"){	// Delete a GW Server
		if($_POST['gw_id']){
			echo "<p>Deleting GW Server</p>";
			$output = shell_exec("./cli/iaas.py gw_server destroy ".$_POST['gw_id']);
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
$sql = "SELECT * FROM gw_servers";
$ret = pg_query($db, $sql);
if(!$ret){
	echo pg_last_error($db);
	exit;
}
$num_fields = pg_num_fields($ret);
?>

<!-- Insert WGGW -->
<table>
	<thead>
<tr><th>Name</th><th>Provider</th><th>VM Image</th><th>Zone</th><th>DNS Zone</th></tr>
</thead>
<tr>
<form method="post" action="gw_servers.php">
	<input type="hidden" name="action" value="add">
	<td><input type="text" name="gw_name" value="<?php echo $default_gw_name_val; ?>"></td>
		
	<td><select name="provider" id="insert_gw_provider">
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
	
	<td><select name="image" id="insert_gw_image">
<?php
// Discover IaaS Providers
$discover_image_sql = "SELECT * FROM iaas_vm_images WHERE type=1";
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
	
	<td><select name="zone" id="insert_gw_zone">
<?php
// Discover IaaS Zones
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
	
	<td><select name="dns_zone" id="insert_gw_dns_zone">
<?php
// Discover DNS Zones
$discover_zone_sql = "SELECT * FROM dns_zones";
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
		
	<td><input type="submit" name="submit" value="Add GW Server"></td>
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
	if(pg_field_name($ret,$i) != "unique_id" && pg_field_name($ret,$i) != 'provider_uid' && pg_field_name($ret,$i) != 'dns_record_uid'){
		echo "<th>".pg_field_name($ret,$i)."</th>";
	}
}
?>

<th>Or Delete</th>
</tr>
</thead>




<?php
//dynamically print row data ready to be modified

while($row = pg_fetch_assoc($ret)){
	
	echo "<tr>";

	// modify form
	
	echo "<form method=\"post\" action=\"gw_servers.php\" onsubmit=\"return confirm('Are you sure you want to Update this GW Server?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"gw_id\" value=\"".$row['id']."\">";
	echo "<td>".$row['id']."</td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"dns_name\" value=\"".$row['name']."\"></td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"port\" value=\"".$row['port']."\"></td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"ipv4_addr\" value=\"".$row['ipv4_addr']."\"></td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"ipv6_addr\" value=\"".$row['ipv6_addr']."\"></td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"pub_key\" value=\"".$row['pub_key']."\"></td>";
	
	echo "<td>".date("F j, Y, g:i a", strtotime($row['created_at']))."</td><td>".date("F j, Y, g:i a", strtotime($row['updated_at']))."</td>";	
	
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"pub_ipv4_addr\" value=\"".$row['pub_ipv4_addr']."\"></td>";
	echo "<td><input type=\"text\" readonly=\"readonly\" name=\"pub_ipv6_addr\" value=\"".$row['pub_ipv6_addr']."\"></td>";
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
	
	echo "<td>"; 
	// Discover DNS Zone
	$discover_dns_zone_sql = "SELECT * FROM dns_zones WHERE id=".$row['dns_zone'];
	$discover_dns_zone_ret = pg_query($db,$discover_dns_zone_sql);
	if($discover_dns_zone_ret){
		while($dns = pg_fetch_assoc($discover_dns_zone_ret)){
			echo "<p>".$dns['name']."</p>";
		}
	}
	else{
		echo "<p>".pg_last_error($db)."</p>";
	}
	echo "</td>";
	
	echo "<td>"; 
	// Discover DNS Provider
	$discover_dns_provider_sql = "SELECT * FROM dns_providers WHERE id=".$row['dns_provider'];
	$discover_dns_provider_ret = pg_query($db,$discover_dns_provider_sql);
	if($discover_dns_provider_ret){
		while($dnsprovider = pg_fetch_assoc($discover_dns_provider_ret)){
			echo "<p>".$dnsprovider['name']."</p>";
		}
	}
	else{
		echo "<p>".pg_last_error($db)."</p>";
	}
	echo "</td>";
	
	echo "<td>";
	echo "<form method=\"post\" action=\"gw_servers.php\" onsubmit=\"return confirm('Are you sure you want to Delete this GW Server?');\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "<input type=\"hidden\" name=\"gw_id\" value=\"".$row['id']."\">";
	echo "<input type=\"submit\" name=\"submit\" value=\"Delete GW\" >";
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
